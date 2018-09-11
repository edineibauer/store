<?php

namespace Store;

abstract class ElasticCore extends ElasticConnection
{
    private $index;
    private $type;
    private $async = false;

    /**
     * ElasticCore constructor.
     * @param string $type
     */
    public function __construct(string $type)
    {
        $this->setType($type);
    }

    /**
     * @param string $type
     */
    public function setType(string $type)
    {
        $this->type = $type;
        $this->setIndex($type);
    }

    /**
     * @param string $index
     */
    private function setIndex(string $index)
    {
        $this->index = $index;
    }

    /**
     * Ativa Desativa Async Search
     * @param bool $async
     */
    public function setAsync(bool $async)
    {
        $this->async = $async;
    }

    /**
     * @return string
     */
    public function getIndex(): string
    {
        return $this->index;
    }

    /**
     * @return string
     */
    protected function getType(): string
    {
        return $this->type;
    }

    /**
     * @param array|null $param
     * @return array
     */
    protected function getBase(array $param = null)
    {
        $body = [
            'index' => $this->index,
            'type' => $this->type
        ];

        if ($param)
            $body = array_merge($body, $param);

        if ($this->async)
            $body['client'] = ['future' => 'lazy'];

        return $body;
    }

    protected function getBody($param)
    {
        $body = [
            'index' => $this->index,
            'type' => $this->type,
            'body' => ["query" => $param]
        ];

        if ($this->async)
            $body['client'] = ['future' => 'lazy'];

        return $body;
    }
}