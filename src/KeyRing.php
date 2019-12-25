<?php

namespace UIS\EUSPE;

use Exception;

class KeyRing
{

  private $dir;

  /**
   * @var string[]
   */
  private $privateKeys;

  /**
   * @var string
   */
  private $password;

  public function __construct($dir)
  {
    $this->dir = $dir;
  }

  public function getFilePath(): string
  {
    return $this->dir . '/key-ring.json';
  }

  /**
   * Prepare dir for downloading certificates.
   * @throws Exception
   */
  public function configure()
  {
    if (!file_exists($this->dir) && !(mkdir($this->dir, 0777, true))) {
      throw new Exception('Can not prepare keys dir.');
    }
  }

  public function setPrivateKeys(array $privateKeys)
  {
    $this->privateKeys = $privateKeys;
  }

  public function getPrivateKeys(): array
  {
    return $this->privateKeys;
  }

  public function getPrivateKeyStamp()
  {
    foreach ($this->privateKeys as $key => $privateKey) {
      if (substr_count($key, '_') > 1) {
        return $privateKey;
      }
    }
    return null;
  }

  /**
   * @return string
   */
  public function getPassword(): string
  {
    return $this->password;
  }

  /**
   * @param string $password
   */
  public function setPassword(string $password): void
  {
    $this->password = $password;
  }
}