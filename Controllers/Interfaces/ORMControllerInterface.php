<?php

namespace HexMakina\kadro\Controllers\Interfaces;

interface ORMControllerInterface extends CRUDControllerInterface
{
    // ORM class tranlator
    public function class_name(): string

    // creation & edition form (GET)
    public function edit();
    // creation & edition persistence (POST)
    public function save();
    // listings (GET)
    public function listing();

    // deletion confirmation form (GET)
    public function destroy_confirm();
    // deletion persistence (POST)
    public function destroy();

    public function dashboard();
    public function export();
    public function copy();

    public function add_errors($errors);
}
