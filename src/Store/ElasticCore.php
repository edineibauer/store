<?php

namespace Store;

abstract class ElasticCore extends ElasticConnection
{
    private $index;
    private $type;
    private $limit;
    private $offset;
    private $async = false;

    /**
     * ElasticCore constructor.
     * @param string $type
     */
    public function __construct(string $type)
    {
        $this->setType($type);
        $this->limit = 50;
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
     * @param int $limit
     */
    public function setLimit(int $limit)
    {
        $this->limit = $limit;
    }

    /**
     * @param int $offset
     */
    public function setOffset(int $offset)
    {
        $this->offset = $offset;
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
     * @return int
     */
    protected function getOffset(): int
    {
        return $this->offset ?? 0;
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
            "scroll" => "1s",
            "size" => $this->limit,
            'body' => ["query" => $param]
        ];

        if ($this->async)
            $body['client'] = ['future' => 'lazy'];

        return $body;
    }
}