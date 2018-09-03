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
    private function handleResult(string $command, int $iResult, int $iErrorCode): bool
    {
        $sErrorDescription = '';
        $bError = ($iResult != EM_RESULT_OK);

        if ($bError) {
            euspe_geterrdescr($iErrorCode, $sErrorDescription);
            throw new \Exception($command . ' - ' . $sErrorDescription, $iErrorCode);
        } elseif($this->debug) {
            print "{$command} = OK <br/>\r\n";
        }
        return !$bError;
    }

    /**
     * @throws \Exception
     */
    public function setup(): void
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

    public function readPrivateKey(Key $key): void
    {
    }

    public function close(): void
    {
        euspe_finalize();
    }

}