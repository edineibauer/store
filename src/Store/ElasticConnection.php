<?php

namespace Store;

use Elasticsearch\ClientBuilder;

abstract class ElasticConnection
{
    private $host = "localhost";
    private $user = "user";
    private $pass = "pass";
    private $port = "9200";
    private $sslCacert;
    private $client;

    /**
     * @param array $host
     */
    protected function setHost(array $host)
    {
        $this->host = $host;
    }

    /**
     * @param mixed $pass
     */
    protected function setPass($pass)
    {
        $this->pass = $pass;
    }

    /**
     * @param mixed $user
     */
    protected function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @param mixed $port
     */
    protected function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * @param string $sslCacert
     */
    protected function setSslCacert(string $sslCacert)
    {
        $this->sslCacert = $sslCacert;
    }
    
    /**
     * @return \Elasticsearch\Client
     */
    protected function elasticsearch()
    {
        if(!$this->client) {
            $host = ['http' . ($this->sslCacert ? 's' : '') . "://{$this->user}:{$this->pass}@{$this->host}:{$this->port}"];
            $client = ClientBuilder::create()->setHosts($host);

            if ($this->sslCacert)
                $client->setSSLVerification($this->sslCacert);
            $this->client = $client->build();
        }

        return $this->client;
    }
}