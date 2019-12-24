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

  public function testClient()
  {
    $user = $this->getUser(2);
    $serverStorage = new \UIS\EUSPE\ServerStorage($this->serversDir);
    $keyStorage = new \UIS\EUSPE\KeyStorage($this->keysDir);
    $certStorage = new \UIS\EUSPE\CertificateStorage($this->certsDir);
    $serverStorage->clearExpired(); // Run it by cron every 1 hour.
    $keyStorage->clearExpired(); // Run it by cron every 1 hour.
    $certStorage->clearExpired(); // Run it by cron every 1 hour.
    $key = $keyStorage->get($user);
    $cert = $certStorage->get($user);
    $client = new \UIS\EUSPE\Client();
    try {
      $client->open();
      $server = $serverStorage->get($user, $cert);
      $server->open();
      $settings = $client->getFileStoreSettings();
      $this->assertNotEmpty($settings);
      if (!$key->exists()) {
        if ($user->keyTypeIsDAT()) {
          $key->setKey($client->encrypt($user->getKeyData(), $this->secretToken));
        } else {
          $keys = $client->prepareKeys($user);
          // TODO: Manage multiple certificates.
          $key->setKey($client->encrypt(json_encode($keys), $this->secretToken));
        }
        $key->setPassword($client->encrypt($user->getPassword(), $this->secretToken));
      }
      $client->retrieveKeyAndCertificates($key, $cert, $this->secretToken);
      $client->parseCertificates($cert->getCerts());
      $client->signData('Data for sign 123', $key, $cert, $this->secretToken);
    } finally {
      if (isset($client)) {
        $client->close();
      }
      if (isset($server)) {
        $server->close();
      }
    }
    return;
  }

  private function getUser($id): \UIS\EUSPE\User
  {
    if (!($user = getenv(sprintf('USER_%d', $id)))) {
      throw new \Exception('did not found user in .env file.');
    }
    [$serverName, $userName, $keyType, $password] = explode(':', $user);

    $user = new \UIS\EUSPE\User(
      $userName,
      $serverName,
      $keyType,
      file_get_contents("{$this->data}/{$userName}.{$keyType}"),
      $password
    );
    return $user;
  }
}