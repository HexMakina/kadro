<?php

namespace HexMakina\kadro\Controllers;

use HexMakina\kadro\Auth\AccessRefusedException;
use HexMakina\BlackBox\Auth\OperatorInterface;
use HexMakina\BlackBox\Controllers\AuthControllerInterface;
use HexMakina\BlackBox\Controllers\IntlControllerInterface;

class Kadro extends Display implements AuthControllerInterface, IntlControllerInterface
{
    private $translation_function_name = 'L';
    private $operator = null;

    public function __toString()
    {
        return get_called_class();
    }

    public function requiresOperator(): bool
    {
        return true; // security by default
    }

    public function operator(): OperatorInterface
    {
        if(is_null($this->operator)){
          $op_id = $this->get('HexMakina\BlackBox\StateAgentInterface')->operatorId();
          $op_class = get_class($this->get('HexMakina\BlackBox\Auth\OperatorInterface'));
          $op = $op_class::safeLoading($op_id);

          $this->operator = $op;
        }
        return $this->operator;
    }

    // returns true or throws AccessRefusedException
    public function authorize($permission = null): bool
    {
        if (!$this->requiresOperator()) {
            return true;
        }

        $operator = $this->operator();
        if (is_null($operator) || $operator->isNew() || !$operator->isActive()) {
            throw new AccessRefusedException();
        }

        if (!is_null($permission) && !$operator->hasPermission($permission)) {
            throw new AccessRefusedException();
        }

        return true;
    }

    public function execute($method)
    {
      // kadro controller is a display controller with authentification and intl
        $this->authorize();
        return parent::execute($method);
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
