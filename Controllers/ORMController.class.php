<?php

namespace HexMakina\kadro\Controllers;
use \HexMakina\Crudites\Interfaces\ModelInterface;

abstract class ORMController extends KadroController implements Interfaces\ORMController
{
  use \HexMakina\Crudites\TraitIntrospector;

  protected $model_class_name = null;
  protected $model_type = null;

  protected $load_model = null;
  protected $form_model = null;

  protected $errors = [];

  public function errors() : array
  {
    return $this->errors;
  }

  public function execute()
  {
    $this->authorize();

    $custom_template = null;
    $method = $this->router()->target_method();

    foreach(['prepare', "before_$method", $method, "after_$method"] as $step => $chainling)
    {
      $this->search_and_execute_trait_methods($chainling);
    	if(method_exists($this, $chainling) && empty($this->errors()))
      {
        $res = $this->$chainling();

        if($this->logger()->has_halting_messages()) // logger handled a critical error during the chailing execution
        {
          break; // dont go on with other
        }

        if($chainling === $method)
        {

          $custom_template = $res;
        }
      }
    }

    if(method_exists($this, 'conclude')) // conclude always executed, even with has_halting_messages
    	$this->conclude();

    if(method_exists($this, 'display'))
    	$template = $this->display($custom_template);
  }

  public function prepare()
  {
    parent::prepare();

    if(!class_exists($this->model_class_name = $this->class_name()))
      throw new \Exception("!class_exists($this->model_class_name)");

    $this->model_type = $this->model_class_name::model_type();

    $reflection = new \ReflectionClass($this->model_class_name);
    $this->form_model = $reflection->newInstanceWithoutConstructor(); //That's it!

    $pk_values = [];

    if($this->router()->submits())
    {
      $this->form_model->import($this->sanitize_post_data($this->router()->submitted()));
      $pk_values = $this->model_class_name::table()->primary_keys_match($this->router()->submitted());

      $this->load_model = $this->model_class_name::exists($pk_values);
    }
    elseif($this->router()->requests())
    {
      $pk_values = $this->model_class_name::table()->primary_keys_match($this->router()->params());

      if(!is_null($this->load_model = $this->model_class_name::exists($pk_values)))
        $this->form_model = clone $this->load_model;
    }

    if(!is_null($this->load_model) && $this->load_model->traceable())
      $this->viewport('load_model_history', $this->load_model->traces() ?? []);
  }

  public function has_load_model()
  {
    return !empty($this->load_model);
  }
  // ----------- META -----------
  public function class_name() : string
  {
    if(is_null($this->model_class_name))
    {
      $this->model_class_name = get_called_class();
      $this->model_class_name = str_replace('\Controllers\\', '\Models\\', $this->model_class_name);
      $this->model_class_name = str_replace('Controller', '', $this->model_class_name);
    }

    return $this->model_class_name;
  }

  public function persist_model($model) : ?ModelInterface
  {
    $this->errors = $model->save($this->operator()->operator_id()); // returns [errors]
    if(empty($this->errors()))
    {
      $this->logger()->nice('CRUDITES_INSTANCE_ALTERED', ['MODEL_'.get_class($model)::model_type().'_INSTANCE']);
      return $model;
    }
    foreach($this->errors() as $field => $error_msg)
      $this->logger()->warning($error_msg, [$field]);

    return null;
  }

  public function dashboard()
  {
    return $this->listing(); //default dashboard is a listing
  }

  public function listing($model=null,$filters=[],$options=[])
  {
    $class_name = is_null($model) ? $this->model_class_name : get_class($model);

    if(!isset($filters['date_start']))  $filters['date_start'] = $this->box('StateAgent')->filters('date_start');
    if(!isset($filters['date_stop']))   $filters['date_stop'] = $this->box('StateAgent')->filters('date_stop');

    // dd($filters);
    $listing = $class_name::filter($filters);
    // dd($listing);
    $this->viewport_listing($class_name, $listing, $this->find_template($this->box('template_engine'), __FUNCTION__));
  }

  public function viewport_listing($class_name, $listing, $listing_template)
  {
    $listing_fields = [];
    if(empty($listing))
    {
      foreach($class_name::table()->columns() as $column)
      {

        if(!$column->is_auto_incremented() && !$column->is_hidden())
          $listing_fields[$column->name()]=L(sprintf('MODEL_%s_FIELD_%s', $class_name::model_type(), $column->name()));
      }
    }
    else
    {
      $current = current($listing);
      if(is_object($current))
        $current = get_object_vars($current);

      foreach(array_keys($current) as $field)
      {
        $listing_fields[$field]=L(sprintf('MODEL_%s_FIELD_%s', $class_name::model_type(), $field));
      }

    }

    $this->viewport('listing', $listing);
    $this->viewport('listing_title', L(sprintf('MODEL_%s_INSTANCES', $class_name::model_type())));
    $this->viewport('listing_fields', $listing_fields);
    $this->viewport('listing_template', $listing_template);

    $this->viewport('route_new', $this->router()->prehop($class_name::model_type().'_new'));
    $this->viewport('route_export', $this->router()->prehop($class_name::model_type().'_export'));
  }

