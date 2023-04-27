<?php

namespace HexMakina\kadro\Models;

use HexMakina\BlackBox\Database\SelectInterface;
use HexMakina\TightORM\TightModel;

class Tag extends TightModel
{
    public function __toString()
    {
        return $this->get('label');
    }

    public static function query_retrieve($filters = [], $options = []): SelectInterface
    {
        //---- JOIN & FILTER SERVICE
        $Query = parent::query_retrieve($filters, $options);
        if(isset($filters['parent']))
        {
           if(is_numeric($filters['parent']))
           {
                $Query->whereEQ('id', $filters['parent'], 'parent');
           }
           else 
           {
                $Query->whereEQ('reference', $filters['parent'], 'parent');
           }
        }

        $Query->orderBy('label');
        return $Query;
    }
}
