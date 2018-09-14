<?php

namespace UIS\EUSPE;

class ServerStorage
{

    private $settingsDir;

    public function __construct(string $settingsDir)
    {
        $this->setSettingsDir($settingsDir);
    }

    public static function getEnumHosts(): array
    {
        return [
            'acskidd.gov.ua',
            'ca.ksystems.com.ua',
            'acsk.privatbank.ua',
            'ca.iit.com.ua',
        ];
    }

    public static function verifyHost(string $host): bool
    {
        return in_array($host, self::getEnumHosts());
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
     * @param string $serverName
     * @param string $userName
     * @return Server
     * @throws \Exception
     */
    public function get(string $serverName, string $userName): Server
    {
        $server = new Server($this);
        $server->setHost($serverName);
        $server->setDir("{$this->settingsDir}/{$serverName}/{$userName}");
        return $server;
    }

    /**
     * @param string $serverName
     * @return string
     * @throws \Exception
     */
    public function getTemplatePath(string $serverName): string
    {
        $reflection = new \ReflectionClass(\Composer\Autoload\ClassLoader::class);
        $vendorDir = dirname(dirname($reflection->getFileName()));
        $path = "{$vendorDir}/uis/euspe/servers/{$serverName}.dist.ini";
        if (!file_exists($path)) {
            throw new \Exception('Missing template file osplm.ini');
        }
        return $path;
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