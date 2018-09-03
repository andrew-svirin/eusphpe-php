<?php

namespace UIS\EUSPE;

class Server
{

    private $serverStorage;

    private $host;

    public function __construct(ServerStorage $serverStorage)
    {
        $this->serverStorage = $serverStorage;
    }

    /**
     * @param Cert $cert
     * @return string
     * @throws \Exception
     */
    private function getTemplatePath(Cert $cert): string
    {
        $path = "{$this->serverStorage->getSettingsDir()}/{$cert->getKey()->getServer()->getHost()}/osplm.ini";
        if (!is_file($path)) {
            throw new \Exception('Missing template file osplm.ini');
        }
        return $path;
    }

    /**
     * @param Cert $cert
     * @return void
     * @throws \Exception
     */
    public function setupConfiguration(Cert $cert): void
    {
        $confDir = "{$this->serverStorage->getSettingsDir()}/{$cert->getKey()->getServer()->getHost()}/tmp/{$cert->getKey()->getName()}";
        $confPath = "{$confDir}/osplm.ini";
        if (!file_exists($confDir)) {
            mkdir($confDir);
            chmod($confDir, 0777);
            $template = file_get_contents($this->getTemplatePath($cert));
            $content = str_replace([
                '{dir}',
            ], [
                $cert->getSettingsPath(),
            ], $template);
            file_put_contents($confPath, $content);
            chmod($confPath, 0777);
        }
        if (!is_dir($cert->getSettingsPath())) {
            mkdir($cert->getSettingsPath());
            chmod($cert->getSettingsPath(), 0777);
        }
        if (!putenv("LD_LIBRARY_PATH={$confDir}")) {
            throw new \Exception('Can not setup env');
        }
    }

    /**
     * @param string $host
     * @throws \Exception
     */
    public function setHost(string $host): void
    {
        if (!ServerStorage::verifyHost($host)) {
            throw new \Exception("Server name {$host} is out of available list. Setup you server config first.");
        }
        $this->host = $host;
    }

    public function getHost(): string
    {
        return $this->host;
    }
}