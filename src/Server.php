<?php

namespace AndrewSvirin\EUSPE;

use Exception;

/**
 * Class Server implements server for client configuration.
 */
class Server
{

  /**
   * @var string
   */
  private $dir;

  public function __construct($dir)
  {
    $this->dir = $dir;
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

  public function getOSPLMConfigPath(): string
  {
    return sprintf('%s/osplm.ini', $this->dir);
  }

  public function getOSPCUConfigPath(): string
  {
    return sprintf('%s/ospcu.ini', $this->dir);
  }
}
