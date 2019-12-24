<?php

namespace UIS\EUSPE;

use Exception;

class Certificate
{

  private $dir;
  private $key;

  public function getDir(): string
  {
    return $this->dir;
  }

  public function setDir(string $dir): void
  {
    $this->dir = $dir;
  }

  public function getKey(): Key
  {
    return $this->key;
  }

  public function setKey(Key $key): void
  {
    $this->key = $key;
  }

  /**
   * Prepare dir for downloading certificates.
   * @throws Exception
   */
  public function configure()
  {
    if (!file_exists($this->dir) && !(mkdir($this->dir, 0777, true))) {
      throw new Exception('Can not prepare certificates dir.');
    }
  }

  public function getCertFiles(): ?array
  {
    $files = scandir($this->dir);
    if (empty($files)) {
      return null;
    }
    $result = [];
    foreach ($files as $key => $file) {
      if ('.cer' === substr($file, -4)) {
        $result[] = "{$this->dir}/{$file}";
      }
    }
    return empty($result) ? null : $result;
  }

  /**
   * @return array
   * @throws Exception
   */
  public function getCerts(): array
  {
    if (!($certFiles = $this->getCertFiles())) {
      throw new Exception('Certificates not found.');
    }
    $certs = [];
    foreach ($certFiles as $certFile) {
      $certs[] = file_get_contents($certFile, FILE_USE_INCLUDE_PATH);
    }
    return $certs;
  }
}