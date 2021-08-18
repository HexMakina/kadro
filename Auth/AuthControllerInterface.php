<?php

namespace HexMakina\kadro\Auth;

interface AuthControllerInterface
{
    public function requires_operator(): bool;
    public function authorize($permission = null): bool;
}
