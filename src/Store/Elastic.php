<?php

use Elasticsearch\ClientBuilder;
use Core\Check;

abstract class Elastic
{
    /**
     * Adiciona log no ElasticSearch
     *
     * @param string $index
     * @param string $id
     * @param mixed $data
     * @return string
     */
    protected function addElastic(string $index, string $id, $data = null): string
    {
        if(!$data && preg_match('/\.json$/i', $id)) {
            $data = $this->getJson(file_get_contents($id));
            $id = pathinfo($id, PATHINFO_FILENAME);

        } elseif(!is_array($data)){
            if(is_string($data) && file_exists($data) && preg_match('/\.json$/i', $data))
                $data = $this->getJson(file_get_contents($data));
            else
                $data = $this->getJson($data);
        }

        $index = strip_tags(trim(Check::name($index)));
        $id = strip_tags(trim(Check::name(str_replace('.json', '', $id))));

        try {
            $response = $this->elasticsearch()->index([
                'index' => $index,
                'type' => $index,
                'id' => $id,
                'body' => $data,
            ]);
            return $response['result'];

        } catch (Exception $e) {
            return "";
        }
    }

    protected function elasticsearch()
    {
        $hosts = ['http://user:pass@localhost:9200'];

        return ClientBuilder::create()
            ->setHosts($hosts)
            ->build();
    }

    /**
     * Verifica se dado é arquivo json, se for, retorna
     * @param $string
     * @return mixed
     */
    protected function getJson($string)
    {
        if(is_string($string)) {
            $j = json_decode($string, true);
            return (json_last_error() == JSON_ERROR_NONE ? $j : null);
        } else {
            return null;
        }
    }
}