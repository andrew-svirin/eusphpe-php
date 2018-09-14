<?php

namespace UIS\EUSPE;

use Exception;

class Client implements ClientInterface
{

    /**
     * @var bool
     */
    private $debug;

    /**
     * @param bool $debug
     */
    public function __construct(bool $debug = false)
    {
        $this->debug = $debug;
    }

    private function handleResult(string $command, int $iResult, int $iErrorCode = 0): bool
    {
        $sErrorDescription = '';
        $bError = ($iResult != EM_RESULT_OK);

        if ($bError) {
            euspe_geterrdescr($iErrorCode, $sErrorDescription);
            print "{$command} = FAIL {$sErrorDescription} {$iErrorCode} <br/>\r\n";
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
     * @param Key $key
     * @param Cert $cert
     * @param string $secretToken
     * @throws Exception
     */
    public function retrieveKeyAndCertificates(Key $key, Cert $cert, string $secretToken): void
    {
        if (null !== $cert->getCertFiles()) {
            return;
        }
        $iErrorCode = 0;
        $this->handleResult(
            'readprivatekeybinary(DAT)',
            euspe_readprivatekeybinary($this->decrypt($key->getFile(), $secretToken), $this->decrypt($key->getPassword(), $secretToken), $iErrorCode),
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
        euspe_resetprivatekey();
    }

    /**
     * @param string $keyData
     * @param string $keyType
     * @return string
     * @throws \Exception
     */
    public function prepareKey(string $keyData, string $keyType): string
    {
        if (Key::ROLE_JKS === $keyType) {
            $iErrorCode = 0;
            $this->handleResult(
                'setruntimeparameter (RESOLVE_OIDS)',
                euspe_setruntimeparameter(EU_RESOLVE_OIDS_PARAMETER, false, $iErrorCode),
                $iErrorCode
            );
            $iKeyIndex = 0;
            $sKeyAlias = '';
            $sPrivateKeyData = null;
            $aCertificates = null;
            $iResult = 0;
            while (0 === $iResult) {
                $iResult = euspe_enumjksprivatekeys($keyData, $iKeyIndex, $sKeyAlias, $iErrorCode);
                if (0 === $iResult) {
                    $this->handleResult(
                        'enumjksprivatekeys',
                        $iResult,
                        $iErrorCode
                    );
                    $this->handleResult(
                        'getjksprivatekey',
                        euspe_getjksprivatekey($keyData, $sKeyAlias, $sPrivateKeyData, $aCertificates, $iErrorCode),
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
                        return $sPrivateKeyData;
                    }
                }
                $iKeyIndex++;
            }
        } elseif (Key::ROLE_DAT === $keyType) {
            return $keyData;
        }
        throw new \Exception('Can not convert key.');
    }

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
     * @param Cert $cert
     * @param string $secretToken
     * @return string
     * @throws Exception
     */
    public function signData(string $data, Key $key, Cert $cert, string $secretToken): string
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
            'ctxreadprivatekeybinary',
            euspe_ctxreadprivatekeybinary(
                $context,
                $this->decrypt($key->getFile(), $secretToken),
                $this->decrypt($key->getPassword(), $secretToken),
                $pkContext,
                $iErrorCode),
            $iErrorCode
        );
        if (0 === $iErrorCode) {
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
        return [];
    }

    function hasData(string $data): string
    {
        // TODO: Implement hasData() method.
        return '';
    }

    function envelopData(string $data, array $certs): string
    {
        // TODO: Implement envelopData() method.
        return '';
    }

    /**
     * @param string $data - message to encrypt
     * @param string $secretToken - encryption key
     * @return string
     * @throws Exception
     */
    public function encrypt(string $data, string $secretToken): string
    {
        if (mb_strlen($secretToken, '8bit') !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new Exception('Key is not the correct size (must be 32 bytes).');
        }
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = $nonce . sodium_crypto_secretbox($data, $nonce, $secretToken);
        sodium_memzero($data);
        sodium_memzero($secretToken);
        return $cipher;
    }

    /**
     * @param string $encryptedData - message encrypted with safeEncrypt()
     * @param string $secretToken - encryption key
     * @return string
     * @throws Exception
     */
    public function decrypt(string $encryptedData, string $secretToken): string
    {
        $nonce = mb_substr($encryptedData, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
        $ciphertext = mb_substr($encryptedData, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');
        $plain = sodium_crypto_secretbox_open(
            $ciphertext,
            $nonce,
            $secretToken
        );
        if (!is_string($plain)) {
            throw new Exception('Invalid MAC');
        }
        sodium_memzero($ciphertext);
        sodium_memzero($secretToken);
        return $plain;
    }
}