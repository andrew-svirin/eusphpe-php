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
   * @param Cert          $cert
   * @param ServerStorage $serverStorage
   * @return void
   * @throws \Exception
   */
  public function configure(Cert $cert, ServerStorage $serverStorage): void
  {
    $confPath = sprintf('%s/osplm.ini', $this->dir);
    if (!file_exists($confPath)) {
      if (!file_exists($this->dir)) {
        mkdir($this->dir, 0777, true);
      }
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
    putenv(sprintf('LD_LIBRARY_PATH=%s', $this->dir));
  }

  public function unconfigure(): void
  {
    putenv(sprintf("LD_LIBRARY_PATH=%s/servers/default", __DIR__));
  }

  /**
   * @param string $host
   * @throws \Exception
   */
  public function setHost(string $host): void
  {
    if (!ServerStorage::verifyHost($host)) {
      throw new \Exception(sprintf('Server name %s is out of available list. Setup you server config first.', $host));
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
