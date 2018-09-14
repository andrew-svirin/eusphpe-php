1. Configure php extension euspe by documentation and remove osplm.ini from extension directory. 
2. Configure stubs for IDE to view functions docs.
3. Configure directories for servers, certificates, keys. 
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
4. Use interface for communication.

5. Exam ple usage:
```php
require_once "vendor/autoload.php";

$serversDir = '/var/www/atlant/TestData/PHP/servers';
$keysDir = '/var/www/atlant/TestData/PHP/keys';
$certsDir = '/var/www/atlant/TestData/PHP/certificates';
$sensitivesDir = '/var/www/atlant/TestData/PHP/sensitives';

// Do not store this token. 32 characters.
$secretToken = '12312312312312312312312312323213';
//$serverName = 'ca.ksystems.com.ua';
$serverName = 'acskidd.gov.ua';
//$serverName = 'acsk.privatbank.ua';
//$userName = 'user-1';
$userName = 'user-2';
//$userName = 'user-3';
try {
    $serverStorage = new \UIS\EUSPE\ServerStorage($serversDir);
    $keyStorage = new \UIS\EUSPE\KeyStorage($keysDir);
    $certStorage = new \UIS\EUSPE\CertStorage($certsDir);
    $serverStorage->clearExpired(); // Run it by cron every 1 hour.
    $keyStorage->clearExpired(); // Run it by cron every 1 hour.
    $certStorage->clearExpired(); // Run it by cron every 1 hour.
    $key = $keyStorage->get($serverName, $userName);
    $cert = $certStorage->get($serverName, $userName);
    $server = $serverStorage->get($serverName, $userName);
    $server->setup($cert, $serverStorage);
    $client = new \UIS\EUSPE\Client(true);
    $client->open();
    print_r($client->getFileStoreSettings());
    if (!$key->exists()) {
        $keyType = 'dat';
//      $keyType = 'jks';
        $keyData = file_get_contents($sensitivesDir . '/' . $serverName . '/' . $userName . '/key.' . $keyType);
        $password = file_get_contents($sensitivesDir . '/' . $serverName . '/' . $userName . '/password');
        $key->setup(
            $client->encrypt($client->prepareKey($keyData, $keyType), $secretToken),
            $client->encrypt($password, $secretToken)
        );
    }
    $client->retrieveKeyAndCertificates($key, $cert, $secretToken);
    print_r($client->parseCertificates($cert->getCerts()));
    print_r($client->signData('Data for sign 123', $key, $cert, $secretToken));
    $client->close();
} catch (Exception $ex) {
    print "FAIL {$ex->getMessage()} {$ex->getCode()}<br/>\r\n";
}
```