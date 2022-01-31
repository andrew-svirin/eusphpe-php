1. Configure php extension euspe by documentation (docs/EUSPHPE/Documentation/EUSignPHPDescription.doc)
    1. Remove osplm.ini from extension directory. 
    1. Add to apache envvars ```LD_LIBRARY_PATH=/server/path/vendor/uis/euspe/servers/default```
1. Configure stubs for IDE to view functions docs (https://github.com/andrew-svirin/phpstorm-stubs).
1. Configure directories for servers, certificates, keys. 
   Look for osplm.dist.ini as example.
   Setup 0777 permissions on folders.
```
- certificates\
- keys\
- servers\
   - server.name\
        - osplm.ini
   - ...
```
1. Use interface for communication.

1. Exam ple usage:
```php
    $user = new \AndrewSvirin\EUSPE\User(
      'user-name',
      'server.name',
      'dat|jks',
      'bDataKey',
      'secret'
    );
    $serverStorage = new \AndrewSvirin\EUSPE\ServerStorage($this->serversDir);
    $keyRingStorage = new \AndrewSvirin\EUSPE\KeyRingStorage($this->keysDir);
    $certStorage = new \AndrewSvirin\EUSPE\CertificateStorage($this->certsDir);
    $serverStorage->clearExpired(); // Run it by cron every 1 hour.
    $keyRingStorage->clearExpired(); // Run it by cron every 1 hour.
    $certStorage->clearExpired(); // Run it by cron every 1 hour.
    $keyRing = $keyRingStorage->prepare($user);
    $cert = $certStorage->prepare($user);
    try {
      $server = $serverStorage->prepare($user, $cert);
      $server->open();
      $client = new \AndrewSvirin\EUSPE\Client();
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
```
