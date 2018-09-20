<?php

namespace Store;

class ElasticSearch extends ElasticCore
{
    private $filter;
    private $result;

    /**
     * Precisa que os valores existam
     * Gera Score
     *
     * @param array $param
     * @return $this
     */
    public function queryMust(array $param): ElasticSearch
    {
        $this->filter['must'] = $this->convertArray($param, "must", "term");
        return $this;
    }

    /**
     * Precisa que os valores não existam
     * Gera Score
     *
     * @param array $param
     * @return $this
     */
    public function queryMustNot(array $param): ElasticSearch
    {
        $this->filter['must_not'] = $this->convertArray($param, "must_not", "term");
        return $this;
    }

    /**
     * Deveria ter os valores
     * Agrega no Score
     *
     * @param array $param
     * @param int $minimo
     * @return $this
     */
    public function queryShould(array $param, int $minimo = 0): ElasticSearch
    {
        $this->filter['should'] = $this->convertArray($param, "should", "term");
        if ($minimo)
            $this->queryShouldHave($minimo);
        return $this;
    }

    /**
     * Mesmo que o Must, porém trabalha sem Score
     * ganhando performance e trabalhando com Cache
     *
     * @param array $param
     * @return $this
     */
    public function queryFilter(array $param): ElasticSearch
    {
        $this->filter['filter'] = $this->convertArray($param, "filter", "term");
        return $this;
    }

    /**
     * Precisa que a coluna tenha valores nulos
     * Gera Score
     *
     * @param string $column
     * @return $this
     */
    public function columnNull(string $column): ElasticSearch
    {
        $this->filter['must_not'] = $this->convertArray(["field" => $column], "must_not", "exists");
        return $this;
    }

    /**
     * Precisa que a coluna tenha valores não nulos
     * Gera Score
     *
     * @param string $column
     * @return $this
     */
    public function columnNotNull(string $column): ElasticSearch
    {
        $this->filter['must'] = $this->convertArray(["field" => $column], "must", "exists");
        return $this;
    }

    /**
     * @param string $column
     * @param $value
     * @return ElasticSearch
     */
    public function columnIquals(string $column, $value): ElasticSearch
    {
        $this->filter['filter'] = $this->convertArray([$column => $value], "filter", "term");
        return $this;
    }

    /**
     * Aplica Filtro com limite do valor
     *
     * @param string $column
     * @param $minimo
     * @param $maximo
     * @return ElasticSearch
     */
    public function columnRange(string $column, $minimo, $maximo): ElasticSearch
    {
        $this->filter['must']['range'][$column] = ['gte' => $minimo, 'lte' => $maximo];
        return $this;
    }

    /**
     * @param string $column
     * @param mixed $gte
     * @return ElasticSearch
     */
    public function columnGreaterThan(string $column, $gte): ElasticSearch
    {
        $this->filter['must']['range'][$column]['gte'] = $gte;
        return $this;
    }

    /**
     * @param string $column
     * @param mixed $lte
     * @return ElasticSearch
     */
    public function columnLessThan(string $column, $lte): ElasticSearch
    {
        $this->filter['must']['range'][$column]['lte'] = $lte;
        return $this;
    }

    /**
     * @param array $param
     * @return ElasticSearch
     */
    public function sqlLike(array $param): ElasticSearch
    {
        $nParam = [];
        foreach ($param as $c => $v) {
            if (is_array($v)) {
                foreach ($v as $item)
                    $nParam[$c][] = (strpos($item . " ", '*') === false ? "*{$item}*" : $item);
            } else {
                $nParam[$c] = (strpos($v . " ", '*') === false ? "*{$v}*" : $v);
            }
        }

        $this->filter['filter'] = $this->convertArray($nParam, "filter", "wildcard");
        return $this;
    }

    /**
     * @param array $param
     * @return ElasticSearch
     */
    public function sqlLikeBegin(array $param): ElasticSearch
    {
        $this->filter['filter'] = $this->convertArray($param, "filter", "prefix");
        return $this;
    }

    /**
     * @param array $param
     * @return ElasticSearch
     */
    public function sqlLikeEnd(array $param): ElasticSearch
    {
        $nParam = [];
        foreach ($param as $c => $v) {
            if (is_array($v)) {
                foreach ($v as $item) {
                    if (strpos($item . " ", '*') === false)
                        $nParam[$c][] = "*{$item}";
                }
            } else {
                if (strpos($v . " ", '*') === false)
                    $nParam[$c] = "*{$v}";
            }
        }

        $this->filter['filter'] = $this->convertArray($nParam, "filter", "wildcard");
        return $this;
    }

    /**
     * operação AND em array com term.
     *
     * @param array $param
     * @return $this
     */
    public function sqlAnd(array $param): ElasticSearch
    {
        $this->filter['filter'] = $this->convertArray($param, "filter", "term");
        return $this;
    }

    /**
     * Operação OR em array com term
     *
     * @param array $param
     * @return $this
     */
    public function sqlOr(array $param): ElasticSearch
    {
        $this->queryShouldHave(1);
        $this->filter['should'] = $this->convertArray($param, "should", "term");
        return $this;
    }

