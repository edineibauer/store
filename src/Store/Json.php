<?php

class Json
{
    private $file;
    private $fileName;

    /**
     * Json constructor.
     * @param string $fileFolder
     */
    public function __construct(string $fileFolder)
    {
        $this->file = [];
        if ($fileFolder)
            $this->setFile($fileFolder);
    }

    /**
     * @return array
     */
    public function get(): array
    {
        return $this->file;
    }

    /**
     * adiciona um valor ao array
     * @param $content
     */
    public function add($content)
    {
        if ($this->fileName && !in_array($content, $this->file))
            $this->file[] = $content;
    }

    /**
     * Remove um valor do array
     * @param $content
     */
    public function remove($content)
    {
        if ($this->fileName && in_array($content, $this->file))
            $this->file = array_diff($this->file, [$content]);
    }

    /**
     * Salva o json atual
     */
    public function save()
    {
        if ($this->fileName) {
            $f = fopen($this->fileName, "w");
            fwrite($f, json_encode($this->file));
            fclose($f);
        }
    }

    /**
     * @param mixed $file
     */
    private function setFile(string $file)
    {
        $this->fileName = $file;
        if (file_exists($file))
            $this->file = json_decode(file_get_contents($file), true);
        else
            $this->checkFolder($file);
    }

    /**
     * @param string $file
     */
    private function checkFolder(string $file)
    {
        $dir = "";
        $folders = explode('/', str_replace([PATH_HOME, HOME], '', $file));
        foreach ($folders as $i => $folder) {
            if ($i < count($folders) - 1) {
                $dir .= $folder . "/";
                $this->createFolderIfNoExist(PATH_HOME . $dir);
            }
        }
    }

    /**
     * @param string $folder
     */
    private function createFolderIfNoExist(string $folder)
    {
        if (!file_exists($folder) && !is_dir($folder))
            mkdir($folder, 0777);
    }
}