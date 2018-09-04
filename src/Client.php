<?php

namespace UIS\EUSPE;

class Client implements ClientInterface
{

    /**
     * @var \UIS\EUSPE\Server
     */
    private $server;
    /**
     * @var CertStorage
     */
    private $certStorage;
    /**
     * @var KeyStorage
     */
    private $keyStorage;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @param \UIS\EUSPE\Server $server
     * @param CertStorage $certStorage
     * @param KeyStorage $keyStorage
     * @param bool $debug
     */
    public function __construct(Server $server, CertStorage $certStorage, KeyStorage $keyStorage, bool $debug = false)
    {
        $this->server = $server;
        $this->certStorage = $certStorage;
        $this->keyStorage = $keyStorage;
        $this->debug = $debug;
    }

    /**
     * @param string $command
     * @param int $iResult
     * @param int $iErrorCode
     * @return bool
     * @throws \Exception
     */
    private function handleResult(string $command, int $iResult, int $iErrorCode = 0): bool
    {
        $sErrorDescription = '';
        $bError = ($iResult != EM_RESULT_OK);

        if ($bError) {
            euspe_geterrdescr($iErrorCode, $sErrorDescription);
            throw new \Exception($command . ' - ' . $sErrorDescription, $iErrorCode);
        } elseif ($this->debug) {
            print "{$command} = OK <br/>\r\n";
        }
        return !$bError;
    }

