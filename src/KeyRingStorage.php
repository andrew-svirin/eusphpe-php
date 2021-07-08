<?php

namespace AndrewSvirin\EUSPE;

use Exception;
use AndrewSvirin\EUSPE\traits\ExpiredTrait;

class KeyRingStorage
{

  use ExpiredTrait;

  /**
   * @var string
   */
  protected $dir;

  public function __construct(string $dir)
  {
    $this->dir = $dir;
  }

  /**
   * @param User $user
   * @return KeyRing
   * @throws Exception
   */
  public function prepare(User $user): KeyRing
  {
    $key = new KeyRing("{$this->dir}/{$user->getServerHost()}/{$user->getUserName()}");
    $key->configure();
    return $key;
  }

  public function exists(KeyRing $keyRing): bool
  {
    return file_exists($keyRing->getFilePath());
  }

  /**
   * @param KeyRing $keyRing
   * @param string $secretToken
   * @throws Exception
   */
  public function store(KeyRing $keyRing, string $secretToken): void
  {
    $encodedPrivateKeys = [];
    foreach ($keyRing->getPrivateKeys() as $key => $privateKey) {
      $encodedPrivateKeys[$key] = base64_encode($privateKey);
    }
    if (!($keys = json_encode($encodedPrivateKeys))) {
      throw new Exception('Can not store keyRing.');
    }
    $data = [
      'keys' => $this->encrypt($keys, $secretToken),
      'password' => $this->encrypt($keyRing->getPassword(), $secretToken),
      'type' => $keyRing->getType(),
    ];
    if (!($dataEncoded = json_encode($data))) {
      throw new Exception('Can not store keyRing.');
    }
    file_put_contents($keyRing->getFilePath(), $dataEncoded);
  }

  /**
   * @param KeyRing $keyRing
   * @param string $secretToken
   * @throws Exception
   */
  public function load(KeyRing $keyRing, string $secretToken): void
  {
    if (!($file = file_get_contents($keyRing->getFilePath()))) {
      throw new Exception('Can not load keyRing.');
    }
    if (!($data = json_decode($file, true))) {
      throw new Exception('Can not load keyRing.');
    }
    if (!($encodedPrivateKeys = json_decode($this->decrypt($data['keys'], $secretToken)))) {
      throw new Exception('Can not load keyRing.');
    }
    $privateKeys = [];
    foreach ($encodedPrivateKeys as $key => $encodedPrivateKey) {
      if (!($privateKeys[$key] = base64_decode($encodedPrivateKey))) {
        throw new Exception('Can not load keyRing.');
      }
    }
    $keyRing->setPrivateKeys($privateKeys);
    $keyRing->setPassword($this->decrypt($data['password'], $secretToken));
    $keyRing->setType($data['type']);
  }

  /**
   * @param string $data - message to encrypt
   * @param string $secretToken - encryption key
   * @return string
   * @throws Exception
   */
  private function encrypt(string $data, string $secretToken): string
  {
    if (mb_strlen($secretToken, '8bit') !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
      throw new Exception('Key is not the correct size (must be 32 bytes).');
    }
    $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $encryptedData = $nonce . sodium_crypto_secretbox($data, $nonce, $secretToken);
    sodium_memzero($data);
    sodium_memzero($secretToken);
    return base64_encode($encryptedData);
  }

  /**
   * @param string $encryptedData - message encrypted with safeEncrypt()
   * @param string $secretToken - encryption key
   * @return string
   * @throws Exception
   */
  private function decrypt(string $encryptedData, string $secretToken): string
  {
    $encryptedData = base64_decode($encryptedData);
    $nonce = mb_substr($encryptedData, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
    $ciphertext = mb_substr($encryptedData, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');
    $plain = sodium_crypto_secretbox_open(
      $ciphertext,
      $nonce,
      $secretToken
    );
    if (!is_string($plain)) {
      throw new Exception('Invalid MAC');
    }
    sodium_memzero($ciphertext);
    sodium_memzero($secretToken);
    return $plain;
  }

  /**
   * @return string
   * @throws Exception
   */
  public function generateSecretToken(): string
  {
    return random_bytes(32);
  }
}
