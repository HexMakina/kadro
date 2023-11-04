<?php

namespace HexMakina\kadro\Controllers;

use HexMakina\kadro\Auth\{Operator};
use HexMakina\BlackBox\Auth\OperatorInterface;
use Throwable;

class Reception extends Kadro
{
    public function requiresOperator(): bool
    {
        return false;
    }


    // throws RouterException if no match
    // throws Exception if no controller found
    public function welcome(OperatorInterface $operator): void
    {
        $this->router()->match(); 

        // do we need to identify the operator ?
        if ($this->router()->name() === 'identify') {
            $this->identify($operator);
        }

        $target_controller = $this->instantiateTargetController();


        // Check if the target controller requires authentication
        if ($target_controller instanceof \HexMakina\BlackBox\Controllers\AuthControllerInterface) {
            // If authentication is required, check if an operator is logged in
            if ($target_controller->requiresOperator()) {

                // Get the operator ID from the state agent
                $operator_id = $this->get('HexMakina\BlackBox\StateAgentInterface')->operatorId();

                // If no operator is logged in, redirect to the checkin page
                if (empty($operator_id)) {
                    $this->checkin();
                    die;
                } else {
                    // If an operator is logged in, check if the operator is active
                    $operator = get_class($operator)::exists($operator_id);
                    if (is_null($operator) || !$operator->isActive()) {
                        // If the operator is not active, log them out and redirect to the checkin page
                        $this->checkout();
                    }
                }
            }
        }

        if ($target_controller instanceof \HexMakina\BlackBox\Controllers\BaseControllerInterface) {
            $target_controller->execute($this->router()->targetMethod());
        }
        else{
            throw new \Exception(sprintf('Unable to run %s::%s, not a Base controller', $target_controller, $this->router()->targetMethod()));
        }
    }

    // GET
    public function checkin(): void
    {
        $res = $this->display(__FUNCTION__);
        $this->get('HexMakina\BlackBox\StateAgentInterface')->resetMessages();
        die($res);
    }

    // GET
    public function checkout(): void
    {
        $this->get('HexMakina\BlackBox\StateAgentInterface')->destroy();
        $this->router()->hop('checkin');
    }

    // POST
    public function identify($op): void
    {
        try {
            $username = $this->router()->submitted('username');
            $password = $this->router()->submitted('password');
            $operator = get_class($op)::exists('username', $username);
            
            if (is_null($operator)) {
                throw new \Exception('OPERATOR_DOES_NOT_EXIST');
            }

            if (!$operator->isActive()) {
                throw new \Exception('OPERATOR_IS_DISABLED');
            }

            if (!$operator->passwordVerify($password)) {
                throw new \Exception('WRONG_LOGIN_OR_PASSWORD');
            }

            $this->get('HexMakina\BlackBox\StateAgentInterface')->operatorId($operator->id());
            $this->logger()->notice('PAGE_CHECKIN_WELCOME', [$operator->name()]);

            if($operator->get('route') && $this->router()->routeExists($operator->get('route')))
                $this->router()->hop($operator->get('route'));
            else 
                $this->router()->hop();

        } catch (\Exception $exception) {

            $this->logger()->warning('KADRO_operator_' . $exception->getMessage());
            $this->router()->hop('checkin');
        }
    }


    private function instantiateTargetController(){
        $try = ['Controllers\\' . $this->router()->targetController()];

        if($this->router()->params('nid')) // Generic routes
            $try[]= 'Controllers\\' . $this->router()->targetController() . $this->router()->params('nid');

        $target_controller = null;
        foreach ($try as $target_controller) {
            try {
                // MVC Cascade
                $target_controller = $this->get($target_controller);
                break;
            } 
            catch (Throwable $t) {
                // faster than calling ::has() then ::get()
            }
        }
        
        if(!class_exists($target_controller)){
            throw new \Exception(sprintf('Unable to run %s::%s, class does not exist', $target_controller, $this->router()->targetMethod()));
        }

        return $target_controller;
    }

}
