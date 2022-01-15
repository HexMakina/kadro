<?php

namespace HexMakina\kadro\Models;

use HexMakina\BlackBox\Database\SelectInterface;
use HexMakina\TightORM\TightModel;

class Traduko extends TightModel
{
    public const TABLE_NAME = 'kadro_traduki';
    public const TABLE_ALIAS = 'traduko';

    public function traceable(): bool
    {
        return false;
    }

    public function immortal(): bool
    {
        return false;
    }

    public static function query_retrieve($filters = [], $options = []): SelectInterface
    {
        $Query = static::table()->select();
        $Query->orderBy(['kategorio', 'ASC']);
        $Query->orderBy(['sekcio', 'ASC']);
        $Query->orderBy(['referenco', 'ASC']);

        return $Query;
    }
}
