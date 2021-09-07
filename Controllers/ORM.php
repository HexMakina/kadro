<?php

namespace HexMakina\kadro\Controllers;

use HexMakina\Interfaces\ORM\ModelInterface;
use HexMakina\Interfaces\Controllers\ORMInterface;
use HexMakina\LeMarchand\LeMarchand;

abstract class ORM extends Kadro implements ORMInterface
{
    protected $model_class_name = null;
    protected $model_type = null;

    protected $load_model = null;
    protected $form_model = null;


    public function add_errors($errors)
    {
        foreach ($errors as $err) {
            if (is_array($err)) {
                $this->add_error(array_unshift($err), array_unshift($err));
            } else {
                $this->add_error($err);
            }
        }
    }

    public function load_model(): ?ModelInterface
    {
        return $this->load_model;
    }

    public function formModel(ModelInterface $setter = null): ModelInterface
    {
        if (!is_null($setter)) {
            $this->form_model = $setter;
        } elseif (is_null($this->form_model)) {
            $reflection = new \ReflectionClass($this->modelClassName());
            $this->form_model = $reflection->newInstanceWithoutConstructor(); //That's it!
        }
        return $this->form_model;
    }

    // shortcut to model_type
    public function modelType(): string
    {
      // have to go through the model to garantee model_type existence via interface
        if (is_null($this->model_type)) {
            $this->model_type = get_class($this->formModel())::model_type();
        }

        return $this->model_type;
    }

    public function modelPrefix($suffix = null): string
    {
        $ret = $this->modelType();

        if (!is_null($suffix)) {
            $ret .= '_' . $suffix;
        }

        return $ret;
    }

    public function prepare()
    {
        parent::prepare();

        $this->model_type = $this->modelClassName()::model_type();

        $pk_values = [];

        if ($this->router()->submits()) {
            $this->formModel()->import($this->sanitize_post_data($this->router()->submitted()));
            $pk_values = $this->modelClassName()::table()->primary_keys_match($this->router()->submitted());

            $this->load_model = $this->modelClassName()::exists($pk_values);
        } elseif ($this->router()->requests()) {
            $pk_values = $this->modelClassName()::table()->primary_keys_match($this->router()->params());

            if (!is_null($this->load_model = $this->modelClassName()::exists($pk_values))) {
                $this->formModel(clone $this->load_model);
            }
        }
        // TODO restore model history
        // if (!is_null($this->load_model) && is_subclass_of($this->load_model, '\HexMakina\Tracer\TraceableInterface') && $this->load_model->traceable()) {
        //   // $traces = $this->tracer()->traces_by_model($this->load_model);
        //     $traces = $this->load_model->traces();
        //     //$this->tracer()->history_by_model($this->load_model);
        //     $this->viewport('load_model_history', $traces ?? []);
        // }
    }

    public function has_load_model()
    {
        return !empty($this->load_model);
    }

    // ----------- META -----------

    // CoC class name by
    // 1. replacing namespace Controllers by Models
    // 2. removing the  from classname
    // overwrite this behavior by setting the model_class_name at controller construction
    public function modelClassName(): string
    {
        if (is_null($this->model_class_name)) {
            preg_match(LeMarchand::RX_MVC, get_called_class(), $m);
            $this->model_class_name = $this->get('Models\\'.$m[2].'::class');
        }

        return $this->model_class_name;
    }

    // public function table_name(): string
    // {
    //     return $this->modelClassName()::table_name();
    // }

    public function model_type_to_label($model = null)
    {
        $model = $model ?? $this->load_model ?? $this->formModel();
        return $this->l(sprintf('MODEL_%s_INSTANCE', get_class($model)::model_type()));
    }
    public function field_name_to_label($model, $field_name)
    {
        $model = $model ?? $this->load_model ?? $this->formModel();
        return $this->l(sprintf('MODEL_%s_FIELD_%s', (get_class($model))::model_type(), $field_name));
    }

