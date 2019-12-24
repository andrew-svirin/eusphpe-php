<?php

namespace UIS\EUSPE;

class User
{

  /**
   * @var string
   */
  private $userName;

  /**
   * @var string
   */
  private $serverName;

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

  public function __construct($userName, $serverName, $keyType, $keyData, $password)
  {
    $this->userName = $userName;
    $this->serverName = $serverName;
    $this->keyType = $keyType;
    $this->keyData = $keyData;
    $this->password = $password;
  }

  /**
   * @return string
   */
  public function getUserName(): string
  {
    return $this->userName;
  }

  /**
   * @return string
   */
  public function getServerName(): string
  {
    return $this->serverName;
  }

  /**
   * @return string
   */
  public function getKeyType(): string
  {
    return $this->keyType;
  }

  /**
   * @return string
   */
  public function getKeyData(): string
  {
    return $this->keyData;
  }

  /**
   * @return string
   */
  public function getPassword(): string
  {
    return $this->password;
  }
}