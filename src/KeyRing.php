<?php

namespace AndrewSvirin\EUSPE;

use Exception;

class KeyRing
{

  private $dir;

  /**
   * @var string[]
   */
  private $privateKeys;

  /**
   * @var string dat|jks
   */
  private $type;

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
  public function configure(): void
  {
    if (!file_exists($this->dir) && !(mkdir($this->dir, 0777, true))) {
      throw new Exception('Can not prepare keys dir.');
    }
  }

  public function setPrivateKeys(array $privateKeys): void
  {
    $this->privateKeys = $privateKeys;
  }

  public function getPrivateKeys(): array
  {
    return $this->privateKeys;
  }

  public function getPrivateKeyStamp(): ?string
  {
    foreach ($this->privateKeys as $key => $privateKey) {
      if (substr_count($key, '_') > 1) {
        return $privateKey;
      }
    }
    return null;
  }

  public function getPassword(): string
  {
    return $this->password;
  }

  public function setPassword(string $password): void
  {
    $this->password = $password;
  }

  public function getType(): string
  {
    return $this->type;
  }

  public function setType(string $type): void
  {
    $this->type = $type;
  }

  public function typeIsJKS(): bool
  {
    return 'jks' === $this->type;
  }

  public function typeIsDAT(): bool
  {
    return 'dat' === $this->type;
  }
}