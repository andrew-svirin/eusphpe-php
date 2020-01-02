<?php

namespace AndrewSvirin\EUSPE;

use Exception;

class Client implements ClientInterface
{

  /**
   * @param string   $command
   * @param int|void $iResult
   * @param int      $iErrorCode
   * @param array    $aAcceptableErrorCodes
   * @return bool
   * @throws Exception
   */
  private function handleResult(string $command, $iResult, int $iErrorCode = null, array $aAcceptableErrorCodes = []): bool
  {
    if (!empty($iErrorCode) && !in_array($iErrorCode, $aAcceptableErrorCodes)) {
      euspe_geterrdescr($iErrorCode, $sErrorDescription);
      $utfEncoding = 'utf-8';
      throw new Exception(
        sprintf(
          'Result: %s Code: %s Command: %s Error: %s. Check error in EUSignConsts.php by code.',
          dechex($iResult),
          dechex($iErrorCode),
          $command,
          ($encoding = mb_detect_encoding($sErrorDescription)) && strtolower($encoding) !== $utfEncoding ?
                mb_convert_encoding($sErrorDescription, $encoding, $utfEncoding) :
                $sErrorDescription
        )
      );
    }
    return $iResult;
  }

  /**
   * {@inheritdoc}
   * @throws Exception
   */
  public function open(): void
  {
    $this->handleResult('setcharset', euspe_setcharset(EM_ENCODING_UTF8));
    $this->handleResult('init', euspe_init($iErrorCode), $iErrorCode);
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   * @throws Exception
   */
  public function readPrivateKey(string $keyData, string $password): void
  {
    $this->handleResult(
      'readprivatekeybinary(DAT)',
      euspe_readprivatekeybinary(
        $keyData,
        $password,
        $iErrorCode
      ),
      $iErrorCode,
      [1]
    );
    $this->handleResult(
      'isprivatekeyreaded',
      euspe_isprivatekeyreaded($bIsPrivateKeyRead, $iErrorCode),
      $iErrorCode
    );
    if (!$bIsPrivateKeyRead) {
      throw new Exception('Private key was not read.');
    }
  }

  /**
   * {@inheritdoc}
   * @throws Exception
   */
  public function resetPrivateKey(): void
  {
    $this->handleResult('resetprivatekey', euspe_resetprivatekey());
  }

  /**
   * {@inheritdoc}
   * @throws Exception
   */
  public function retrieveJKSPrivateKeys(string $keyData): array
  {
    $privateKeys = [];
    $this->handleResult(
      'setruntimeparameter (RESOLVE_OIDS)',
      euspe_setruntimeparameter(EU_RESOLVE_OIDS_PARAMETER, false, $iErrorCode),
      $iErrorCode
    );
    $iKeyIndex = 0;
    while (true) {
      $this->handleResult(
        'enumjksprivatekeys',
        euspe_enumjksprivatekeys(
          $keyData,
          $iKeyIndex,
          $sKeyAlias,
          $iErrorCode
        ),
        $iErrorCode,
        [7]
      );
      $iKeyIndex++;
      if (7 === $iErrorCode) {
        break;
      }
      $this->handleResult(
        'getjksprivatekey',
        euspe_getjksprivatekey(
          $keyData,
          $sKeyAlias,
          $sPrivateKeyData,
          $aCertificates,
          $iErrorCode
        ),
        $iErrorCode
      );
      $privateKeys[$sKeyAlias] = $sPrivateKeyData;
    }
    return $privateKeys;
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   * @throws Exception
   */
  public function signData(string $data, string $keyData, string $password): string
  {
    $this->handleResult('ctxcreate', euspe_ctxcreate($context, $iErrorCode), $iErrorCode);
    $this->handleResult(
      'ctxreadprivatekeybinary',
      euspe_ctxreadprivatekeybinary(
        $context,
        $keyData,
        $password,
        $pkContext,
        $iErrorCode),
      $iErrorCode
    );
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
    $this->handleResult('ctxfreeprivatekey', euspe_ctxfreeprivatekey($pkContext));
    $this->handleResult('ctxfree', euspe_ctxfree($context));
    return $sSign;
  }

  /**
   * {@inheritdoc}
   * @throws Exception
   */
  public function getSignsCount(string $sign): int
  {
    $this->handleResult('ctxfreeprivatekey', euspe_getsignscount($sign, $iCount, $iErrorCode), $iErrorCode);
    return $iCount;
  }

  /**
   * {@inheritdoc}
   * @throws Exception
   */
  public function getSignerInfo(string $sign, int $index): array
  {
    $this->handleResult('ctxfreeprivatekey', euspe_getsignerinfoex($index, $sign, $signerInfo, $signerCert, $iErrorCode), $iErrorCode);
    return $signerInfo;
  }

  /**
   * {@inheritdoc}
   */
  public function close(): void
  {
    euspe_finalize();
  }

  function envelopData(string $data, array $certs): string
  {
    // TODO: Implement envelopData() method.
    return '';
  }
}