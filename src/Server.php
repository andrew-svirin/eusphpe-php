<?php

namespace UIS\EUSPE;

use Exception;

class Server
{

  /**
   * @var string
   */
  private $dir;

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
    $dir = realpath($this->dir);
    putenv(sprintf('LD_LIBRARY_PATH=%s', $dir));
  }

  /**
   * Close connection.
   */
  public function close(): void
  {
    $dir = sprintf('%s/servers/default', __DIR__);
    putenv(sprintf('LD_LIBRARY_PATH=%s', $dir));
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
