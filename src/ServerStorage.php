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
     * @param string $name
     * @return Server
     * @throws \Exception
     */
    public function get(string $name): Server
    {
        $server = new Server($this);
        $server->setHost($name);
        return $server;
    }
}