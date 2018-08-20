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
        $data = parent::getElastic($this->index, $id);
        if ($data)
            return $data;

        $json = new Json($this->index . "/" . $id);
        $data = $json->get();
        if (!empty($data))
            parent::addElastic($this->index, $id, $data);

        return $data;
    }

    /**
     * @param string $id
     * @param array|null $data
     * @return string
     */
    public function add(string $id, array $data = null): string
    {
        new Json($this->index . '/' . $id, $data);
        return parent::addElastic($this->index, $id, $data);
    }

    /**
     * @param string $id
     * @param array|null $data
     * @return string
     */
    public function update(string $id, array $data = null): string
    {
        if ($this->get($id)) {
            $json = new Json($this->index . '/' . $id, $data);
            $json->save();
            return parent::updateElastic($this->index, $id, $data);
        }
    }

    /**
     * @param string $id
     */
    public function delete(string $id)
    {
        $json = new Json($this->index . "/" . $id);
        $json->delete();
        parent::deleteElastic($this->index, $id);
    }
}