<?php

namespace UIS\EUSPE\traits;

/**
 * Trait ExpiredTrait
 * @property string $dir
 */
trait ExpiredTrait {

  public function clearExpired(int $ttl = 3600, $time = null): void
  {
    if (null === $time) {
      $time = time();
    }
    $servers = scandir($this->dir);
    foreach ($servers as $server) {
      $serverDir = "{$this->dir}/{$server}";
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
