<?php

namespace UIS\EUSPE;

class KeyStorage
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

    /**
     * @param Server $server
     * @param string $name
     * @return Key
     * @throws \Exception
     */
    public function get(Server $server, string $name): Key
    {
        $dir = "{$this->settingsDir}/{$server->getHost()}/{$name}";
        if (!(is_dir($dir))) {
            throw new \Exception('Key not found in storage.');
        }
        $key = new Key();
        $key->setName($name);
        $key->setPassword(file_get_contents("{$dir}/password"));
        $key->setFilePath("{$dir}/key.dat");
        $key->setServer($server);
        return $key;
    }
}