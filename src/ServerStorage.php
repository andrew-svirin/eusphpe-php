<?php

namespace UIS\EUSPE;

use Exception;

class ServerStorage
{

  private $settingsDir;

  public function __construct(string $settingsDir)
  {
    $this->setSettingsDir($settingsDir);
  }

  public static function getEnumHosts(): array
  {
    return [
      'acskidd.gov.ua',
      'ca.ksystems.com.ua',
      'acsk.privatbank.ua',
      'ca.iit.com.ua',
    ];
  }

  public static function verifyHost(string $host): bool
  {
    return in_array($host, self::getEnumHosts());
  }

  private function setSettingsDir(string $settingsDir): void
  {
    $this->settingsDir = $settingsDir;
  }

  public function getSettingsDir(): string
  {
    return $this->settingsDir;
  }

  /**
   * Get server connection for user.
   * @param User $user
   * @param Certificate $cert
   * @return Server
   * @throws Exception
   */
  public function get(User $user, Certificate $cert): Server
  {
    $server = new Server($this);
    $server->setHost($user->getServerName());
    $server->setDir("{$this->settingsDir}/{$user->getServerName()}/{$user->getUserName()}");
    $server->configure();
    $confPath = sprintf('%s/osplm.ini', $server->getDir());
    if (!file_exists($confPath)) {
      // Prepare server configuration from template.
      $template = file_get_contents($this->getTemplatePath($user->getServerName()));
      $content = str_replace('{dir}', $cert->getDir(), $template);
      file_put_contents($confPath, $content);
    }
    return $server;
  }

  /**
   * @param string $serverName
   * @return string
   * @throws Exception
   */
  public function getTemplatePath(string $serverName): string
  {
    $path = sprintf('%s/servers/%s.dist.ini', __DIR__, $serverName);
    if (!file_exists($path)) {
      throw new Exception('Missing template file osplm.ini');
    }
    return $path;
  }

  public function clearExpired(int $ttl = 3600, $time = null): void
  {
    if (null === $time) {
      $time = time();
    }
    $servers = scandir($this->settingsDir);
    foreach ($servers as $server) {
      $serverDir = "{$this->settingsDir}/{$server}";
      if (!is_dir($serverDir) || '.' === $server || '..' === $server) {
        continue;
      }
      $users = scandir($serverDir);
      foreach ($users as $user) {
        $userDir = "{$serverDir}/{$user}";
        if (!is_dir($userDir) || '.' === $user || '..' === $user || ($time - filemtime($userDir) < $ttl)) {
          continue;
        }
        $files = scandir($userDir);
        foreach ($files as $file) {
          if ('.' === $file || '..' === $file) {
            continue;
          }
          unlink("{$userDir}/{$file}");
        }
        rmdir($userDir);
      }
    }
  }
}