<?php

namespace UIS\EUSPE;

class Server
{

    private $serverStorage;
    private $host;
    private $dir;

    public function __construct(ServerStorage $serverStorage)
    {
        $this->serverStorage = $serverStorage;
    }

    /**
     * @param Cert $cert
     * @param ServerStorage $serverStorage
     * @return void
     * @throws \Exception
     */
    public function setup(Cert $cert, ServerStorage $serverStorage): void
    {
        $confPath = "{$this->dir}/osplm.ini";
        if (!file_exists($confPath)) {
            mkdir($this->dir, 0777, true);
            $template = file_get_contents($serverStorage->getTemplatePath($this->getHost()));
            $content = str_replace([
                '{dir}',
            ], [
                $cert->getDir(),
            ], $template);
            file_put_contents($confPath, $content);
        }
        if (!is_dir($cert->getDir())) {
            mkdir($cert->getDir(), 0777, true);
        }
        if (!putenv("LD_LIBRARY_PATH={$this->dir}")) {
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

    public function getDir(): string
    {
        return $this->dir;
    }

    public function setDir(string $dir): void
    {
        $this->dir = $dir;
    }
}