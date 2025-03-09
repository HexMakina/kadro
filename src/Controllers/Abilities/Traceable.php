<?php

namespace HexMakina\kadro\Controllers\Abilities;

use HexMakina\BlackBox\Database\TracerInterface;
use HexMakina\Tracer\Trace;
use HexMakina\BlackBox\ORM\ModelInterface;
use HexMakina\BlackBox\Auth\OperatorInterface;
use Psr\Container\ContainerInterface;

trait Traceable
{

    abstract public function formModel(): ModelInterface;

    abstract public function loadModel(): ?ModelInterface;

    abstract public function operator(): OperatorInterface;


    public function getTracer(): TracerInterface
    {
        return $this->get('HexMakina\BlackBox\Database\TracerInterface');
    }

    public function TraceableTraitor_after_save(): void
    {
        $trace = new Trace();
        $trace->tableName(get_class($this->formModel())::table());

        if (is_null($this->loadModel())) {
            $trace->isInsert(true);
        } else {
            $trace->isUpdate(true);
        }

        $trace->tablePk($this->formModel()->pk());
        $trace->operatorId($this->operator()->id());

        $this->getTracer()->trace($trace);
    }

    public function TraceableTraitor_after_destroy(): void
    {
        ;
    }



}
