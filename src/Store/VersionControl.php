<?php

namespace Store;

use Helper\Helper;

abstract class VersionControl
{
    private $folder;
    private $backup;

    /**
     * VersionControl constructor.
     * @param string $folder
     */
    public function __construct(string $folder)
    {
        $this->folder = $folder;
        $this->backup = defined("BACKUP") ? BACKUP : 2;
    }

    /**
     * Cria uma Versão do arquivo
     *
     * @param string $file
     * @param array $data
     */
    protected function createVerion(string $file, array $data)
    {
        list($id, $folder) = $this->getBaseInfo($file);
        $idVersion = $this->getLastVersion(PATH_HOME . "_cdn/{$folder}/{$id}");

        $json = new Json($folder);
        $json->setVersionamento(false);
        $json->add($id . "#{$idVersion}", $data);
    }

    /**
     * Cria um comando de exclusão
     *
     * @param string $file
     */
    protected function deleteVerion(string $file)
    {
        list($id, $folder) = $this->getBaseInfo($file);

        //Deleta qualquer versão existente
        for ($i = $this->backup; $i > 0; $i--) {
            if(file_exists(PATH_HOME . "_cdn/{$folder}/version/{$id}#{$i}.json"))
                unlink(PATH_HOME . "_cdn/{$folder}/version/{$id}#{$i}.json");
        }

        $json = new Json($folder);
        $json->setVersionamento(false);
        $json->add($id . "#{$this->backup}");
    }

    /**
     * Retorna/Controla a versão mais atual
     *
     * @param string $url
     * @return int
     */
    private function getLastVersion(string $url): int
    {
        for ($idVersion = $this->backup; $idVersion > 0; $idVersion--) {
            if (!file_exists("{$url}#{$idVersion}.json")) {
                break;
            } elseif ($idVersion === 1) {

                //chegou ao limite e não encontrou vaga.
                //Rename files to remove last and free first
                for ($i = $this->backup; $i > 1; $i--)
                    rename("{$url}#" . ($i - 1) . ".json", "{$url}#{$i}.json");

                break;
            }
        }

        return $idVersion;
    }

    /**
     * Obtém os dados da url
     *
     * @param string $file
     * @return array
     */
    private function getBaseInfo(string $file)
    {
        $id = pathinfo($file, PATHINFO_FILENAME);
        $dir = pathinfo($file, PATHINFO_DIRNAME);
        $folder = str_replace(PATH_HOME . '_cdn/', '', $dir) . "/version";
        Helper::createFolderIfNoExist($dir . '/version');

        return [$id, $folder];
    }

}