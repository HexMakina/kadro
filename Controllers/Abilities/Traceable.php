<?php

namespace HexMakina\kadro\Controllers\Abilities;

use \HexMakina\Tracer\TracerInterface;
use \HexMakina\Tracer\Trace;
use \HexMakina\TightORM\Interfaces\ModelInterface;
use \HexMakina\kadro\Auth\OperatorInterface;
use Psr\Container\ContainerInterface;

trait Traceable
{
  // abstract public function container(): ContainerInterface;
  // abstract public function get_tracer() : TracerInterface;
  //
    abstract public function formModel(): ModelInterface;
    abstract public function load_model(): ?ModelInterface;
    abstract public function operator(): OperatorInterface;
    abstract public function container(): ContainerInterface;
    abstract public function table_name(): string;

    public function getTracer(): TracerInterface
    {
        return $this->container()->get('TracerInterface');
    }

    public function TraceableTraitor_after_save()
    {
        $trace = new Trace();
        $trace->tableName($this->table_name());

        if (is_null($this->load_model())) {
            $trace->isInsert(true);
        } else {
            $trace->isUpdate(true);
        }
        $trace->tablePk($this->formModel()->get_id());
        $trace->operatorId($this->operator()->operator_id());

        $this->getTracer()->trace($trace);
    }

    public function TraceableTraitor_after_destroy()
    {
        ;
    }
  // abstract public static function table_name();


  // public function trace(TracerInterface $tracer, ModelInterface $model, OperatorInterface $op)
  // {
  //   $tracer->trace($model->last_alter_query, $op->operator_id(), $model->get_id());
  //
  // }

  // public function traces($options = []) : array
  // {
  //   $options['query_table'] = get_class($this)::table_name();
  //   $options['query_id'] = $this->get_model_id();
  //   return $this->get_tracer()->traces($options);
  //
  //   // $q = $this->get_tracer()->tracing_table()->select();
  //   // $q->aw_fields_eq(['query_table' => get_class($this)::table_name(), 'query_id' => $this->get_id()]);
  //   // $q->order_by(['query_on', 'DESC']);
  //   // $q->run();
  //   // $res = $q->ret_ass();
  //   //
  //   // return $res;
  // }

  // don't really know the purpose of this anymore.. came from Tracer
  // public function traces_by_model(ModelInterface $m)
  // {
  //     return $this->get_tracer()->traces(['id' => $m->get_id(), 'table' => get_class($m)::table_name()]);
  // }
}
