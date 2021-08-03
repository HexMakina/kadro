<?php

namespace HexMakina\kadro\Controllers\Interfaces;

interface CRUDController
{
   public function dashboard();
   public function edit();
   public function save();
   public function listing();

   public function destroy();

   public function errors() : array;
}


