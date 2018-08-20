<?php

/**
 * Classe permite realizar operações CRUD no ElasticSearch
 * Utilizando como base, diretórios para o index de conteúdo
 */

class StoreFolder extends Elastic
{
    private $folder;

    /**
     * Store constructor.
     * @param string $index
     */
    public function __construct()
    {
        $this->folder = PATH_HOME . "_cdn/store/directoryList.json";
    }

    public function reIndexFolders() {
        $json = new Json($this->folder);
        foreach ($json->get() as $folder)
            $this->addFolderToElastic($folder);
    }

    /**
     * Adiciona os conteúdos da Pasta ao Store
     * @param string $folder
     */
    public function addFolder(string $folder)
    {
        $rep = new Json($this->folder);
        $rep->add($folder);
        $rep->save();

        $this->addFolderToElastic($folder);
    }

    /**
     * Remove os conteúdos da Pasta do Store
     * @param string $folder
     */
    public function removeFolder(string $folder)
    {
        $rep = new Json($this->folder);
        $rep->remove($folder);
        $rep->save();
    }

    /**
     * Adiciona os conteúdos da Pasta ao Elastic
     * @param string $folder
     */
    private function addFolderToElastic(string $folder)
    {
        if (!preg_match('/\/$/i', $folder))
            $folder .= "/";

        $folderNameExplode = explode('/', $folder);
        $type = $folderNameExplode[count($folderNameExplode) - 2];

        foreach ($this->listFolder($folder) as $file) {
            if (preg_match('/\.json$/', $file))
                parent::addElastic($type, $folder.$file);
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