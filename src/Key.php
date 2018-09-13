<?php

namespace UIS\EUSPE;

class Key
{

    const ROLE_DAT = 'dat';
    const ROLE_JKS = 'jks';

    private $dir;
    private $server;
    private $role;

    public function getFilePath(): string
    {
        return $this->dir . '/key.bin';
    }

    public function getPasswordPath(): string
    {
        return $this->dir . '/password.bin';
    }

    public function getServer(): Server
    {
        return $this->server;
    }

    public function setServer(Server $server): void
    {
        $this->server = $server;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): void
    {
        $this->role = $role;
    }

    public function exists(): bool
    {
        return file_exists($this->getFilePath()) && file_exists($this->getPasswordPath());
    }

    public function setup(string $file, string $password): void
    {
        mkdir($this->dir, 0777, true);
        file_put_contents($this->getFilePath(), $file);
        file_put_contents($this->getPasswordPath(), $password);
    }

    public function getFile(): string
    {
        return file_get_contents($this->getFilePath());
    }

    public function getPassword(): string
    {
        return file_get_contents($this->getPasswordPath());
    }

    public function getDir(): string
    {
        return $this->dir;
    }

    public function setDir(string $dir): void
    {
        $this->dir = $dir;
    }
}