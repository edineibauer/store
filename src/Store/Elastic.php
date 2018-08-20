<?php

use Elasticsearch\ClientBuilder;

abstract class Elastic
{

    /**
     * @param string $index
     * @param string $id
     * @return null
     */
    protected function getElastic(string $index, string $id)
    {
        $params = [
            'index' => $index,
            'type' => $index,
            'id' => $id
        ];

        try {
            return $this->elasticsearch()->get($params)['_source'];
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @param string $index
     * @param string $id
     * @param array $data
     * @return string
     */
    protected function updateElastic(string $index, string $id, array $data): string
    {
        if($dados = $this->getElastic($index, $id))
            return $this->addElastic($index, $id, $this->arrayMerge($dados, $data), true);
    }

    /**
     * Adiciona log no ElasticSearch
     *
     * @param string $index
     * @param string $id
     * @param null $data
     * @param bool $forceUpdate
     * @return string
     */
    protected function addElastic(string $index, string $id, $data = null, bool $forceUpdate = false): string
    {
        $idFilter = strip_tags(trim($this->name(str_replace('.json', '', $id))));

        if ($forceUpdate || !$this->getElastic($index, $id)) {
            if (!$data && preg_match('/\.json$/i', $id)) {
                $data = $this->getJson(file_get_contents($id));
                $idFilter = pathinfo($id, PATHINFO_FILENAME);

            } elseif (!is_array($data)) {
                if (is_string($data) && file_exists($data) && preg_match('/\.json$/i', $data))
                    $data = $this->getJson(file_get_contents($data));
                else
                    $data = $this->getJson($data);
            }

            $index = strip_tags(trim($this->name($index)));

            try {
                $response = $this->elasticsearch()->index([
                    'index' => $index,
                    'type' => $index,
                    'id' => $idFilter,
                    'body' => $data,
                ]);
                return $response['result'];

            } catch (Exception $e) {
                return "";
            }
        } else {
            return "exist";
        }
    }

    /**
     * @param string $index
     * @param string $id
     */
    protected function deleteElastic(string $index, string $id)
    {
        $params = [
            'index' => $index,
            'type' => $index,
            'id' => $id
        ];

        try {
            $this->elasticsearch()->delete($params);
        } catch (Exception $e) {
        }
    }

    /**
     * Verifica se dado é arquivo json, se for, retorna
     * @param $string
     * @return mixed
     */
    private function getJson($string)
    {
        if (is_string($string)) {
            $j = json_decode($string, true);
            return (json_last_error() == JSON_ERROR_NONE ? $j : null);
        } else {
            return null;
        }
    }

    /**
     * @return \Elasticsearch\Client
     */
    private function elasticsearch()
    {
        $hosts = ['http://user:pass@localhost:9200'];

        return ClientBuilder::create()
            ->setHosts($hosts)
            ->build();
    }

    /**
     * <b>Tranforma URL:</b> Tranforma uma string no formato de URL amigável e retorna o a string convertida!
     * @param STRING $Name = Uma string qualquer
     * @return STRING
     */
    private function name($Name)
    {
        $f = array();
        $f['a'] = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜüÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿRr|"!@#$%&*()_-+={[}]/?;:.,\\\'<>°ºª¹²³£¢¬™®★’`§☆●•…”“’‘♥♡■◎≈◉';
        $f['b'] = "aaaaaaaceeeeiiiidnoooooouuuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr                                                            ";

        $data = strtr(utf8_decode($Name), utf8_decode($f['a']), $f['b']);
        $data = strip_tags(trim($data));
        $data = str_replace(' ', '-', $data);
        $data = str_replace(array('-----', '----', '---', '--'), '-', $data);

        return str_replace('?', '-', utf8_decode(strtolower(utf8_encode($data))));
    }

    /**
     * @param array $array1
     * @param array $array2
     * @return array
     */
    private function arrayMerge(array &$array1, array &$array2): array
    {
        $merged = $array1;
        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key]))
                $merged[$key] = $this->arrayMerge($merged[$key], $value);
            else
                $merged[$key] = $value;
        }
        return $merged;
    }
}