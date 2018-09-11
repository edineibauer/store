<?php

use \Helper\Helper;

class ElasticSearch extends ElasticCore
{
    private $filter;
    private $result;

    /**
     * Obtém através de um ID
     *
     * @param string $id
     * @return array|null
     */
    public function get(string $id)
    {
        try {
            if ($data = $this->elasticsearch()->get($this->getBase(["id" => $id])))
                return array_merge(["id" => $data['_id']], $data['_source']);

            $json = new Json("store/" . parent::getType());
            $data = $json->get($id);
            if (!empty($data)){
                $store = new Store(parent::getIndex());
                $store->add($id, $data);
            }

            return $data;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * operação AND em array com term ou match.
     *
     * @param array $param
     * @param string $term
     * @return array|null
     */
    public function and(array $param, string $term = "term")
    {
        if(!empty($this->filter['bool']['must'])) {
            $this->filter['bool']['must'] = Helper::arrayMerge($this->filter['bool']['must'],$this->convertArray($param, $term));
        } else {
            $this->filter['bool']['must'] = $this->convertArray($param, $term);
        }
    }

    /**
     * operação OR em array com term ou match.
     *
     * @param array $param
     * @param string $term
     * @return array|null
     */
    public function or(array $param, string $term = "term")
    {
        if(!empty($this->filter['bool']['should'])) {
            $this->filter['bool']['should'] = Helper::arrayMerge($this->filter['bool']['should'],$this->convertArray($param, $term));
        } else {
            $this->filter['bool']['should'] = $this->convertArray($param, $term);
        }
    }

    /**
     * Single Result
     *
     * @return array
     */
    public function getResult(): array
    {
        if(!$this->result)
            $this->query();

        if ($this->result && $this->result['total'] > 0)
            return array_merge(["id" => $this->result['hits'][0]['_id']], $this->result['hits'][0]['_source']);

        return [];
    }

    /**
     * Multiple Results
     *
     * @return mixed
     */
    public function getResults()
    {
        if(!$this->result)
            $this->query();

        $result = [];
        foreach ($this->result['hits'] as $item)
            $result[] = array_merge(["id" => $item['_id'], "_index" => $item['_index'], "_score" => $item['_score']], $item['_source']);

        return $result;
    }

    /**
     * Obtém o número de resultados encontrados
     *
     * @return mixed
     */
    public function getCount()
    {
        if(!$this->result)
            $this->query();

        return $this->result['total'];
    }

    /**
     * Converte um array em um array associativo ao termo passado
     *
     * @param array $data
     * @param string $term
     * @return array
     */
    private function convertArray(array $data, string $term)
    {
        $dataReturn = [];
        foreach ($data as $column => $value)
            $dataReturn[] = [$term => [$column => $value]];

        return $dataReturn;
    }

    /**
     * Executa a query DSL e atribui o result
     *
     */
    private function query()
    {
        try {
            $this->result = $this->elasticsearch()->search($this->getBody($this->filter))['hits'];
        } catch (Exception $e) {
        }
    }
}