<?php

namespace AndrewSvirin\EUSPE;

class User
{

  /**
   * @var string
   */
  private $userName;

  /**
   * @var string
   */
  private $serverHost;

  /**
   * @var string [dat|jks]
   */
  private $keyType;

  /**
   * @var string
   */
  private $keyData;

  /**
   * @var string
   */
  private $password;

  public function __construct(string $userName, string $serverHost, string $keyType = null, string $keyData = null, string $password = null)
  {
    $this->userName = $userName;
    $this->serverHost = $serverHost;
    $this->keyType = $keyType;
    $this->keyData = $keyData;
    $this->password = $password;
  }

  public function getUserName(): string
  {
    return $this->userName;
  }

  public function getServerHost(): string
  {
    return $this->serverHost;
  }

  public function getKeyType(): string
  {
    return $this->keyType;
  }

  public function getKeyData(): string
  {
    return $this->keyData;
  }

  public function getPassword(): string
  {
    return $this->password;
  }
}