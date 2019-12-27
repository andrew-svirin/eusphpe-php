<?php

use PHPUnit\Framework\TestCase;

/**
 * Class ClientTest.
 */
class ClientTest extends TestCase
{

  var $data = __DIR__ . '/../_data';

  private $serversDir;

  private $keysDir;

  private $certsDir;

  private $secretToken;

  public function setUp()
  {
    parent::setUp();
    $this->serversDir = $this->data . '/servers';
    $this->keysDir = $this->data . '/keys';
    $this->certsDir = $this->data . '/certificates';
    $this->secretToken = base64_decode(getenv('SECRET_TOKEN_B64'));
  }

  /**
   * @throws Exception
   */
  public function testClientForUser5()
  {
    $this->checkClientForUser(5);
  }

  /**
   * @throws Exception
   */
  public function testClientForUser6()
  {
    $this->checkClientForUser(6);
  }

  /**
   * @param $id
   * @throws Exception
   */
  private function checkClientForUser($id)
  {
    $user = $this->getUser($id);
    $serverStorage = new \AndrewSvirin\EUSPE\ServerStorage($this->serversDir);
    $keyRingStorage = new \AndrewSvirin\EUSPE\KeyRingStorage($this->keysDir);
    $certStorage = new \AndrewSvirin\EUSPE\CertificateStorage($this->certsDir);
    $serverStorage->clearExpired(); // Run it by cron every 1 hour.
    $keyRingStorage->clearExpired(); // Run it by cron every 1 hour.
    $certStorage->clearExpired(); // Run it by cron every 1 hour.
    $keyRing = $keyRingStorage->prepare($user);
    $cert = $certStorage->prepare($user);
    $client = new \AndrewSvirin\EUSPE\Client();
    try {
      $server = $serverStorage->prepare($user, $cert);
      $server->open();
      $client->open();
      $settings = $client->getFileStoreSettings();
      $this->assertNotEmpty($settings);
      if (!$keyRingStorage->exists($keyRing)) {
        $keyRing->setPassword($user->getPassword());
        $keyRing->setType($user->getKeyType());
        if ($keyRing->typeIsDAT()) {
          $keyRing->setPrivateKeys([$user->getKeyData()]);
        }
        else {
          $keyRing->setPrivateKeys($client->retrieveJKSPrivateKeys($user->getKeyData()));
        }
        $keyRingStorage->store($keyRing, $this->secretToken);
      }
      $keyRingStorage->load($keyRing, $this->secretToken);
      if (!$cert->hasCerts()) {
        foreach ($keyRing->getPrivateKeys() as $privateKey) {
          $client->readPrivateKey($privateKey, $keyRing->getPassword());
          $client->resetPrivateKey();
        }
      }
      $certificates = $client->parseCertificates($cert->loadCerts());
      $this->assertNotEmpty($certificates);
      if ($keyRing->typeIsJKS()) {
        $sign = $client->signData('Data for sign 123', $keyRing->getPrivateKeyStamp(), $keyRing->getPassword());
        $signsCount = $client->getSignsCount($sign);
        $this->assertNotEmpty($signsCount);
        for ($i = 0; $i < $signsCount; $i++) {
          $signerInfo = $client->getSignerInfo($sign, $i);
          $this->assertNotEmpty($signerInfo);
        }
      }
    } finally {
      if (isset($client)) {
        $client->close();
      }
      if (isset($server)) {
        $server->close();
      }
    }
  }

  private function getUser($id): \AndrewSvirin\EUSPE\User
  {
    if (!($user = getenv(sprintf('USER_%d', $id)))) {
      throw new \Exception('did not found user in .env file.');
    }
    [$serverHost, $userName, $keyType, $password] = explode(':', $user);

    $user = new \AndrewSvirin\EUSPE\User(
      $userName,
      $serverHost,
      $keyType,
      file_get_contents("{$this->data}/{$userName}.{$keyType}"),
      $password
    );
    return $user;
  }
}