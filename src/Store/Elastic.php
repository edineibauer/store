<?php

use Elasticsearch\ClientBuilder;

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

        $index = strip_tags(trim($this->name($index)));
        $id = strip_tags(trim($this->name(str_replace('.json', '', $id))));

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
}