  public function copy()
  {
    $this->form_model = $this->load_model->copy();

    $this->route_back($this->load_model);
    $this->edit();
  }

  public function edit(){}

  public function save()
  {
    $model = $this->persist_model($this->form_model);

    if(empty($this->errors()))
      $this->route_back($model);
    else
    {
      $this->edit();
      return 'edit';
    }
  }

  public function before_edit()
  {
    if(!is_null($this->router()->params('id')) && is_null($this->load_model))
    {
      $this->logger()->warning('CRUDITES_ERR_INSTANCE_NOT_FOUND', ['MODEL_'.$this->model_class_name::model_type().'_INSTANCE']);
      $this->router()->hop($this->model_class_name::model_type());
    }
  }

  public function before_save() : array { return [];}

  // default: hop to altered object
  public function after_save() {$this->router()->hop($this->route_back());}

  public function destroy_confirm()
  {
    if(is_null($this->load_model))
    {
      $this->logger()->warning('CRUDITES_ERR_INSTANCE_NOT_FOUND', ['MODEL_'.$this->model_type.'_INSTANCE']);
      $this->router()->hop($this->model_type);
    }

    $this->before_destroy();

    return 'destroy';
  }

  public function before_destroy() // default: checks for load_model and immortality, hops back to object on failure
  {
    if(is_null($this->load_model))
    {
      $this->logger()->warning('CRUDITES_ERR_INSTANCE_NOT_FOUND', ['MODEL_'.$this->model_type.'_INSTANCE']);
      $this->router()->hop($this->model_type);
    }
    elseif($this->load_model->immortal())
    {

      $this->logger()->warning('CRUDITES_ERR_INSTANCE_IS_IMMORTAL', ['MODEL_'.$this->model_type.'_INSTANCE']);
      $this->router()->hop($this->route_model($this->load_model));
    }
  }

  public function destroy()
  {
    if(!$this->router()->submits())
      throw new \Exception('KADRO_ROUTER_MUST_SUBMIT');

    if($this->load_model->destroy($this->operator()->operator_id()) === false)
    {
      $this->logger()->info('CRUDITES_ERR_INSTANCE_IS_UNDELETABLE', [''.$this->load_model]);
      $this->route_back($this->load_model);
    }
    else
    {
      $this->logger()->nice('CRUDITES_INSTANCE_DESTROYED', ['MODEL_'.$this->model_type.'_INSTANCE']);
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

    if(isset($this->load_model))
      $this->viewport('load_model', $this->load_model);

    if(isset($this->form_model))
      $this->viewport('form_model', $this->form_model);
  }

  public function collection_to_csv($collection, $filename)
  {
    // TODO use Format/File/CSV class to generate file
    $file_path = $this->box('settings.export.directory').$filename.'.csv';
    $fp = fopen($file_path, 'w');

    $header = false;

    foreach($collection as $line)
    {
      $line = get_object_vars($line);
      if($header === false)
      {
        fputcsv($fp,array_keys($line));
        $header = true;
      }
      fputcsv($fp,$line);
    }
    fclose($fp);

    $this->router()->send_file($file_path);
  }

  public function export()
  {
    $format = $this->router()->params('format');
    switch($format)
    {
      case null:
        $filename = $this->model_type;
        $collection = $this->model_class_name::listing();
        return $this->collection_to_csv($collection, $filename);

      case 'xlsx':
        $report_controller = $this->box('HexMakina\koral\Controllers\ReportController');
        return $report_controller->collection($this->model_class_name);
    }

  }

  public function route_new(ModelInterface $model) : string
  {
    return $this->router()->prehop(get_class($model)::model_type().'_new');
  }

  public function route_list(ModelInterface $model) : string
  {
    return $this->router()->prehop(get_class($model)::model_type());
  }

  public function route_model(ModelInterface $model) : string
  {
    $route_params = [];

    $route_name = get_class($model)::model_type().'_';
    if($model->is_new())
      $route_name.= 'new';
    else
    {
      $route_name.= 'default';
      $route_params = ['id' => $model->get_id()];
    }
    $res = $this->router()->prehop($route_name, $route_params);
    return $res;
  }

  public function route_factory($route=null, $route_params=[]) : string
  {
    if(is_null($route) && $this->router()->submits())
      $route = $this->form_model;

    if(!is_null($route) && is_subclass_of($route, '\HexMakina\Crudites\Interfaces\ModelInterface'))
      $route = $this->route_model($route);

    return parent::route_factory($route, $route_params);
  }

  private function sanitize_post_data($post_data=[])
  {
    foreach($this->model_class_name::table()->columns() as $col)
    {
      if($col->type()->is_boolean())
      {
          $post_data[$col->name()] = !empty($post_data[$col->name()]);
      }
    }

    return $post_data;
  }
}
?>
