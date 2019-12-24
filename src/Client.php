<?php

namespace UIS\EUSPE;

use Exception;

class Client implements ClientInterface
{

  /**
   * @param string $command
   * @param int|void $iResult
   * @param int $iErrorCode
   * @return bool
   * @throws Exception
   */
  private function handleResult(string $command, $iResult, int $iErrorCode = null): bool
  {
    if (empty($iErrorCode) && !empty($iResult)) {
      euspe_geterrdescr($iErrorCode, $sErrorDescription);
      throw new Exception(sprintf('%s %s Error: %s. Check error in EUSignConsts.php by code.', dechex($iResult), $command, $sErrorDescription), $iErrorCode);
    }
    return $iResult;
  }

  /**
   * Open connection to server.
   * @throws Exception
   */
  public function open(): void
  {
    $this->handleResult('setcharset', euspe_setcharset(EM_ENCODING_UTF8));
    $this->handleResult('init', euspe_init($iErrorCode), $iErrorCode);
  }

  /**
   * @return array
   * @throws Exception
   */
  public function getFileStoreSettings(): array
  {
    $this->handleResult('getfilestoresettings', euspe_getfilestoresettings(
      $sFileStorePath,
      $bCheckCRLs,
      $bAutoRefresh,
      $bOwnCRLsOnly,
      $bFullAndDeltaCRLs,
      $bAutoDownloadCRLs,
      $bSaveLoadedCerts,
      $iExpireTime,
      $iErrorCode
    ), $iErrorCode);
    return [
      'sFileStorePath' => $sFileStorePath,
      'bCheckCRLs' => $bCheckCRLs,
      'bAutoRefresh' => $bAutoRefresh,
      'bOwnCRLsOnly' => $bOwnCRLsOnly,
      'bFullAndDeltaCRLs' => $bFullAndDeltaCRLs,
      'bAutoDownloadCRLs' => $bAutoDownloadCRLs,
      'bSaveLoadedCerts' => $bSaveLoadedCerts,
      'iExpireTime' => $iExpireTime,
    ];
  }

  /**
   * @param Key $key
   * @param Certificate $cert
   * @param string $secretToken
   * @throws Exception
   */
  public function retrieveKeyAndCertificates(Key $key, Certificate $cert, string $secretToken): void
  {
    if (null !== $cert->getCertFiles()) {
      return;
    }
    $this->handleResult(
      'readprivatekeybinary(DAT)',
      euspe_readprivatekeybinary(
        $this->decrypt($key->getKey(), $secretToken),
        $this->decrypt($key->getPassword(), $secretToken),
        $iErrorCode
      ),
      $iErrorCode
    );
    $this->handleResult(
      'isprivatekeyreaded',
      euspe_isprivatekeyreaded($bIsPrivateKeyRead, $iErrorCode),
      $iErrorCode
    );
    if (!$bIsPrivateKeyRead) {
      throw new Exception('Can not retrieve Key and Certificates. Private key was not read.');
    }
    $this->handleResult('resetprivatekey', euspe_resetprivatekey());
  }

  /**
   * Prepare multiple keys for jks.
   * @param User $user
   * @return array
   * @throws Exception
   */
  public function prepareKeys(User $user)
  {
    $this->handleResult(
      'setruntimeparameter (RESOLVE_OIDS)',
      euspe_setruntimeparameter(EU_RESOLVE_OIDS_PARAMETER, false, $iErrorCode),
      $iErrorCode
    );
    $iKeyIndex = 0;
    $certificates = [];
    while (empty($iResult)) {
      $iResult = $this->handleResult(
        'enumjksprivatekeys',
        euspe_enumjksprivatekeys(
          $user->getKeyData(),
          $iKeyIndex,
          $sKeyAlias,
          $iErrorCode
        ),
        EM_RESULT_ERROR
      );

      $this->handleResult(
        'getjksprivatekey',
        euspe_getjksprivatekey(
          $user->getKeyData(),
          $sKeyAlias,
          $sPrivateKeyData,
          $aCertificates,
          $iErrorCode
        )
      );
      $this->handleResult(
        'setruntimeparameter (RESOLVE_OIDS)',
        euspe_setruntimeparameter(EU_RESOLVE_OIDS_PARAMETER, true, $iErrorCode)
      );
      $parsedCerts = $this->parseCertificates($aCertificates);
      $certificates[] = $parsedCerts;

      $iKeyIndex++;
    }
    return $certificates;
  }

  /**
   * @param array $certs
   * @return array
   * @throws Exception
   */
  public function parseCertificates(array $certs): array
  {
    $parsed = [];
    foreach ($certs as $certData) {
      $this->handleResult(
        'parsecert',
        euspe_parsecert($certData, $certInfo, $iErrorCode),
        $iErrorCode
      );
      if (EU_SUBJECT_TYPE_END_USER !== $certInfo['subjType']) {
        continue;
      }
      $parsed[] = $certInfo;
    }
    return $parsed;
  }

  /**
   * @param string $data
   * @param Key $key
   * @param Certificate $cert
   * @param string $secretToken
   * @return string
   * @throws Exception
   */
  public function signData(string $data, Key $key, Certificate $cert, string $secretToken): string
  {
    $this->handleResult('ctxcreate', euspe_ctxcreate($context, $iErrorCode), $iErrorCode);
    $this->handleResult(
      'ctxreadprivatekeybinary',
      euspe_ctxreadprivatekeybinary(
        $context,
        $this->decrypt($key->getKey(), $secretToken),
        $this->decrypt($key->getPassword(), $secretToken),
        $pkContext,
        $iErrorCode),
      $iErrorCode
    );
    if (0 === $iErrorCode) {
      $this->handleResult(
        'ctxsigndata',
        euspe_ctxsigndata($pkContext, EU_CTX_SIGN_DSTU4145_WITH_GOST34311, $data, true, true, $sSign, $iErrorCode),
        $iErrorCode
      );
      $this->handleResult(
        'ctxisalreadysigned',
        euspe_ctxisalreadysigned($pkContext, EU_CTX_SIGN_DSTU4145_WITH_GOST34311, $sSign, $bIsAlreadySigned, $iErrorCode),
        $iErrorCode
      );
      if (!$bIsAlreadySigned) {
        throw new Exception('Content not signed properly.');
      }
    }
    $this->handleResult('ctxfreeprivatekey', euspe_ctxfreeprivatekey($pkContext));
    $this->handleResult('ctxfree', euspe_ctxfree($context));
    return $sSign;
  }

  public function close(): void
  {
    euspe_finalize();
  }

  function getSignerCertInfo(string $data): array
  {
    // TODO: Implement getSignerCertInfo() method.
    return [];
  }

  function hasData(string $data): string
  {
    // TODO: Implement hasData() method.
    return '';
  }

  function envelopData(string $data, array $certs): string
  {
    // TODO: Implement envelopData() method.
    return '';
  }

  /**
   * @param string $data - message to encrypt
   * @param string $secretToken - encryption key
   * @return string
   * @throws Exception
   */
  public function encrypt(string $data, string $secretToken): string
  {
    if (mb_strlen($secretToken, '8bit') !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
      throw new Exception('Key is not the correct size (must be 32 bytes).');
    }
    $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $cipher = $nonce . sodium_crypto_secretbox($data, $nonce, $secretToken);
    sodium_memzero($data);
    sodium_memzero($secretToken);
    return $cipher;
  }

  /**
   * @param string $encryptedData - message encrypted with safeEncrypt()
   * @param string $secretToken - encryption key
   * @return string
   * @throws Exception
   */
  public function decrypt(string $encryptedData, string $secretToken): string
  {
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
}