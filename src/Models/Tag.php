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

    public function slug(): string
    {
        return $this->get('slug');
    }

    public function label(): string
    {
        return $this->get('label');
    }

    public function content(): string
    {
        return $this->get('content') ?? '';
    }

    public static function queryListing($filters = [], $options = []): SelectInterface{
        return self::query_retrieve($filters, $options);
    }

    public static function query_retrieve($filters = [], $options = []): SelectInterface
    {
        //---- JOIN & FILTER SERVICE
        $query = parent::query_retrieve($filters, $options);

        if (isset($filters['parent'])) {
            $query->join(['tag', 'parent'], [['parent', 'id', 'tag', 'parent_id']]);
            $column_name = is_numeric($filters['parent']) ? 'id' : 'slug';
            $query->whereEQ($column_name, $filters['parent'], 'parent');
        }

        $query->orderBy('label');
        return $query;
    }
}
