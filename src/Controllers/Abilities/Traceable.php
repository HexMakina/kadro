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
        $trace->tableName(get_class($this->formModel())::relationalMappingName());

        if (is_null($this->loadModel())) {
            $trace->isInsert(true);
        } else {
            $trace->isUpdate(true);
        }

        $trace->tablePk($this->formModel()->getId());
        $trace->operatorId($this->operator()->getId());

        $this->getTracer()->trace($trace);
    }

    public function TraceableTraitor_after_destroy(): void
    {
        ;
    }



  // public function trace(TracerInterface $tracer, ModelInterface $model, OperatorInterface $op)
  // {
  //   $tracer->trace($model->last_alter_query, $op->getId(), $model->getId());
  //
  // }

  // public function traces($options = []) : array
  // {
  //   $options['query_table'] = get_class($this)::relationalMappingName();
  //   $options['query_id'] = $this->get_model_id();
  //   return $this->get_tracer()->traces($options);
  //
  //   // $q = $this->get_tracer()->tracing_table()->select();
  //   // $q->whereFieldsEQ(['query_table' => get_class($this)::relationalMappingName(), 'query_id' => $this->getId()]);
  //   // $q->orderBy(['query_on', 'DESC']);
  //   // $q->run();
  //   // $res = $q->retAss();
  //   //
  //   // return $res;
  // }

  // don't really know the purpose of this anymore.. came from Tracer
  // public function traces_by_model(ModelInterface $m)
  // {
  //     return $this->get_tracer()->traces(['id' => $m->getId(), 'table' => get_class($m)::relationalMappingName()]);
  // }
}
