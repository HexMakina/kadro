<?php

namespace HexMakina\kadro\Models;

use HexMakina\BlackBox\Database\SelectInterface;
use HexMakina\TightORM\TightModel;

class Traduko extends TightModel
{
    /**
     * @var string
     */
    public const TABLE_NAME = 'kadro_traduki';

    /**
     * @var string
     */
    public const TABLE_ALIAS = 'traduko';

    public function traceable(): bool
    {
        return false;
    }

    public function immortal(): bool
    {
        return false;
    }

    public static function filter($filters = [], $options = []): SelectInterface
    {
        $select = static::table()->select();
        $select->orderBy(['kategorio', 'ASC']);
        $select->orderBy(['sekcio', 'ASC']);
        $select->orderBy(['referenco', 'ASC']);

        return $select;
    }
}
