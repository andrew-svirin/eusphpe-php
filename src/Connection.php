<?php

namespace AndrewSvirin\EUSPE;

class Connection
{

    /**
     * @var KeyRing
     */
    private $keyRing;

    /**
     * @var Certificate
     */
    private $certificate;

    /**
     * @var Server
     */
    private $server;

    public function __construct(KeyRing $keyRing, Certificate $certificate, Server $server)
    {
        $this->keyRing = $keyRing;
        $this->certificate = $certificate;
        $this->server = $server;
    }

    public function getKeyRing(): KeyRing
    {
        return $this->keyRing;
    }

    public function getCertificate(): Certificate
    {
        return $this->certificate;
    }

    public function getServer(): Server
    {
        return $this->server;
    }
}
