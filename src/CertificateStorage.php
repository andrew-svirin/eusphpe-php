<?php

namespace UIS\EUSPE;

use Exception;
use UIS\EUSPE\traits\ExpiredTrait;

class CertificateStorage
{

  use ExpiredTrait;

  protected $dir;

  public function __construct(string $dir)
  {
    $this->dir = $dir;
  }

  /**
   * @param User $user
   * @return Certificate
   * @throws Exception
   */
  public function prepare(User $user): Certificate
  {
    $cert = new Certificate("{$this->dir}/{$user->getServerHost()}/{$user->getUserName()}");
    $cert->configure();
    return $cert;
  }

}