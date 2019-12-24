<?php

namespace UIS\EUSPE;

interface ClientInterface
{

  function open(): void;

  function getFileStoreSettings(): array;

  function retrieveKeyAndCertificates(Key $key, Certificate $cert, string $secretToken): void;

  function parseCertificates(array $certs): array;

  function signData(string $data, Key $key, Certificate $cert, string $secretToken): string;

  function getSignerCertInfo(string $data): array;

  function hasData(string $data): string;

  function envelopData(string $data, array $certs): string;

  function close(): void;
}