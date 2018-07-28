<?php

/**
 * Classe permite realizar operações CRUD no ElasticSearch
 * Utilizando como base, diretórios para o index de conteúdo
 */

use Core\Check;
use Core\Helper;

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

        foreach (Helper::listFolder($folder) as $file) {
            if (preg_match('/\.json$/', $file))
                parent::addElastic($type, $folder.$file);
        }
    }
}