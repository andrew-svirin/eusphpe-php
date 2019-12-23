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

  public function setUp()
  {
    parent::setUp();
    $this->serversDir = $this->data . '/servers';
    $this->keysDir = $this->data . '/keys';
    $this->certsDir = $this->data . '/certificates';
  }

  public function testOne()
  {
    // Do not store this token. 32 characters.
    $user = $this->getUser(1);
    $serverStorage = new \UIS\EUSPE\ServerStorage($this->serversDir);
    $keyStorage = new \UIS\EUSPE\KeyStorage($this->keysDir);
    $certStorage = new \UIS\EUSPE\CertStorage($this->certsDir);
    $serverStorage->clearExpired(); // Run it by cron every 1 hour.
    $keyStorage->clearExpired(); // Run it by cron every 1 hour.
    $certStorage->clearExpired(); // Run it by cron every 1 hour.
    $key = $keyStorage->get($user['serverName'], $user['userName']);
    $cert = $certStorage->get($user['serverName'], $user['userName']);
    $client = new \UIS\EUSPE\Client();
    try {
      $server = $serverStorage->get($user['serverName'], $user['userName']);
      $server->configure($cert, $serverStorage);
      $client->open();
      $settings = $client->getFileStoreSettings();
      $this->assertNotEmpty($settings);
      if (!$key->exists()) {
        $keyData = file_get_contents($this->data . '/' . $user['userName'] . '.' . $user['keyType']);
        $key->setup(
          $client->encrypt($client->prepareKey($keyData, $user['keyType']), $user['secretToken']),
          $client->encrypt($user['password'], $user['secretToken'])
        );
      }
      $client->retrieveKeyAndCertificates($key, $cert, $user['secretToken']);
      $client->parseCertificates($cert->getCerts());
      $client->signData('Data for sign 123', $key, $cert, $user['secretToken']);
    } catch (\Exception $ex) {
      print "FAIL {$ex->getMessage()} {$ex->getCode()}<br/>\r\n";
    } finally {
      if (isset($client)) {
        $client->close();
      }
      if (isset($server)) {
        $server->unconfigure();
      }
    }
    return;
  }

  private function getUser($id)
  {
    if (!($user = getenv(sprintf('USER_%d', $id)))) {
      return null;
    }
    [$secretToken, $serverName, $userName, $keyType, $password] = explode(':', $user);
    return [
      'secretToken' => base64_decode($secretToken),
      'serverName' => $serverName,
      'userName' => $userName,
      'keyType' => $keyType,
      'password' => $password,
    ];
  }
}