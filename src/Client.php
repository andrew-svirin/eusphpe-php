<?php

namespace UIS\EUSPE;

class Client
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
    public function __construct(Server $server, CertStorage $certStorage, KeyStorage $keyStorage, bool $debug)
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
     * @throws \Exception
     */
    public function retrieveKeyAndCertificates(Key $key, Cert $cert): void
    {
        if (Key::ROLE_DAT === $key->getRole()) {
            $this->retrieveKeyAndCertificatesFromDat($key, $cert);
        } else if (Key::ROLE_JKS === $key->getRole()) {
            $this->retrieveKeyAndCertificatesFromJks($key, $cert);
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
        $cert->setCerts($certs);
        $key->setPk(file_get_contents($key->getFilePath(), FILE_USE_INCLUDE_PATH));
    }

    /**
     * @param Key $key
     * @param Cert $cert
     * @throws \Exception
     */
    private function retrieveKeyAndCertificatesFromJks(Key $key, Cert $cert): void
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
        $this->handleResult(
            'enumjksprivatekeys',
            euspe_enumjksprivatekeys($sJKSPrivateKeyData, $iKeyIndex, $sKeyAlias, $iErrorCode),
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
        $cert->setCerts($aCertificates);
        $key->setPk($sPrivateKeyData);
    }

    /**
     * @param Cert $cert
     * @return array
     * @throws \Exception
     */
    public function parseCertificates(Cert $cert): array
    {
        $parsed = [];
        foreach ($cert->getCerts() as $certData) {
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

    public function signData(string $data): string
    {
        
    }

    public function close(): void
    {
        euspe_finalize();
    }

}