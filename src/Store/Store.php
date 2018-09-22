<?php

namespace Store;

class Store extends ElasticCrud
{
    private $type;
    private $json;

    /**
     * Store constructor.
     *
     * @param string $type
     * @param bool $versionamento -> Se ativo, grava versões dos arquivos jsons
     * @param bool $storeJson -> Se ativo, armazena os dados em json, senão, somente no ElasticSearch
     * @param string|null $index
     */
    public function __construct(string $type, bool $versionamento = true, bool $storeJson = true, string $index = null)
    {
        parent::__construct($type);
        $this->type = $type;
        if ($storeJson) {
            $this->json = new Json($index ?? "store" . "/" . $type);
            $this->json->setVersionamento($versionamento);
            if (!file_exists(PATH_HOME . "_cdn/.htaccess"))
                $this->createDeny();
        }
    }

    /**
     * @param string $id
     * @return array
     */
    public function get(string $id = null): array
    {
        if ($data = parent::getElastic($id))
            return $data;

        if ($this->json)
            $data = $this->json->get($id);

        if ($data && !preg_match('/#/i', $id))
            parent::add($id, $data);

        return $data;
    }

    /**
     * @param string $id
     * @param array|null $data
     * @return string
     */
    public function update(string $id, array $data = null): string
    {
        if ($this->json)
            $this->json->update($id, $data);
        return parent::update($id, $data);
    }

    /**
     * Cria Registro
     *
     * @param string|null $id
     * @param array|null $data
     * @return string
     */
    public function add($id = null, array $data = []): string
    {
        if(!$id)
            $id = md5(strtotime("now") . rand(0, 100000));

        if ($this->json)
            $this->json->add($id, $data);
        return parent::add($id, $data);
    }

    /**
     * Cria ou Atualiza um Registro
     *
     * @param string|null $id
     * @param array $data
     * @return string
     */
    public function save($id = null, array $data = []): string
    {
        if(!$id)
            $id = md5(strtotime("now") . rand(0, 100000));

        if ($this->json)
            $this->json->save($id, $data);
        return parent::save($id, $data);
    }

    /**
     * @param string $id
     */
    public function delete(string $id)
    {
        if ($this->json)
            $this->json->delete($id);
        parent::delete($id);
    }

    /**
     * Obtém os dados de uma versão anterior
     *
     * @param string $id
     * @param int $version
     * @return array
     */
    public function getVersion(string $id, int $version = 1): array
    {
        if ($this->json)
            return $this->json->getVersion($id, $version);

        return [];
    }

    /**
     * Atualiza os dados para uma versão anterior
     *
     * @param string $id
     * @param int $version
     */
    public function rollBack(string $id, int $version = 1)
    {
        if ($this->json && $dataBack = $this->json->getVersion($id, $version)) {
            $this->json->update($id, $dataBack, $version);
            parent::update($id, $dataBack);
        }
    }

    private function createDeny()
    {
        $f = fopen(PATH_HOME . "_cdn/.htaccess", "w+");
        fwrite($f, "Deny from all");
        fclose($f);
    }
}