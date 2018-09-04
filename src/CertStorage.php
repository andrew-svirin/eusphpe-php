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

    public function get(Key $key): Cert
    {
        $cert = new Cert();
        $cert->setKey($key);
        $cert->setSettingsPath("{$this->settingsDir}/{$key->getServer()->getHost()}/{$key->getName()}");
        return $cert;
    }

    public function persist(Key $key, string $data, array $info): void
    {
        $filePath = "CA-{$info['serial']}.cer";
        file_put_contents("{$this->settingsDir}/{$key->getServer()->getHost()}/{$key->getName()}/{$filePath}", $data);
    }
}