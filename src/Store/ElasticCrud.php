<?php

class ElasticCrud extends ElasticCore
{
    /**
     * @param string $id
     * @return array|null
     */
    public function get(string $id)
    {
        try {
            $data = $this->elasticsearch()->get($this->getBase(["id" => $id]));
            return array_merge(["id" => $data['_id']], $data['_source']);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Cria ou Atualiza Registros no ElasticSearch
     *
     * @param string $id
     * @param array $data
     * @return string
     */
    public function save(string $id, $data = []): string
    {
        if ($dados = $this->get($id))
            $data = $this->arrayMerge($dados, $data);

        try {
            $response = $this->elasticsearch()->index($this->getBase(["id" => $id, "body" => $data]));
            return $response['result'];

        } catch (Exception $e) {
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
    public function update(string $id, array $data): string
    {
        if ($dados = $this->get($id)) {
            try {
                $response = $this->elasticsearch()->index($this->getBase(["id" => $id, "body" => $this->arrayMerge($dados, $data)]));
                return $response['result'];
            } catch (Exception $e) {
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
    public function add(string $id, $data = []): string
    {
        if (!$this->get($id)) {
            try {
                $response = $this->elasticsearch()->index($this->getBase(["id" => $id, "body" => $data]));
                return $response['result'];
            } catch (Exception $e) {
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
    public function delete(string $id)
    {
        try {
            $this->elasticsearch()->delete($this->getBase(["id" => $id]));
        } catch (Exception $e) {
        }
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