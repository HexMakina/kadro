<?php

namespace HexMakina\kadro\Models\Interfaces;

// implementation in Models\Abilities\Event
interface Searchable
{
    public function searchableFields(): array;
}
