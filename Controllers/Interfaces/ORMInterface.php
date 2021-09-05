<?php

namespace HexMakina\kadro\Controllers\Interfaces;

use HexMakina\TightORM\Interfaces\ModelInterface;

interface ORMInterface
{
    // ORM class tranlator
    public function modelClassName(): string;
    public function table_name(): string;

    public function load_model(): ?ModelInterface;
    public function formModel(): ModelInterface;

    public function dashboard();

    // creation & edition form (GET)
    public function edit();

    // creation & edition persistence (POST)
    public function save();

    // deletion confirmation form (GET)
    public function destroy_confirm();
    // deletion persistence (POST)
    public function destroy();

    // listings (GET)
    public function listing($model = null, $filters = [], $options = []);


    public function export();
    public function copy();

    public function errors(): array;
    public function add_errors($errors);
}
