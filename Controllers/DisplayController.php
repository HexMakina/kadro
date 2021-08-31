<?php

namespace HexMakina\kadro\Controllers;

class DisplayController extends BaseController implements Interfaces\DisplayControllerInterface
{
    protected $template_variables = [];

  // DisplayController is BaseController with a display function
    public function execute()
    {
        $custom_template = parent::execute();
        return $this->display($custom_template);
    }

    public function viewport($key = null, $value = null, $coercion = false)
    {
      // no key, returns all
        if (is_null($key)) {
            return $this->template_variables;
        }

      // got key, got null value, returns $[$key]
        if (is_null($value)) {
            if ($coercion === true) { // break rule 1 ?
                $this->template_variables[$key] = null;
            }

            return $this->template_variables[$key] ?? null;
        }

      // got key, got value
      // sets or coerces $[$key]=$value and returns $[$key]
        if (!isset($this->template_variables[$key]) || $coercion === true) {
            $this->template_variables[$key] = $value;
        }

        return $this->template_variables[$key] ?? null;
    }

    public function display($custom_template = null, $standalone = false)
    {
        $smarty = $this->get('template_engine');

        $template = $this->find_template($smarty, $custom_template); // throws Exception if nothing found

        $this->viewport('controller', $this);

        $this->viewport('user_messages', $this->logger()->getUserReport());


        $this->viewport('file_root', $this->router()->file_root());
        $this->viewport('view_path', $this->router()->file_root() . $this->get('settings.smarty.template_path') . 'app/');
        $this->viewport('web_root', $this->router()->web_root());
        $this->viewport('view_url', $this->router()->web_root() . $this->get('settings.smarty.template_path'));
        $this->viewport('images_url', $this->router()->web_root() . $this->get('settings.smarty.template_path') . 'images/');

        foreach ($this->viewport() as $template_var_name => $value) {
            $smarty->assign($template_var_name, $value);
        }

        if ($standalone === false) {
            $smarty->display(sprintf('%s|%s', $this->get('settings.smarty.template_inclusion_path'), $template));
        } else {
            $smarty->display($template);
        }


        return true;
    }

    protected function template_base()
    {
        return strtolower(str_replace('Controller', '', (new \ReflectionClass(get_called_class()))->getShortName()));
    }

    protected function find_template($smarty, $custom_template = null)
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

        if (!empty($this->router()->target_method())) {
          // 1. check for template in controller-related directory
            $templates ['target_1'] = sprintf('%s/%s.html', $controller_template_path, $this->router()->target_method());
          // 2. check for template in app-related directory
            $templates ['target_2'] = sprintf('_layouts/%s.html', $this->router()->target_method());
          // 3. check for template in kadro directory
            $templates ['target_3'] = sprintf('%s.html', $this->router()->target_method());
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