    /**
     * Single Result
     *
     * @param string|null $id
     * @return array
     */
    public function getResult(string $id = null): array
    {
        if ($id) {
            return $this->getResultById($id);
        } else {
            $this->query();

            if ($this->result && !empty($this->result['hits']['hits']) && $this->result['hits']['total'] > 0)
                return array_merge(["id" => $this->result['hits']['hits'][0]['_id']], $this->result['hits']['hits'][0]['_source']);

            return [];
        }
    }

    /**
     * Retorna lista de resultados
     *
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getResults(int $limit = 0, int $offset = 0)
    {
        if ($limit > 0)
            parent::setLimit($limit);

        if ($offset > 0)
            parent::setOffset($offset);

        $this->query();

        if (parent::getOffset() > 0) {
            $result = $this->result;
            for ($i = 0; $i < parent::getOffset(); $i++) {
                if (!empty($result['hits']['hits']) && count($result['hits']['hits']) > 0) {
                    $result = $this->elasticsearch()->scroll([
                            "scroll_id" => $result['_scroll_id'],
                            "scroll" => "1s"           // and the same timeout window
                        ]
                    );
                } else {
                    $result = [];
                    break;
                }
            }

            return $this->getResultFiltered($result);

        } elseif (!empty($this->result['hits']['hits'])) {
            return $this->getResultFiltered($this->result);
        }

        return [];
    }

    /**
     * @param array $resultados
     * @return array
     */
    private function getResultFiltered(array $resultados): array
    {
        $result = [];

        foreach ($resultados['hits']['hits'] as $item)
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
        $this->query();

        return $this->result['hits']['total'];
    }

    /**
     * Converte um array em um array associativo ao termo passado
     *
     * @param array $data
     * @param string $context
     * @param string $term
     * @return array
     */
    private function convertArray(array $data, string $context, string $term)
    {
        $dataReturn = [];
        foreach ($data as $column => $value) {
            if (is_array($value)) {
                foreach ($value as $v)
                    $dataReturn = $this->convertArrayValue($column, $v, $dataReturn, $term);
            } else {
                $dataReturn = $this->convertArrayValue($column, $value, $dataReturn, $term);
            }
        }

        return (!empty($this->filter[$context]) ? array_merge($this->filter[$context], $dataReturn) : $dataReturn);
    }

    /**
     * @param $column
     * @param $value
     * @param array $dataReturn
     * @param string $term
     * @return array
     */
    private function convertArrayValue($column, $value, array $dataReturn, string $term)
    {
        if ($term === "term" && is_string($value) && preg_match("/(@|\s|-|=|!|#|$|%|¨|&|\*|\(|\)|_|\+|\/|\\|\||\?|;|:|,|<|>|}|{|\]|\[|~|^|°|º|ª)/i", trim($value))) {
            $ex = array_filter(preg_split("/(@|\s|-|=|!|#|$|%|¨|&|\*|\(|\)|_|\+|\/|\\|\||\?|;|:|,|<|>|}|{|\]|\[|~|^|°|º|ª)/i", trim($value)), function ($value) {
                return ($value !== null && $value !== false && $value !== true && $value !== '');
            });
            foreach ($ex as $item)
                $dataReturn[] = $this->tokenize($term, $column, $item);

            return $dataReturn;
        }
        $dataReturn[] = $this->tokenize($term, $column, $value);

        return $dataReturn;
    }

    /**
     * @param string $term
     * @param string $column
     * @param $value
     * @return array
     */
    private function tokenize(string $term, string $column, $value): array
    {
        if (is_string($value))
            $value = mb_strtolower(trim($value));
        return [$term => [$column => $value]];
    }

    /**
     * Informa ao Should quantos valores devem existir ao mínimo
     * por exemplo, para termos um OR, precisamos que ao mínimo 1 termo exista
     *
     * @param int $value
     */
    private function queryShouldHave(int $value)
    {
        $this->filter['minimum_should_match'] = $value;
    }

    /**
     * Obtém através de um ID
     *
     * @param string $id
     * @return array|null
     */
    private function getResultById(string $id)
    {
        try {
            if ($data = $this->elasticsearch()->get($this->getBase(["id" => $id])))
                return array_merge(["id" => $data['_id']], $data['_source']);

            $json = new Json("store/" . parent::getType());
            $data = $json->get($id);
            if (!empty($data)) {
                $store = new Store(parent::getIndex());
                $store->add($id, $data);
            }

            return $data;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Executa a query DSL e atribui o result
     *
     */
    private function query()
    {
        try {
            if (!empty($this->fiflter['more_like_this']))
                $filter = $this->filter;
            else
                $filter = (empty($this->filter) ? ["match_all" => new \stdClass()] : ["bool" => $this->filter]);

            $this->result = $this->elasticsearch()->search($this->getBody($filter));
        } catch (\Exception $e) {
        }
    }
}