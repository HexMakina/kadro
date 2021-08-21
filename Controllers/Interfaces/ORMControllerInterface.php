<?php

namespace HexMakina\kadro\Controllers\Interfaces;

interface ORMControllerInterface extends CRUDControllerInterface
{
    // ORM class tranlator
    public function class_name(): string;

    public function export();
    public function copy();

    public function add_errors($errors);
}
