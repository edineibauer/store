<?php

namespace Store;

class Indice extends ElasticConnection
{

    /**
     * @param string $id
     * @return int
     */
    public function delete(string $id)
    {
        $el = new ElasticSearch($id);

        if ($el->getCount() === 0)
            parent::elasticsearch()->indices()->delete(['index' => $id]);
        else
            return 2;

        return 1;
    }
}