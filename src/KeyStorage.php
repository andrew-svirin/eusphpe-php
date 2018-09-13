<?php

namespace UIS\EUSPE;

use Exception;

class KeyStorage
{

    private $settingsDir;

    /**
     * @param string $settingsDir
     * @throws Exception
     */
    public function __construct(string $settingsDir)
    {
        $this->setSettingsDir($settingsDir);
    }

    /**
     * @param string $settingsDir
     * @throws Exception
     */
    private function setSettingsDir(string $settingsDir): void
    {
        if (!is_dir($settingsDir)) {
            throw new Exception("Settings dir {$settingsDir} is not exists.");
        }
        $this->settingsDir = $settingsDir;
    }

    public function getSettingsDir(): string
    {
        return $this->settingsDir;
    }

    /**
     * @param string $hostName
     * @param string $userName
     * @return Key
     */
    public function get(string $hostName, string $userName): Key
    {
        $key = new Key();
        $key->setDir("{$this->settingsDir}/{$hostName}/{$userName}");
        return $key;
    }
}