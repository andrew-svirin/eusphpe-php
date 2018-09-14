<?php

namespace UIS\EUSPE;

use Exception;

class KeyStorage
{

    private $settingsDir;

    public function __construct(string $settingsDir)
    {
        $this->setSettingsDir($settingsDir);
    }

    private function setSettingsDir(string $settingsDir): void
    {
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

    public function clearExpired(int $ttl = 3600, $time = null): void
    {
        if (null === $time) {
            $time = time();
        }
        $servers = scandir($this->settingsDir);
        foreach ($servers as $server) {
            $serverDir = "{$this->settingsDir}/{$server}";
            if (!is_dir($serverDir) || '.' === $server || '..' === $server) {
                continue;
            }
            $users = scandir($serverDir);
            foreach ($users as $user) {
                $userDir = "{$serverDir}/{$user}";
                if (!is_dir($userDir) || '.' === $user || '..' === $user || ($time - filemtime($userDir) < $ttl)) {
                    continue;
                }
                $files = scandir($userDir);
                foreach ($files as $file) {
                    if ('.' === $file || '..' === $file) {
                        continue;
                    }
                    unlink("{$userDir}/{$file}");
                }
                rmdir($userDir);
            }
        }
    }
}