    /**
     * @throws \Exception
     */
    public function open(): void
    {
        $iErrorCode = 0;
        $this->handleResult('setcharset', euspe_setcharset(EM_ENCODING_UTF8), $iErrorCode);
        $this->handleResult('init', euspe_init($iErrorCode), $iErrorCode);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getFileStoreSettings(): array
    {
        $iErrorCode = 0;
        $sFileStorePath = '';
        $bCheckCRLs = null;
        $bAutoRefresh = null;
        $bOwnCRLsOnly = null;
        $bFullAndDeltaCRLs = null;
        $bAutoDownloadCRLs = null;
        $bSaveLoadedCerts = null;
        $iExpireTime = 0;

        $this->handleResult('getfilestoresettings', euspe_getfilestoresettings(
            $sFileStorePath,
            $bCheckCRLs, $bAutoRefresh, $bOwnCRLsOnly,
            $bFullAndDeltaCRLs, $bAutoDownloadCRLs,
            $bSaveLoadedCerts, $iExpireTime,
            $iErrorCode), $iErrorCode);
        return [
            'sFileStorePath' => $sFileStorePath,
            'bCheckCRLs' => $bCheckCRLs,
            'bAutoRefresh' => $bAutoRefresh,
            'bOwnCRLsOnly' => $bOwnCRLsOnly,
            'bFullAndDeltaCRLs' => $bFullAndDeltaCRLs,
            'bAutoDownloadCRLs' => $bAutoDownloadCRLs,
            'bSaveLoadedCerts' => $bSaveLoadedCerts,
            'iExpireTime' => $iExpireTime,
        ];
    }

    /**
     * @throws \Exception
     */
    private function resetPrivateKey(): void
    {
        $this->handleResult('resetprivatekey', euspe_resetprivatekey());
    }

    /**
     * @param Key $key
     * @param Cert $cert
     * @param KeyStorage $keyStorage
     * @throws \Exception
     */
    public function retrieveKeyAndCertificates(Key $key, Cert $cert, KeyStorage $keyStorage): void
    {
        if (Key::ROLE_DAT === $key->getRole()) {
            $this->retrieveKeyAndCertificatesFromDat($key, $cert);
        } else if (Key::ROLE_JKS === $key->getRole()) {
            $this->retrieveKeyAndCertificatesFromJks($key, $keyStorage);
            $this->retrieveKeyAndCertificatesFromDat($key, $cert);
        } else {
            throw new \Exception('Incorrect key role.');
        }
    }

    /**
     * @param Key $key
     * @param Cert $cert
     * @throws \Exception
     */
    private function retrieveKeyAndCertificatesFromDat(Key $key, Cert $cert): void
    {
        $files = $cert->getCertFiles();
        if (null === $files) {
            $iErrorCode = 0;
            $this->handleResult(
                'readprivatekeyfile(DAT)',
                euspe_readprivatekeyfile($key->getFilePath(), $key->getPassword(), $iErrorCode),
                $iErrorCode
            );
            $bIsPrivateKeyRead = false;
            $this->handleResult(
                'isprivatekeyreaded',
                euspe_isprivatekeyreaded($bIsPrivateKeyRead, $iErrorCode),
                $iErrorCode
            );
            if (!$bIsPrivateKeyRead) {
                throw new \Exception('Private key was not read.');
            }
            $this->resetPrivateKey();
            $files = $cert->getCertFiles();
        }
        $certs = [];
        foreach ($files as $file) {
            $certs[] = file_get_contents($file, FILE_USE_INCLUDE_PATH);
        }
    }

    /**
     * @param Key $key
     * @param KeyStorage $keyStorage
     * @throws \Exception
     */
    private function retrieveKeyAndCertificatesFromJks(Key $key, KeyStorage $keyStorage): void
    {
        $iErrorCode = 0;
        $this->handleResult(
            'setruntimeparameter (RESOLVE_OIDS)',
            euspe_setruntimeparameter(EU_RESOLVE_OIDS_PARAMETER, false, $iErrorCode),
            $iErrorCode
        );
        $sJKSPrivateKeyData = file_get_contents($key->getFilePath(), FILE_USE_INCLUDE_PATH);
        $iKeyIndex = 0;
        $sKeyAlias = '';
        $sPrivateKeyData = null;
        $aCertificates = null;
        $iResult = 0;
        while (0 === $iResult) {
            $iResult = euspe_enumjksprivatekeys($sJKSPrivateKeyData, $iKeyIndex, $sKeyAlias, $iErrorCode);
            if (0 === $iResult) {
                $this->handleResult(
                    'enumjksprivatekeys',
                    $iResult,
                    $iErrorCode
                );
                $this->handleResult(
                    'getjksprivatekey',
                    euspe_getjksprivatekey($sJKSPrivateKeyData, $sKeyAlias, $sPrivateKeyData, $aCertificates, $iErrorCode),
                    $iErrorCode
                );
                $this->handleResult(
                    'setruntimeparameter (RESOLVE_OIDS)',
                    euspe_setruntimeparameter(EU_RESOLVE_OIDS_PARAMETER, true, $iErrorCode),
                    $iErrorCode
                );
                $parsedCerts = $this->parseCertificates($aCertificates);
                $certInfo = array_shift($parsedCerts);
                if (!empty($certInfo['subjDRFOCode'])) {
                    $keyStorage->persist($key, $sPrivateKeyData);
                    break;
                }
            }
            $iKeyIndex++;
        }
    }

    /**
     * @param array $certs
     * @return array
     * @throws \Exception
     */
    public function parseCertificates(array $certs): array
    {
        $parsed = [];
        foreach ($certs as $certData) {
            $this->handleResult(
                'parsecert',
                euspe_parsecert($certData, $certInfo, $iErrorCode),
                $iErrorCode
            );
            if (EU_SUBJECT_TYPE_END_USER !== $certInfo['subjType']) {
                continue;
            }
            $parsed[] = $certInfo;
        }
        return $parsed;
    }

    /**
     * @param string $data
     * @param Key $key
     * @return string
     * @throws \Exception
     */
    public function signData(string $data, Key $key): string
    {
        $iErrorCode = 0;
        $context = '';
        $pkContext = '';
        $sSign = '';
        $bIsAlreadySigned = false;
        $bExternal = true;
        $bAppendCert = true;

        $this->handleResult(
            'ctxcreate',
            euspe_ctxcreate($context, $iErrorCode),
            $iErrorCode
        );
        $this->handleResult(
            'ctxreadprivatekeyfile',
            euspe_ctxreadprivatekeyfile($context, $key->getFilePath(), $key->getPassword(), $pkContext, $iErrorCode),
            $iErrorCode
        );
        $this->handleResult(
            'ctxsigndata',
            euspe_ctxsigndata($pkContext, EU_CTX_SIGN_DSTU4145_WITH_GOST34311, $data, $bExternal, $bAppendCert, $sSign, $iErrorCode),
            $iErrorCode
        );
        $this->handleResult(
            'ctxisalreadysigned',
            euspe_ctxisalreadysigned($pkContext, EU_CTX_SIGN_DSTU4145_WITH_GOST34311, $sSign, $bIsAlreadySigned, $iErrorCode),
            $iErrorCode
        );
        if (!$bIsAlreadySigned) {
            throw new \Exception('Content not signed properly.');
        }
        euspe_ctxfreeprivatekey($pkContext);
        euspe_ctxfree($context);
        return $sSign;
    }

    public function close(): void
    {
        euspe_finalize();
    }

    function getSignerCertInfo(string $data): array
    {
        // TODO: Implement getSignerCertInfo() method.
    }

    function hasData(string $data): string
    {
        // TODO: Implement hasData() method.
    }

    function envelopData(string $data, array $certs): string
    {
        // TODO: Implement envelopData() method.
    }
}