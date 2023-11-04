<?php

namespace HexMakina\kadro\Controllers;

use HexMakina\BlackBox\Controllers\DisplayControllerInterface;

class Display extends Base implements DisplayControllerInterface
{
    protected array $template_variables = [];

    // Display is Base with a display function
    public function execute($method):bool
    {
        $custom_template = parent::execute($method);
        echo $this->display($custom_template);

        return true;
    }

    public function viewport($key = null, $value = null, $coercion = false)
    {
        $ret = null;
        // no key, returns all
        if (is_null($key)) {
            $ret = $this->template_variables;
        }
        // got key, got null value, returns $[$key]
        elseif (is_null($value) && $coercion === true) {
            $this->template_variables[$key] = $value;
        }
        // got key, got value
        // sets or coerces $[$key]=$value and returns $[$key]
        elseif (!isset($this->template_variables[$key]) || $coercion === true) {
            $this->template_variables[$key] = $value;
        }

        return $ret ?? $this->template_variables[$key] ?? null;
    }

    public function display($template, $standalone = false) : string
    {
        $engine = $this->get('HexMakina\BlackBox\TemplateInterface');
        if($this->has('settings.template.registerClass'))
        {
            foreach($this->get('settings.template.registerClass') as $class => $namespaced_class){
                $engine->registerFunction($class, function() use($namespaced_class){
                    return $namespaced_class;
                });
            }
        }
        foreach($this->get('settings.template.extensions') as $namespaced_class){
            $engine->loadExtension(new $namespaced_class);
        }
        
        $template = $this->find_template($engine, $template); // throws Exception if nothing found

        $this->viewport('controller', $this);
        $this->viewport('user_messages', $this->get('HexMakina\BlackBox\StateAgentInterface')->messages());
        $this->viewport('errors', $this->errors());

        $engine->addData($this->viewport());
        return $engine->render($template);
    }

    protected function find_template($engine, $template_name): string
    {
        $controller_template_path = $this->nid();
        // $template_extension = $engine->getFileExtension();
        $name = "$template_name";
        if($engine->exists($name))
          return $name;

        $name = "$controller_template_path/$template_name";
        if($engine->exists($name))
          return $name;

        foreach($this->get('settings.template.extraDirectories') as $folder => $path){
          $name = "$folder::$template_name";
          if($engine->exists($name))
            return $name;

          $name = "$folder::$controller_template_path/$template_name";
          if($engine->exists($name))
            return $name;
        }


        throw new \Exception('KADRO_ERR_NO_TEMPLATE_TO_DISPLAY');
    }
}
