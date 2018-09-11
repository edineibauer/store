<?php

/**
 * Classe permite realizar operações CRUD no ElasticSearch
 * Utilizando como base, diretórios para o index de conteúdo
 */

class StoreFolder
{
    private $file;
    private $json;
    private $elastic;

    /**
     * StoreFolder constructor.
     */
    public function __construct()
    {
        $this->file = "directoryList";
        $this->json = new Json("store/folderContent/");
        $this->elastic = new Elasticsearch("folderContent");
    }

    public function reIndexFolders() {
        foreach ($this->json->get($this->file) as $folder)
            $this->addFolderToElastic($folder);
    }

    /**
     * Adiciona os conteúdos da Pasta ao Store
     * @param string $folder
     */
    public function addFolder(string $folder)
    {
        $rep = $this->json->get($this->file);
        if(!in_array($folder, $rep))
            $rep[] = $folder;

        $this->json->save($this->file, $rep);

        $this->addFolderToElastic($folder);
    }

    /**
     * Remove os conteúdos da Pasta do Store
     * @param string $folder
     */
    public function removeFolder(string $folder)
    {
        $rep = $this->json->get($this->file);
        unset($rep[$folder]);
        $this->json->update($this->file, $rep);
    }

    /**
     * Adiciona os conteúdos da Pasta ao ElasticCrud
     * @param string $folder
     */
    private function addFolderToElastic(string $folder)
    {
        if(preg_match("/^" . preg_quote(PATH_HOME) . "/i", $folder)) {
            $c = explode('/', str_replace(PATH_HOME, '',$folder));
            if(count($c) > 1) {
                $elastic = new ElasticSearch($c[count($c)-2]);
            foreach ($this->listFolder($folder) as $file) {
                if (preg_match('/\.json$/', $file))
                    $elastic->add(str_replace('.json', '', $file), json_decode(file_get_contents($file), true));
            }
            }
        }
    }

    /**
     * <b>listFolder:</b> Lista os arquivos e pastas de uma pasta.
     * @param string $dir = nome do diretório a ser varrido
     * @param int $limit = nome do diretório a ser varrido
     * @return array $directory = lista com cada arquivo e pasta no diretório
     */
    private function listFolder(string $dir, int $limit = 5000): array
    {
        $directory = [];
        if (file_exists($dir)) {
            $i = 0;
            foreach (scandir($dir) as $b):
                if ($b !== "." && $b !== ".." && $i < $limit):
                    $directory[] = $b;
                    $i++;
                endif;
            endforeach;
        }

        return $directory;
    }
}