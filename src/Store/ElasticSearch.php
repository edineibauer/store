<?php

class ElasticSearch extends ElasticCore
{
    private $result;

    /**
     * operação AND em array com term ou match.
     *
     * @param array $param
     * @param string $term
     * @return array|null
     */
    public function and(array $param, string $term = "term")
    {
        $filter = [
            "bool" => [
                "must" => $this->convertArray($param, $term)
            ]
        ];

        return $this->query($filter);
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
        $filter = [
            "bool" => [
                "should" => $this->convertArray($param, $term)
            ]
        ];

        return $this->query($filter);
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result['hits'];
    }

    public function getCount()
    {
        return $this->result['total'];
    }

    /**
     * @return array
     */
    public function getResultBest(): array
    {
        if ($this->result && $this->result['total'] > 0)
            return $this->result['hits'][0]['_source'];

        return [];
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

    private function query(array $params)
    {
        try {
            $this->result = $this->elasticsearch()->search($this->getBody($params))['hits'];
        } catch (Exception $e) {
            return null;
        }
    }
}