<?php

namespace HexMakina\kadro\Controllers\Interfaces;

interface CRUDControllerInterface
{
    public function dashboard();

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

    public function errors(): array;
}
