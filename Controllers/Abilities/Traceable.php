<?php

namespace HexMakina\kadro\Controllers\Abilities;

use HexMakina\Interfaces\Database\TracerInterface;
use HexMakina\Tracer\Trace;
use HexMakina\Interfaces\ORM\ModelInterface;
use HexMakina\Interfaces\Auth\OperatorInterface;
use Psr\Container\ContainerInterface;

trait Traceable
{

    abstract public function formModel(): ModelInterface;
    abstract public function load_model(): ?ModelInterface;
    abstract public function operator(): OperatorInterface;


    public function getTracer(): TracerInterface
    {
        return $this->get('TracerInterface');
    }

    public function TraceableTraitor_after_save()
    {
        $trace = new Trace();
        $trace->tableName(get_class($this->formModel())::table_name());

        if (is_null($this->load_model())) {
            $trace->isInsert(true);
        } else {
            $trace->isUpdate(true);
        }
        $trace->tablePk($this->formModel()->getId());
        $trace->operatorId($this->operator()->operatorId());

        $this->getTracer()->trace($trace);
    }

    public function TraceableTraitor_after_destroy()
    {
        ;
    }
  // abstract public static function table_name();


  // public function trace(TracerInterface $tracer, ModelInterface $model, OperatorInterface $op)
  // {
  //   $tracer->trace($model->last_alter_query, $op->operatorId(), $model->getId());
  //
  // }

  // public function traces($options = []) : array
  // {
  //   $options['query_table'] = get_class($this)::table_name();
  //   $options['query_id'] = $this->get_model_id();
  //   return $this->get_tracer()->traces($options);
  //
  //   // $q = $this->get_tracer()->tracing_table()->select();
  //   // $q->aw_fields_eq(['query_table' => get_class($this)::table_name(), 'query_id' => $this->getId()]);
  //   // $q->order_by(['query_on', 'DESC']);
  //   // $q->run();
  //   // $res = $q->ret_ass();
  //   //
  //   // return $res;
  // }

  // don't really know the purpose of this anymore.. came from Tracer
  // public function traces_by_model(ModelInterface $m)
  // {
  //     return $this->get_tracer()->traces(['id' => $m->getId(), 'table' => get_class($m)::table_name()]);
  // }
}
