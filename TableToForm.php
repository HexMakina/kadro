<?php

namespace HexMakina\kadro;

use HexMakina\BlackBox\ORM\ModelInterface;
use HexMakina\Marker\{Form,Element};

class TableToForm
{
    private static function compute_field_value($model, $field_name)
    {
        $ret = '';
        if (method_exists($model, $field_name)) {
            $ret = $model->$field_name();
        }elseif (property_exists($model, $field_name)) {
            $ret = $model->$field_name;
        }

        return $ret;
    }

    public static function label($model, $field_name, $attributes = [], $errors = [])
    {
        $label_content = $field_name;

        if (isset($attributes['label'])) {
            $label_content = $attributes['label'];
            unset($attributes['label']);
        }

        $ret = Form::label($field_name, $label_content, $attributes, $errors);
        return $ret;
    }

    public static function field(ModelInterface $model, $field_name, $attributes = [], $errors = []): string
    {
        $field_value = $attributes['value'] ?? self::computeFieldValue($model, $field_name);
        $attributes['name'] = $attributes['name'] ?? $field_name;

        $table = get_class($model)::table();

        if (is_null($table->column($field_name))) {
            return Form::input($field_name, $field_value, $attributes, $errors);
        }

        $field = $table->column($field_name);

        if (isset($attributes['required']) && $attributes['required'] === false) {
            unset($attributes['required']);
        } elseif (!$field->isNullable()) {
            $attributes[] = 'required';
        }

        return self::fieldByType($field, $field_value, $attributes, $errors);
    }

    public static function fieldWithLabel($model, $field_name, $attributes = [], $errors = []): string
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
            if ($column->isAutoIncremented()) {
                if (!$model->isNew()) {
                    $form_field = self::field($model, $column_name);
                }
            } else {
                $form_field = self::fieldWithLabel($model, $column_name);
                if (!is_null($group_class)) {
                    $form_field = new Element('div', $form_field, ['class' => $group_class]);
                }
            }
            $ret .= PHP_EOL . $form_field;
        }

        return $ret;
    }

    private static function fieldByType($field, $field_value, $attributes = [], $errors = []): string
    {
        if ($field->isAutoIncremented()) {
            return Form::hidden($field->name(), $field_value);
        }
        if ($field->type()->isBoolean()) {
            $option_list = $attributes['values'] ?? [0 => 0, 1 => 1];
            return Form::select($field->name(), $option_list, $field_value, $attributes); //
        }
        if ($field->type()->isInteger()) {
            return Form::input($field->name(), $field_value, $attributes, $errors);
        }
        if ($field->type()->isYear()) {
            $attributes['size'] = $attributes['maxlength'] = 4;
            return Form::input($field->name(), $field_value, $attributes, $errors);
        }
        if ($field->type()->isDate()) {
            return Form::date($field->name(), $field_value, $attributes, $errors);
        }
        if ($field->type()->isTime()) {
            return Form::time($field->name(), $field_value, $attributes, $errors);
        }
        if ($field->type()->isDatetime()) {
            return Form::datetime($field->name(), $field_value, $attributes, $errors);
        }
        if ($field->type()->isText()) {
            return Form::textarea($field->name(), $field_value, $attributes, $errors);
        }
        if ($field->type()->isEnum()) {
            $enum_values = [];
            foreach ($field->type()->getEnumValues() as $e_val) {
                $enum_values[$e_val] = $e_val;
            }

            $selected = $attributes['value'] ?? $field_value ?? '';
          // foreach($field->)
            return Form::select($field->name(), $enum_values, $selected, $attributes); //

          // throw new \Exception('ENUM IS NOT HANDLED BY TableToFom');
        }
        if ($field->type()->isString()) {
            $max_length = $field->type()->getLength();
            $attributes['size'] = $attributes['maxlength'] = $max_length;
            return Form::input($field->name(), $field_value, $attributes, $errors);
        }

        return Form::input($field->name(), $field_value, $attributes, $errors);
    }

    private static function computeFieldValue($model, $field_name)
    {
        if (method_exists($model, $field_name)) {
            return $model->$field_name();
        }

        if (property_exists($model, $field_name)) {
            return $model->$field_name;
        }

        return '';
    }
}
