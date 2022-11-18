<?php

namespace HexMakina\kadro;

use HexMakina\BlackBox\ORM\ModelInterface;
use HexMakina\Marker\{Form,Element};

class TableToForm
{
    public static function label($model, string $field_name, $attributes = [], array $errors = []): string
    {
        $label_content = $field_name;

        if (isset($attributes['label'])) {
            $label_content = $attributes['label'];
            unset($attributes['label']);
        }

        return Form::label($field_name, $label_content, $attributes, $errors);
    }

    public static function field(ModelInterface $model, $field_name, $attributes = [], $errors = []): string
    {
        $field_value = $attributes['value'] ?? self::computeFieldValue($model, $field_name);
        $attributes['name'] ??= $field_name;

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

    public static function fieldWithLabel(\HexMakina\BlackBox\ORM\ModelInterface $model, string $field_name, $attributes = [], array $errors = []): string
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
            $ret = Form::hidden($field->name(), $field_value);
        }
        elseif ($field->type()->isBoolean()) {
            $option_list = $attributes['values'] ?? [0 => 0, 1 => 1];
            $ret = Form::select($field->name(), $option_list, $field_value, $attributes); //
        }
        elseif ($field->type()->isInteger()) {
            $ret = Form::input($field->name(), $field_value, $attributes, $errors);
        }
        elseif ($field->type()->isYear()) {
            $attributes['size'] = 4;
            $attributes['maxlength'] = 4;
            $ret = Form::input($field->name(), $field_value, $attributes, $errors);
        }
        elseif ($field->type()->isDate()) {
            $ret = Form::date($field->name(), $field_value, $attributes, $errors);
        }
        elseif ($field->type()->isTime()) {
            $ret = Form::time($field->name(), $field_value, $attributes, $errors);
        }
        elseif ($field->type()->isDatetime()) {
            $ret = Form::datetime($field->name(), $field_value, $attributes, $errors);
        }
        elseif ($field->type()->isText()) {
            $ret = Form::textarea($field->name(), $field_value, $attributes, $errors);
        }
        elseif ($field->type()->isEnum()) {
            $enum_values = [];
            foreach ($field->type()->getEnumValues() as $enumValue) {
                $enum_values[$enumValue] = $enumValue;
            }

            $selected = $attributes['value'] ?? $field_value ?? '';
            $ret = Form::select($field->name(), $enum_values, $selected, $attributes); //
        }
        elseif ($field->type()->isString()) {
            $max_length = $field->type()->getLength();
            $attributes['size'] = $max_length;
            $attributes['maxlength'] = $max_length;
            $ret = Form::input($field->name(), $field_value, $attributes, $errors);
        }
        else
          $ret = Form::input($field->name(), $field_value, $attributes, $errors);

        return $ret;
    }

    private static function computeFieldValue($model, $field_name)
    {
        $ret = '';
        
        if (method_exists($model, $field_name)) {
            $ret = $model->$field_name();
        }

        if (property_exists($model, $field_name)) {
            return $model->$field_name;
        }

        return $ret;
    }
}
