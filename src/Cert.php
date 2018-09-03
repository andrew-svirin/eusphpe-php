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

}