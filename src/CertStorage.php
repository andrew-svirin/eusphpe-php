<?php

namespace UIS\EUSPE;

class CertStorage
{

    private $settingsDir;

    /**
     * @param string $settingsDir
     * @throws \Exception
     */
    public function __construct(string $settingsDir)
    {
        $this->setSettingsDir($settingsDir);
    }

    /**
     * @param string $settingsDir
     * @throws \Exception
     */
    private function setSettingsDir(string $settingsDir): void
    {
        if (!is_dir($settingsDir)) {
            throw new \Exception("Settings dir {$settingsDir} is not exists.");
        }
        $this->settingsDir = $settingsDir;
    }

    public function getSettingsDir(): string
    {
        return $this->settingsDir;
    }

    public function get(string $serverName, string $userName): Cert
    {
        $cert = new Cert();
        $cert->setDir("{$this->settingsDir}/{$serverName}/{$userName}");
        return $cert;
    }
}