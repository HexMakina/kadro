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

    public function display($custom_template, $standalone = false): string
    {
        $smarty = $this->get('\Smarty');

        $template = $this->find_template($smarty, $custom_template); // throws Exception if nothing found
        $this->viewport('controller', $this);

        $messages_session_key = \HexMakina\LogLaddy\LogLaddy::OSD_SESSION_KEY;
        
        $messages = $this->get('HexMakina\BlackBox\StateAgentInterface')->get($messages_session_key);
        $this->viewport('user_messages', $messages);

        $this->viewport('web_root', $this->router()->webRoot());
        $this->viewport('view_path', $this->router()->filePath() . $this->get('settings.smarty.template_path') . 'app/');
        $this->viewport('view_url', $this->router()->webRoot() . $this->get('settings.smarty.template_path'));
        $this->viewport('images_url', $this->router()->webRoot() . $this->get('settings.smarty.template_path') . 'images/');

        foreach ($this->viewport() as $template_var_name => $value) {
            $smarty->assign($template_var_name, $value);
        }

        if ($standalone === false) {
            return $smarty->fetch(sprintf('%s|%s', $this->get('settings.smarty.template_inclusion_path'), $template));
        } 
        
        return $smarty->fetch($template);
    }

    protected function template_base(): string
    {
        return strtolower(str_replace('Controller', '', (new \ReflectionClass(static::class))->getShortName()));
    }

    protected function find_template($smarty, $custom_template = null): string
    {
        $controller_template_path = $this->template_base();
        $templates = [];

        if (!empty($custom_template)) {
          // 1. check for custom template in the current controller directory
            $templates ['custom_3'] = sprintf('%s/%s.html', $controller_template_path, $custom_template);
          // 2. check for custom template formatted as controller/view
            $templates ['custom_2'] = $custom_template . '.html';
            $templates ['custom_1'] = '_layouts/' . $custom_template . '.html';
        }

        if (!empty($this->router()->targetMethod())) {
          // 1. check for template in controller-related directory
            $templates ['target_1'] = sprintf('%s/%s.html', $controller_template_path, $this->router()->targetMethod());
          // 2. check for template in app-related directory
            $templates ['target_2'] = sprintf('_layouts/%s.html', $this->router()->targetMethod());
          // 3. check for template in kadro directory
            $templates ['target_3'] = sprintf('%s.html', $this->router()->targetMethod());
        }

        $templates ['default_3'] = sprintf('%s/edit.html', $controller_template_path);
        $templates ['default_4'] = 'edit.tpl';
        $templates = array_unique($templates);
        while (!is_null($tpl_path = array_shift($templates))) {
            if ($smarty->templateExists($tpl_path)) {
                return $tpl_path;
            }
        }

        throw new \Exception('KADRO_ERR_NO_TEMPLATE_TO_DISPLAY');
    }
}
