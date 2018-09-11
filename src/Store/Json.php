<?php

class Json
{
    private $folder;
    private $file;

    /**
     * Json constructor.
     * @param string|null $folder
     */
    public function __construct(string $folder = null)
    {
        $this->folder = $folder ?? "store";
    }

    /**
     * @param string $folder
     */
    public function setFolder(string $folder)
    {
        $this->folder = $folder;
    }

    /**
     * @param string $file
     * @return array
     */
    public function get(string $file): array
    {
        $id = pathinfo($file, PATHINFO_FILENAME);
        $this->setFile($file);
        if (file_exists($this->file))
            return array_merge(["id" => $id], json_decode(file_get_contents($this->file), true));

        return [];
    }

    /**
     * Cria ou Atualiza arquivo
     *
     * @param string $id
     * @param array $data
     */
    public function save(string $id, array $data)
    {
        $this->setFile($id);
        if ($this->file) {
            if (file_exists($this->file)) {
                // update
                $this->update($id, $data);
            } else {
                // add
                $this->add($id, $data);
            }
        }
    }

    /**
     * Adiciona arquivo Json
     *
     * @param string $id
     * @param array $data
     * @return bool
     */
    public function add(string $id, array $data): bool
    {
        try {
            $this->setFile($id);
            if ($this->file) {
                $this->checkFolder();
                $f = fopen($this->file, "w+");
                fwrite($f, json_encode($data));
                fclose($f);

                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Atualiza arquivo Json
     *
     * @param string $id
     * @param array $data
     * @return bool
     */
    public function update(string $id, array $data): bool
    {
        $this->setFile($id);
        if ($this->file && file_exists($this->file)) {
            return $this->add($id, $this->arrayMerge($this->get($id), $data));
        } else {
            return false;
        }
    }

    /**
     * Deleta um arquivo json
     *
     * @param string $id
     */
    public function delete(string $id)
    {
        $this->setFile($id);
        if (file_exists($this->file))
            unlink($this->file);
    }

    /**
     * Seta o caminho do arquivo Json a ser trabalhado
     *
     * @param mixed $file
     */
    private function setFile(string $file)
    {
        if (!$this->file) {
            $this->file = (preg_match("/^" . preg_quote(PATH_HOME, '/') . "/i", $file) ? $file : PATH_HOME . "_cdn/{$this->folder}/{$file}");

            // Verifica se é final .json
            if (!preg_match("/\.json$/i", $file))
                $this->file .= ".json";
        }
    }

    /**
     * Cria diretório caminho do arquivo caso não exista
     */
    private function checkFolder()
    {
        $dir = PATH_HOME;
        $folders = explode('/', str_replace(PATH_HOME, '', $this->file));
        $max = count($folders) -1;
        foreach ($folders as $i => $folder) {
            $dir .= $folder . "/";
            if($i < $max) {
                if (!file_exists($dir) && !is_dir($dir))
                    mkdir($dir, 0777);
            }
        }
    }

    /**
     * @param array $array1
     * @param array $array2
     * @return array
     */
    private function arrayMerge(array $merged, array &$array2): array
    {
        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key]))
                $merged[$key] = $this->arrayMerge($merged[$key], $value);
            else
                $merged[$key] = $value;
        }
        return $merged;
    }
}