    public function dashboard()
    {
        $this->listing(); //default dashboard is a listing
    }

    public function listing($model = null, $filters = [], $options = [])
    {
        $class_name = is_null($model) ? $this->modelClassName() : get_class($model);

        if (!isset($filters['date_start'])) {
            $filters['date_start'] = $this->get('StateAgent')->filters('date_start');
        }
        if (!isset($filters['date_stop'])) {
            $filters['date_stop'] = $this->get('StateAgent')->filters('date_stop');
        }

        $listing = $this->modelClassName()::filter($filters);

        $this->viewport_listing($class_name, $listing, $this->find_template($this->get('template_engine'), __FUNCTION__));
    }

    public function viewport_listing($class_name, $listing, $listing_template)
    {
        $listing_fields = [];
        if (empty($listing)) {
            $hidden_columns = ['created_by', 'created_on', 'password'];
            foreach ($class_name::table()->columns() as $column) {
                if (!$column->isAutoIncremented() && !in_array($column->name(), $hidden_columns)) {
                    $listing_fields[$column->name()] = $this->l(sprintf('MODEL_%s_FIELD_%s', $class_name::model_type(), $column->name()));
                }
            }
        } else {
            $current = current($listing);
            if (is_object($current)) {
                $current = get_object_vars($current);
            }

            foreach (array_keys($current) as $field) {
                $listing_fields[$field] = $this->l(sprintf('MODEL_%s_FIELD_%s', $class_name::model_type(), $field));
            }
        }

        $this->viewport('listing', $listing);
        $this->viewport('listing_title', $this->l(sprintf('MODEL_%s_INSTANCES', $class_name::model_type())));
        $this->viewport('listing_fields', $listing_fields);
        $this->viewport('listing_template', $listing_template);

        $this->viewport('route_new', $this->router()->hyp($class_name::model_type() . '_new'));
        $this->viewport('route_export', $this->router()->hyp($class_name::model_type() . '_export'));
    }

    public function copy()
    {
        $this->formModel($this->load_model->copy());

        $this->route_back($this->load_model);
        $this->edit();
    }

    public function edit()
    {
    }

    public function save()
    {
        $model = $this->persist_model($this->formModel());

        if (empty($this->errors())) {
            $this->route_back($model);
        } else {
            $this->edit();
            return 'edit';
        }
    }

    public function persist_model($model): ?ModelInterface
    {
        $this->errors = $model->save($this->operator()->operator_id()); // returns [errors]
        if (empty($this->errors())) {
            $this->logger()->nice($this->l('CRUDITES_INSTANCE_ALTERED', [$this->l('MODEL_' . get_class($model)::model_type() . '_INSTANCE')]));
            return $model;
        }
        foreach ($this->errors() as $field => $error_msg) {
            $this->logger()->warning($this->l($error_msg, [$field]));
        }

        return null;
    }

    public function before_edit()
    {
        if (!is_null($this->router()->params('id')) && is_null($this->load_model)) {
            $this->logger()->warning($this->l('CRUDITES_ERR_INSTANCE_NOT_FOUND', [$this->l('MODEL_' . $this->modelClassName()::model_type() . '_INSTANCE')]));
            $this->router()->hop($this->modelClassName()::model_type());
        }
    }

    public function before_save()
    {
        return [];
    }

  // default: hop to altered object
    public function after_save()
    {
        $this->router()->hop($this->route_back());
    }

    public function destroy_confirm()
    {
        if (is_null($this->load_model)) {
            $this->logger()->warning($this->l('CRUDITES_ERR_INSTANCE_NOT_FOUND', [$this->l('MODEL_' . $this->model_type . '_INSTANCE')]));
            $this->router()->hop($this->model_type);
        }

        $this->before_destroy();

        return 'destroy';
    }

