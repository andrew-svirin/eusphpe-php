<?php

namespace AndrewSvirin\EUSPE;

interface ClientInterface
{

  /**
   * Open connection.
   */
  function open(): void;

  /**
   * Get actual file store settings.
   * @return array
   */
  function getFileStoreSettings(): array;

  /**
   * Download certificates to storage from remote server.
   * @param string $keyData
   * @param string $password
   */
  function readPrivateKey(string $keyData, string $password): void;

  /**
   * Reset private key after read.
   */
  function resetPrivateKey(): void;

  /**
   * Prepare multiple keys for jks.
   * @param string $keyData
   * @return array
   */
  function retrieveJKSPrivateKeys(string $keyData): array;

  /**
   * Parse certificates.
   * @param array $certs
   * @return array
   */
  function parseCertificates(array $certs): array;

  /**
   * Sign some data by private key.
   * @param string $data
   * @param string $keyData
   * @param string $password
   * @return string
   */
  function signData(string $data, string $keyData, string $password): string;

  /**
   * Count of signs in sign.
   * @param string $sign
   * @return int
   */
  function getSignsCount(string $sign): int;

  /**
   * Signer info from sign.
   * @param string $sign
   * @param int    $index
   * @return array
   */
  function getSignerInfo(string $sign, int $index): array;

  /**
   * @param string $data
   * @param array  $certs
   * @return string
   */
  function envelopData(string $data, array $certs): string;

  /**
   * Close connection.
   */
  function close(): void;
}