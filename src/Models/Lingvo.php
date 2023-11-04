<?php

namespace HexMakina\kadro\Models;

use HexMakina\BlackBox\Database\SelectInterface;
use HexMakina\TightORM\TightModel;

class Lingvo extends TightModel
{
    /**
     * @var string
     */
    public const TABLE_NAME = 'kartisto_ISO639';

    /**
     * @var string
     */
    public const ISO_3 = 'Part3';

    /**
     * @var string
     */
    public const ISO_2B = 'Part2B';

    /**
     * @var string
     */
    public const ISO_2T = 'Part2T';

    /**
     * @var string
     */
    public const ISO_1 = 'Part1';

    /**
     * @var string
     */
    public const ISO_NAME = 'Ref_Name';

    /**
     * @var string
     */
    public const ISO_SET = 'Set';

    /**
     * @var array<string, string>
     */
    public const ISO_SETS = [self::ISO_1 => 'ISO 639-1', self::ISO_3 => 'ISO 639-3', self::ISO_2B => 'ISO 639-2B', self::ISO_2T => 'ISO 639-2T'];

    /**
     * @var string
     */
    public const ISO_DEFAULT = self::ISO_3;

    /**
     * @var string
     */
    public const ISO_SCOPE = 'Scope';

    /**
     * @var array<string, string>
     */
    public const ISO_SCOPES = ['I' => 'Individual', 'M' => 'Macrolanguage', 'S' => 'Special'];

    /**
     * @var string
     */
    public const ISO_TYPE = 'Type';

    /**
     * @var array<string, string>
     */
    public const ISO_TYPES = ['A' => 'Ancient', 'C' => 'Constructed', 'E' => 'Extinct', 'H' => 'Historical', 'L' => 'Living', 'S' => 'Special'];


    public function __toString()
    {
        return $this->iso_name() . ' (' . $this->get(self::ISO_3) . ')';
    }

    public function traceable(): bool
    {
        return false;
    }

    public function iso_name()
    {
        return $this->get('Ref_Name');
    }

    public function iso_scope(): string
    {
        return self::ISO_SCOPES[$this->get('scope')];
    }

    public function iso_type(): string
    {
        return self::ISO_TYPES[$this->get('scope')];
    }


    public static function query_retrieve($filter = [], $options = []): SelectInterface
    {

        $searchable_fields = [self::ISO_NAME, self::ISO_3, self::ISO_2B, self::ISO_2T, self::ISO_1];

        $select = static::table()->select();

        if (isset($filter['name'])) {
        }

        if (isset($filter['code'])) {
        }

        if (isset($filter['term'])) {
            $select->whereFilterContent(['term' => $filter['term'], 'fields' => $searchable_fields], $select->tableLabel(), 'OR');
        }

        if (isset($filter['requires_authority']) && isset(self::ISO_SETS[$filter['requires_authority']])) {
            $select->whereNotEmpty($filter['requires_authority']);
        }

        if (isset($filter['types'])) {
            $wc = sprintf("AND " . self::ISO_TYPE . " IN ('%s') ", implode("', '", array_keys(self::ISO_TYPES)));
            $select->where($wc);
        }

        if (isset($filter['scopes'])) {
            $wc = sprintf("AND " . self::ISO_SCOPE . " IN ('%s') ", implode("', '", array_keys(self::ISO_SCOPES)));
            $select->where($wc);
        }

        $select->orderBy([self::ISO_1, 'DESC']);
        $select->orderBy([self::ISO_2T, 'DESC']);
        $select->orderBy([self::ISO_3, 'DESC']);
        $select->orderBy([self::ISO_NAME, 'DESC']);

        return $select;
    }

    /**
     * @return array<int|string, mixed>
     */
    public static function search_language($term, $authority = null): array
    {
        $rows = self::query_retrieve(['term' => $term, 'requires_authority' => $authority])->retAss();
        $ret = [];
        foreach ($rows as $row) {
            $ret[$row[self::ISO_3]] = $row[self::ISO_NAME];
        }

        return $ret;
    }

    public static function language_name($code)
    {
        $Query = self::table()->select([self::ISO_NAME]);
        $Query->whereEQ(self::ISO_3, $code);

        $rows = $Query->retCol();

        if (isset($rows[0])) { // only 1 result
            return current($rows);
        }

        return null; // no results
    }

    public static function ISO639_1_to_ISO639_3($code)
    {
        $Query = self::table()->select([self::ISO_3])->whereEQ(self::ISO_1, $code, self::TABLE_NAME)->limit(1);
        $row = static::retrieve($Query);
        $row = get_object_vars($row[0]);
        return $row[self::ISO_3];
    }
}
