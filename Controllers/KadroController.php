<?php

namespace HexMakina\kadro\Controllers;

use HexMakina\kadro\Auth\{AccessRefusedException, AuthControllerInterface, OperatorInterface};
use HexMakina\kadro\Controllers\Interfaces\IntlControllerInterface;

class KadroController extends DisplayController implements AuthControllerInterface, IntlControllerInterface
{
    private $translation_function_name = 'L';

    public function __toString()
    {
        return get_called_class();
    }

    public function requires_operator(): bool
    {
        return true; // security by default
    }

    public function operator(): OperatorInterface
    {
        return $this->get('OperatorInterface');
    }

    public function authorize($permission = null): bool
    {
      // if(!$this->requires_operator() || is_null($permission))
        if (!$this->requires_operator()) {
            return true;
        }

        $operator = $this->operator();
        if (is_null($operator) || $operator->is_new() || !$operator->is_active()) {
            throw new AccessRefusedException();
        }

        if (!is_null($permission) && !$operator->has_permission($permission)) {
            throw new AccessRefusedException();
        }

        return true;
    }

    public function execute()
    {
      // kadro controller is a display controller with authentification and intl
        $this->authorize();
        return parent::execute();
    }

    public function prepare()
    {
        parent::prepare();
        $this->trim_request_data();
    }

    // intl function, calls to lezer
    public function l($message, $context = []): string
    {
        return call_user_func($this->translation_function_name, $message, $context);
    }

    private function trim_request_data()
    {
        array_walk_recursive($_GET, function (&$value) {
            $value = trim($value);
        });
        array_walk_recursive($_REQUEST, function (&$value) {
            $value = trim($value);
        });

        if ($this->router()->submits()) {
            array_walk_recursive($_POST, function (&$value) {
                $value = trim($value);
            });
        }
    }
}
