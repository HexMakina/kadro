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
        return $this->get('content') ?? $this->get('label');
    }

    // public static function filter($filters = [], $options = []): SelectInterface{
    //     return self::filter($filters, $options);
    // }

    public static function filter($filters = [], $options = []): SelectInterface
    {
        //---- JOIN & FILTER SERVICE
        $query = parent::filter($filters, $options);

        if (array_key_exists('parent', $filters) && $filters['parent'] === null) {
            $query->whereIsNull('parent_id');
        } else {
            $query->join(['tag', 'parent'], [['parent', 'id', 'tag', 'parent_id']], 'LEFT');
            $query->selectAlso(['parent_label' => ['parent', 'label']]);

            if (array_key_exists('parent', $filters)) {
                $column_name = is_numeric($filters['parent']) ? 'id' : 'slug';
                $query->whereEQ($column_name, $filters['parent'], 'parent');
            }
        }
        $query->orderBy('content ASC, label ASC');
        return $query;
    }
}
