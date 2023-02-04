<?php

namespace HexMakina\kadro\Controllers;

use HexMakina\BlackBox\Controllers\DisplayControllerInterface;

class Display extends Base implements DisplayControllerInterface
{
    protected array $template_variables = [];

  // Display is Base with a display function
    public function execute($method): bool
    {
        $custom_template = parent::execute($method);
        return $this->display($custom_template);
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

        $template = $this->find_template($engine, $template); // throws Exception if nothing found

        $this->viewport('controller', $this);
        $this->viewport('user_messages', $this->get('HexMakina\BlackBox\StateAgentInterface')->messages());

        $engine->addData($this->viewport());
        return $engine->render($template);
    }

    // protected function template_base(): string
    // {
    //     return strtolower(str_replace('Controller', '', (new \ReflectionClass(static::class))->getShortName()));
    // }

    protected function find_template($engine, $template_name): string
    {
        $controller_template_path = $this->className();
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
        //
        // $name = 'Reception/checkin';
        // vd($engine->path($name), $name);
        // dd($engine->exists($name), $name);
        // if (!empty($custom_template)) {
        //   // 1. check for custom template in the current controller directory
        //     $templates ['custom_3'] = sprintf('%s/%s.%s', $controller_template_path, $custom_template, $template_extension);
        //   // 2. check for custom template formatted as controller/view
        //     $templates ['custom_2'] = sprintf('%s.%s', $custom_template, $template_extension);
        //     $templates ['custom_1'] = sprintf('_layouts/%s.%s', $custom_template, $template_extension);
        // }
        //
        // if (!empty($this->router()->targetMethod())) {
        //   // 1. check for template in controller-related directory
        //     $templates ['target_1'] = sprintf('%s/%s.html', $controller_template_path, $this->router()->targetMethod());
        //   // 2. check for template in app-related directory
        //     $templates ['target_2'] = sprintf('_layouts/%s.html', $this->router()->targetMethod());
        //   // 3. check for template in kadro directory
        //     $templates ['target_3'] = sprintf('%s.html', $this->router()->targetMethod());
        // }
        //
        // $templates ['default_3'] = sprintf('%s/edit.html', $controller_template_path);
        // $templates ['default_4'] = 'edit.tpl';
        // $templates = array_unique($templates);
        //
        // while (!is_null($tpl_path = array_shift($templates))) {
        //     vd($engine->exists($tpl_path), $tpl_path);
        //     if ($engine->exists($tpl_path)) {
        //         return $tpl_path;
        //     }
        // }
        // dd($templates);

        throw new \Exception('KADRO_ERR_NO_TEMPLATE_TO_DISPLAY');
    }
}
