<?php

namespace UIS\EUSPE;

class ServerStorage
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

    public static function getEnumHosts(): array
    {
        return [
            'acskidd.gov.ua',
            'ca.ksystems.com.ua',
            'acsk.privatbank.ua',
        ];
    }

    public static function verifyHost(string $host): bool
    {
        return in_array($host, self::getEnumHosts());
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
     * @param string $serverName
     * @param string $userName
     * @return Server
     * @throws \Exception
     */
    public function get(string $serverName, string $userName): Server
    {
        $server = new Server($this);
        $server->setHost($serverName);
        $server->setDir("{$this->settingsDir}/{$serverName}/tmp/{$userName}");
        return $server;
    }

    /**
     * @param string $serverName
     * @return string
     * @throws \Exception
     */
    public function getTemplatePath(string $serverName): string
    {
        $path = "{$this->settingsDir}/{$serverName}/osplm.ini";
        if (!file_exists($path)) {
            throw new \Exception('Missing template file osplm.ini');
        }
        return $path;
    }
}