    public function before_destroy() // default: checks for load_model and immortality, hops back to object on failure
    {
        if (is_null($this->load_model)) {
            $this->logger()->warning($this->l('CRUDITES_ERR_INSTANCE_NOT_FOUND', [$this->l('MODEL_' . $this->model_type . '_INSTANCE')]));
            $this->router()->hop($this->model_type);
        } elseif ($this->load_model->immortal()) {
            $this->logger()->warning($this->l('CRUDITES_ERR_INSTANCE_IS_IMMORTAL', [$this->l('MODEL_' . $this->model_type . '_INSTANCE')]));
            $this->router()->hop($this->route_model($this->load_model));
        }
    }

    public function destroy()
    {
        if (!$this->router()->submits()) {
            throw new \Exception('KADRO_ROUTER_MUST_SUBMIT');
        }

        if ($this->load_model->destroy($this->operator()->operator_id()) === false) {
            $this->logger()->info($this->l('CRUDITES_ERR_INSTANCE_IS_UNDELETABLE', ['' . $this->load_model]));
            $this->route_back($this->load_model);
        } else {
            $this->logger()->nice($this->l('CRUDITES_INSTANCE_DESTROYED', [$this->l('MODEL_' . $this->model_type . '_INSTANCE')]));
            $this->route_back($this->model_type);
        }
    }

    public function after_destroy()
    {
        $this->router()->hop($this->route_back());
    }

    public function conclude()
    {
        $this->viewport('errors', $this->errors());
        $this->viewport('form_model_type', $this->model_type);
        $this->viewport('form_model', $this->formModel());

        if (isset($this->load_model)) {
            $this->viewport('load_model', $this->load_model);
        }
    }

    public function collection_to_csv($collection, $filename)
    {
      // TODO use Format/File/CSV class to generate file
        $file_path = $this->get('settings.export.directory') . $filename . '.csv';
        $fp = fopen($file_path, 'w');

        $header = false;

        foreach ($collection as $line) {
            $line = get_object_vars($line);
            if ($header === false) {
                fputcsv($fp, array_keys($line));
                $header = true;
            }
            fputcsv($fp, $line);
        }
        fclose($fp);

        return $file_path;
    }

    public function export()
    {
        $format = $this->router()->params('format');
        switch ($format) {
            case null:
                $filename = $this->model_type;
                $collection = $this->modelClassName()::listing();
                $file_path = $this->collection_to_csv($collection, $filename);
                $this->router()->sendFile($file_path);
                break;

            case 'xlsx':
                $report_controller = $this->get('HexMakina\koral\Controllers\ReportController');
                return $report_controller->collection($this->modelClassName());
        }
    }

    public function route_new(ModelInterface $model): string
    {
        return $this->router()->hyp(get_class($model)::model_type() . '_new');
    }

    public function route_list(ModelInterface $model): string
    {
        return $this->router()->hyp(get_class($model)::model_type());
    }

    public function route_model(ModelInterface $model): string
    {
        $route_params = [];

        $route_name = get_class($model)::model_type() . '_';
        if ($model->is_new()) {
            $route_name .= 'new';
        } else {
            $route_name .= 'default';
            $route_params = ['id' => $model->get_id()];
        }
        $res = $this->router()->hyp($route_name, $route_params);
        return $res;
    }

    public function route_factory($route = null, $route_params = []): string
    {
        if (is_null($route) && $this->router()->submits()) {
            $route = $this->formModel();
        }

        if (!is_null($route) && is_subclass_of($route, '\HexMakina\Interfaces\ORM\ModelInterface')) {
            $route = $this->route_model($route);
        }

        return parent::route_factory($route, $route_params);
    }

    private function sanitize_post_data($post_data = [])
    {
        foreach ($this->modelClassName()::table()->columns() as $col) {
            if ($col->type()->isBoolean()) {
                $post_data[$col->name()] = !empty($post_data[$col->name()]);
            }
        }

        return $post_data;
    }

    // overriding displaycontroller
    protected function template_base()
    {
        return $this->modelClassName()::model_type();
    }
}
