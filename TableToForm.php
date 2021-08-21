<?php

namespace HexMakina\kadro;

use HexMakina\TightORM\Interfaces\ModelInterface;
use HexMakina\Marker\{Form,Element};

class TableToForm
{
    private static function compute_field_value($model, $field_name)
    {
        if (method_exists($model, $field_name)) {
            return $model->$field_name();
        }

        if (property_exists($model, $field_name)) {
            return $model->$field_name;
        }

        return '';
    }

    public static function label($model, $field_name, $attributes = [], $errors = [])
    {
        $label_content = $field_name;

        if (isset($attributes['label'])) {
            $label_content = $attributes['label'];
            unset($attributes['label']);
        }
      // else
      // {
      //   $field_label = sprintf('MODEL_%s_FIELD_%s', get_class($model)::model_type(),$field_name);
      //   if(!defined("L::$field_label"))
      //   {
      //       $field_label = sprintf('MODEL_common_FIELD_%s',$field_name);
      //     if(!defined("L::$field_label"))
      //       $field_label = $field_name;
      //   }
      //
      //   $label_content = L($field_label);
      // }

        $ret = Form::label($field_name, $label_content, $attributes, $errors);
        return $ret;
    }

    public static function field(ModelInterface $model, $field_name, $attributes = [], $errors = []): string
    {
        $field_value = $attributes['value'] ?? self::compute_field_value($model, $field_name);
        $attributes['name'] = $attributes['name'] ?? $field_name;

        $table = get_class($model)::table();

        if (is_null($table->column($field_name))) {
            return Form::input($field_name, $field_value, $attributes, $errors);
        }

        $ret = '';

        $field = $table->column($field_name);


        if (isset($attributes['required']) && $attributes['required'] === false) {
            unset($attributes['required']);
        } elseif (!$field->is_nullable()) {
            $attributes[] = 'required';
        }

        if ($field->is_auto_incremented()) {
            $ret .= Form::hidden($field->name(), $field_value);
        } elseif ($field->type()->is_boolean()) {
            $option_list = $attributes['values'] ?? [0 => 0, 1 => 1];
            $ret .= Form::select($field->name(), $option_list, $field_value, $attributes); //
        } elseif ($field->type()->is_integer()) {
            $ret .= Form::input($field->name(), $field_value, $attributes, $errors);
        } elseif ($field->type()->is_year()) {
            $attributes['size'] = $attributes['maxlength'] = 4;
            $ret .= Form::input($field->name(), $field_value, $attributes, $errors);
        } elseif ($field->type()->is_date()) {
            $ret .= Form::date($field->name(), $field_value, $attributes, $errors);
        } elseif ($field->type()->is_time()) {
            $ret .= Form::time($field->name(), $field_value, $attributes, $errors);
        } elseif ($field->type()->is_datetime()) {
            $ret .= Form::datetime($field->name(), $field_value, $attributes, $errors);
        } elseif ($field->type()->is_text()) {
            $ret .= Form::textarea($field->name(), $field_value, $attributes, $errors);
        } elseif ($field->type()->is_enum()) {
            $enum_values = [];
            foreach ($field->type()->enum_values() as $e_val) {
                $enum_values[$e_val] = $e_val;
            }

            $selected = $attributes['value'] ?? $field_value ?? '';
          // foreach($field->)
            $ret .= Form::select($field->name(), $enum_values, $selected, $attributes); //

          // throw new \Exception('ENUM IS NOT HANDLED BY TableToFom');
        } elseif ($field->type()->is_string()) {
            $max_length = $field->type()->length();
            $attributes['size'] = $attributes['maxlength'] = $max_length;
            $ret .= Form::input($field->name(), $field_value, $attributes, $errors);
        } else {
            $ret .= Form::input($field->name(), $field_value, $attributes, $errors);
        }


        return $ret;
    }

    public static function field_with_label($model, $field_name, $attributes = [], $errors = []): string
    {
        $field_attributes = $attributes;
        if (isset($attributes['label'])) {
            unset($field_attributes['label']);
        }

        return sprintf('%s %s', self::label($model, $field_name, $attributes, $errors), self::field($model, $field_name, $field_attributes, $errors));
    }


    public static function fields(ModelInterface $model, $group_class = null): string
    {
        $table = get_class($model)::table();
        $ret = '';
        foreach ($table->columns() as $column_name => $column) {
          // vd($column_name);

            $form_field = '';
            if ($column->is_auto_incremented()) {
                if (!$model->is_new()) {
                    $form_field = self::field($model, $column_name);
                }
            } else {
                $form_field = self::field_with_label($model, $column_name);
                if (!is_null($group_class)) {
                    $form_field = new Element('div', $form_field, ['class' => $group_class]);
                }
            }
            $ret .= PHP_EOL . $form_field;
        }

        return $ret;
    }
}