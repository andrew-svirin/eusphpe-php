<?php

namespace UIS\EUSPE;

class Key
{

    private $name;
    private $filePath;
    private $password;
    private $server;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): void
    {
        $this->filePath = $filePath;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getServer(): Server
    {
        return $this->server;
    }

    public function setServer(Server $server): void
    {
        $this->server = $server;
    }
}