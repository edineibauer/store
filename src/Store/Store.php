<?php

class Store
{
    private $type;
    private $json;
    private $elastic;

    /**
     * Store constructor.
     * @param string $type
     * @param string|null $index
     */
    public function __construct(string $type, string $index = null)
    {
        $this->type = $type;
        $this->elastic = new ElasticCrud($type);

        $this->json = new Json($index ?? "store" . "/" . $type);

        if(!file_exists(PATH_HOME . "_cdn/.htaccess"))
            $this->createDeny();
    }

    /**
     * @param string $id
     * @return array
     */
    public function get(string $id = null)
    {
        $data = $this->elastic->get($id);
        if ($data)
            return $data;

        $data = $this->json->get($id);
        if (!empty($data))
            $this->elastic->add($id, $data);

        return $data;
    }

    /**
     * Cria ou Atualiza um Registro
     *
     * @param string $id
     * @param array $data
     * @return string
     */
    public function save(string $id, array $data): string
    {
        $this->json->save($id, $data);
        return $this->elastic->save($id, $data);
    }

    /**
     * Cria Registro
     *
     * @param string $id
     * @param array|null $data
     * @return string
     */
    public function add(string $id, array $data = null): string
    {
        $this->json->add($id, $data);
        return $this->elastic->add($id, $data);
    }

    /**
     * @param string $id
     * @param array|null $data
     * @return string
     */
    public function update(string $id, array $data = null): string
    {
        $this->json->update($id, $data);
        return $this->elastic->update($id, $data);
    }

    /**
     * @param string $id
     */
    public function delete(string $id)
    {
        $this->json->delete($id);
        $this->elastic->delete($id);
    }

    private function createDeny()
    {
        $f = fopen(PATH_HOME . "_cdn/.htaccess", "w+");
        fwrite($f, "Deny from all");
        fclose($f);
    }
}