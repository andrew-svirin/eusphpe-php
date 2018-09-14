<?php

namespace UIS\EUSPE;

class CertStorage
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

    public function get(string $serverName, string $userName): Cert
    {
        $cert = new Cert();
        $cert->setDir("{$this->settingsDir}/{$serverName}/{$userName}");
        return $cert;
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