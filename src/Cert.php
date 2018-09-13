<?php

namespace UIS\EUSPE;

class Cert
{

    private $dir;
    private $key;

    public function getDir(): string
    {
        return $this->dir;
    }

    public function setDir(string $dir): void
    {
        $this->dir = $dir;
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
        $files = scandir($this->dir);
        if (empty($files)) {
            return null;
        }
        $result = [];
        foreach ($files as $key => $file) {
            if ('.cer' === substr($file, -4)) {
                $result[] = "{$this->dir}/{$file}";
            }
        }
        return empty($result) ? null : $result;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getCerts(): array
    {
        if (!($certFiles = $this->getCertFiles())) {
            throw new \Exception('Certificates not found.');
        }
        $certs = [];
        foreach ($certFiles as $certFile) {
            $certs[] = file_get_contents($certFile, FILE_USE_INCLUDE_PATH);
        }
        return $certs;
    }

    public function getTimeGone(): ?int
    {
        $time = time();
        $lastRequestFilePath = "{$this->dir}/last-request.txt";
        if (!file_exists($lastRequestFilePath)) {
            $lastRequestTime = 0;
        } else {
            $lastRequestTime = (int)file_get_contents($lastRequestFilePath);
        }
        return $time - $lastRequestTime;
    }

    public function setTimeGone(): void
    {
        $time = time();
        $lastRequestFilePath = "{$this->dir}/last-request.txt";
        file_put_contents($lastRequestFilePath, $time);
    }
}