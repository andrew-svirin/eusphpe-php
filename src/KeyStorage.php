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
        if (file_exists("{$dir}/key.dat")) {
            $key->setFilePath("{$dir}/key.dat");
            $key->setRole(Key::ROLE_DAT);
        } elseif (file_exists("{$dir}/key.jks")) {
            $key->setFilePath("{$dir}/key.jks");
            $key->setRole(Key::ROLE_JKS);
        } else {
            throw new \Exception('Not found key file in:' . $dir );
        }
        $key->setServer($server);
        return $key;
    }

    public function persist(Key $key, string $data): void
    {
        $filePath = "{$this->settingsDir}/{$key->getServer()->getHost()}/{$key->getName()}/key.dat";
        file_put_contents($filePath, $data);
        $key->setFilePath($filePath);
        $key->setRole(Key::ROLE_DAT);
    }
}