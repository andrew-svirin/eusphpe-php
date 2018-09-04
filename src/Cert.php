<?php

namespace UIS\EUSPE;

class Cert
{

    private $settingsPath;
    private $key;
    private $certs;

    public function getSettingsPath(): string
    {
        return $this->settingsPath;
    }

    public function setSettingsPath(string $settingsPath): void
    {
        $this->settingsPath = $settingsPath;
    }

    public function getKey(): Key
    {
        return $this->key;
    }

    public function setKey(Key $key): void
    {
        $this->key = $key;
    }

    public function getCertFiles(): ?array
    {
        $files = scandir($this->settingsPath);
        if (empty($files)) {
            return null;
        }
        $result = [];
        foreach ($files as $key => $file) {
            if ('.cer' === substr($file, -4)) {
                $result[] = "{$this->settingsPath}/{$file}";
            }
        }
        return $result;
    }

    public function setCerts(array $certs): void
    {
        $this->certs = $certs;
    }

    public function getCerts(): array
    {
        return $this->certs;
    }

    public function getUserPaths(): array
    {
        $mainPaths = [];
        $files = scandir($this->settingsPath);
        foreach ($files as $file) {
            if ('CA' === substr($file, 0, 2) && '04000000' === substr($file, 19, 8)) {
                $mainPaths[] = "{$this->settingsPath }/{$file}";
            }
        }
        return $mainPaths;
    }

}