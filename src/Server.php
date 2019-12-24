<?php

namespace UIS\EUSPE;

use Exception;

class Server
{

  /**
   * @var ServerStorage
   */
  private $serverStorage;

  /**
   * @var string
   */
  private $host;

  /**
   * @var string
   */
  private $dir;

  public function __construct(ServerStorage $serverStorage)
  {
    $this->serverStorage = $serverStorage;
  }

  /**
   * Prepare servers dir.
   * @throws Exception
   */
  public function configure(): void
  {
    if (!file_exists($this->dir) && !(mkdir($this->dir, 0777, true))) {
      throw new Exception('Can not prepare servers dir.');
    }
  }

  /**
   * Open connection.
   * @return void
   * @throws Exception
   */
  public function open(): void
  {
    putenv(sprintf('LD_LIBRARY_PATH=%s', $this->dir));
  }

  /**
   * Close connection.
   */
  public function close(): void
  {
    putenv(sprintf('LD_LIBRARY_PATH=%s/servers/default', __DIR__));
  }

  /**
   * @param string $host
   * @throws Exception
   */
  public function setHost(string $host): void
  {
    if (!ServerStorage::verifyHost($host)) {
      throw new Exception(sprintf('Server name %s is out of available list. Setup you server config first.', $host));
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
