1. Configure php extension euspe by documentation (docs/EUSPHPE-20180505/Documentation/EUSignPHPDescription.doc)
    1. Remove osplm.ini from extension directory. 
    1. Add to apache envvars ```LD_LIBRARY_PATH=/server/path/vendor/uis/euspe/servers/default```
1. Configure stubs for IDE to view functions docs.
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
require_once "vendor/autoload.php";

$serversDir = '/var/www/atlant/TestData/PHP/servers';
$keysDir = '/var/www/atlant/TestData/PHP/keys';
$certsDir = '/var/www/atlant/TestData/PHP/certificates';

//// Do not store this token. 32 characters.
$secretToken = ;
$serverName = ;
$userName = ;
$serverStorage = new \UIS\EUSPE\ServerStorage($serversDir);
$keyStorage = new \UIS\EUSPE\KeyStorage($keysDir);
$certStorage = new \UIS\EUSPE\CertStorage($certsDir);
$serverStorage->clearExpired(); // Run it by cron every 1 hour.
$keyStorage->clearExpired(); // Run it by cron every 1 hour.
$certStorage->clearExpired(); // Run it by cron every 1 hour.
$key = $keyStorage->get($serverName, $userName);
$cert = $certStorage->get($serverName, $userName);
$client = new \UIS\EUSPE\Client();
try {
    $server = $serverStorage->get($serverName, $userName);
    $server->configure($cert, $serverStorage);
    $client->open();
    print_r($client->getFileStoreSettings());
    if (!$key->exists()) {
        $keyType = ;
        $keyData = ;
        $password = ;
        $key->setup(
            $client->encrypt($client->prepareKey($keyData, $keyType), $secretToken),
            $client->encrypt($password, $secretToken)
        );
    }
    $client->retrieveKeyAndCertificates($key, $cert, $secretToken);
    $client->parseCertificates($cert->getCerts());
    $client->signData('Data for sign 123', $key, $cert, $secretToken);
} catch (Exception $ex) {
    $client->close();
    $server->unconfigure();
    print "FAIL {$ex->getMessage()} {$ex->getCode()}<br/>\r\n";
}
```