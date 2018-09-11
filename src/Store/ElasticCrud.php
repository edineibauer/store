<?php

namespace Store;

use Helper\Convert;
use \Helper\Helper;

abstract class ElasticCrud extends ElasticCore
{
    /**
     * @param string $id
     * @return array
     */
    protected function getElastic(string $id): array
    {
        try {
            $data = $this->elasticsearch()->get($this->getBase(["id" => Convert::name($id)]));
            if ($data)
                return array_merge(["id" => $data['_id']], $data['_source']);

            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Cria ou Atualiza Registros no ElasticSearch
     *
     * @param string $id
     * @param array $data
     * @return string
     */
    protected function save(string $id, array $data = []): string
    {
        if ($dados = $this->getElastic($id)) {
            unset($data['created']);
            $data = Helper::arrayMerge($dados, $data);
        } else {
            $data['created'] = strtotime("now");
        }

        try {
            $data['updated'] = strtotime("now");
            $response = $this->elasticsearch()->index($this->getBase(["id" => Convert::name($id), "body" => $data]));
            return $response['result'];

        } catch (\Exception $e) {
            return "Erro {$e}";
        }
    }

    /**
     * Atualiza Registro no ElasticSearch
     *
     * @param string $id
     * @param array $data
     * @return string
     */
    protected function update(string $id, array $data): string
    {
        if ($dados = $this->getElastic($id)) {
            try {
                unset($data['created']);
                $data['updated'] = strtotime("now");
                $response = $this->elasticsearch()->index($this->getBase(["id" => Convert::name($id), "body" => Helper::arrayMerge($dados, $data)]));
                return $response['result'];
            } catch (\Exception $e) {
                return "Erro {$e}";
            }
        } else {
            return "not exist";
        }
    }

    /**
     * Adiciona Registro no ElasticSearch
     *
     * @param string $id
     * @param array $data
     * @return string
     */
    protected function add(string $id, array $data = []): string
    {
        if (!$this->getElastic($id)) {
            try {
                $data['created'] = strtotime("now");
                $data['updated'] = strtotime("now");
                $response = $this->elasticsearch()->index($this->getBase(["id" => Convert::name($id), "body" => $data]));
                return $response['result'];
            } catch (\Exception $e) {
                return "Erro {$e}";
            }
        } else {
            return "exist";
        }
    }

    /**
     * Deleta Registro no ElasticSearch
     *
     * @param string $id
     */
    protected function delete(string $id)
    {
        try {
            $this->elasticsearch()->delete($this->getBase(["id" => Convert::name($id)]));
        } catch (\Exception $e) {
        }
    }
}