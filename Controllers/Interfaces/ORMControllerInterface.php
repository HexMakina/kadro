<?php

namespace HexMakina\kadro\Controllers\Interfaces;

use HexMakina\TightORM\Interfaces\ModelInterface;

interface ORMControllerInterface extends CRUDControllerInterface
{
    // ORM class tranlator
    public function class_name(): string;
    public function table_name(): string;

    public function load_model(): ?ModelInterface;
    public function form_model(): ModelInterface;

    public function export();
    public function copy();

    public function add_errors($errors);
}
