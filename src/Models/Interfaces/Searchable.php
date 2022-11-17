<?php

namespace HexMakina\kadro\Models\Interfaces;

// implementation in Models\Abilities\Event
interface Searchable
{
    /**
     * @return mixed[]
     */
    public function searchableFields(): array;
}
