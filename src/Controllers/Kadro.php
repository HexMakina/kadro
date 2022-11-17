<?php

namespace HexMakina\kadro\Controllers;

use HexMakina\kadro\Auth\AccessRefusedException;
use HexMakina\BlackBox\Auth\OperatorInterface;
use HexMakina\BlackBox\Controllers\AuthControllerInterface;
use HexMakina\BlackBox\Controllers\IntlControllerInterface;

class Kadro extends Display implements AuthControllerInterface, IntlControllerInterface
{
    private string $translation_function_name = 'L';

    /**
     * @var mixed|null
     */
    private $operator;

    public function __toString(): string
    {
        return get_called_class();
    }

    public function requiresOperator(): bool
    {
        return true; // security by default
    }

    public function operator(): OperatorInterface
    {
        $op_class = get_class($this->get('HexMakina\BlackBox\Auth\OperatorInterface'));
        if (is_null($this->operator) && !empty($op_id = $this->get('HexMakina\BlackBox\StateAgentInterface')->operatorId())) {
            $this->operator = $op_class::safeLoading($op_id);
        }

        return $this->operator ?? new $op_class;
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

        if (is_null($permission)) {
            return true;
        }

        if ($operator->hasPermission($permission)) {
            return true;
        }

        throw new AccessRefusedException();
    }

    public function execute($method): bool
    {
      // kadro controller is a display controller with authentification and intl
        $this->authorize();
        return parent::execute($method);
    }

    public function prepare(): void
    {
        parent::prepare();
        $this->trim_request_data();
    }

    // intl function, calls to lezer
    public function l($message, $context = []): string
    {
        return call_user_func($this->translation_function_name, $message, $context);
    }

    private function trim_request_data(): void
    {
        array_walk_recursive($_GET, static function (&$value) : void {
            $value = trim($value);
        });
        array_walk_recursive($_REQUEST, static function (&$value) : void {
            $value = trim($value);
        });

        if ($this->router()->submits()) {
            array_walk_recursive($_POST, static function (&$value) : void {
                $value = trim($value);
            });
        }
    }
}
