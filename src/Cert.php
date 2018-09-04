<?php

namespace UIS\EUSPE;

class Cert
{

    private $settingsPath;
    private $key;

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
        return empty($result) ? null : $result;
    }

    public function getCerts(): array
    {
        $certs = [];
        foreach ($this->getCertFiles() as $certFile) {
            $certs[] = file_get_contents($certFile, FILE_USE_INCLUDE_PATH);
        }
        return $certs;
    }
}