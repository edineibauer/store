<?php

class Store extends Elastic
{
    private $index;

    /**
     * Store constructor.
     * @param string $index
     */
    public function __construct(string $index)
    {
        $this->index = $index;
    }

    /**
     * @param string $id
     * @return array
     */
    public function get(string $id = null)
    {
        $params = [
            'index' => $this->index,
            'type' => $this->index,
            'id' => $id
        ];

        try {
            return $this->elasticsearch()->get($params)['_source'];
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @param string $id
     * @param null $data
     * @return string
     */
    public function add(string $id, $data = null): string
    {
        new Json($this->index . '/' . $id, $data);
        return parent::addElastic($this->index, $id, $data);
    }

    /**
     * @param string $id
     */
    public function delete(string $id) {
        $params = [
            'index' => $this->index,
            'type' => $this->index,
            'id' => $id
        ];


        try {
            $this->elasticsearch()->delete($params);
        } catch (Exception $e) {
        }
    }
}