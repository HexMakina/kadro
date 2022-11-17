<?php

namespace HexMakina\kadro\Models\Abilities;

use HexMakina\Tempus\Dato;

trait Event
{

    abstract public function get($prop_name);

    abstract public function __toString();

    public function event_field(): string
    {
        return 'occured_on';
    }

    public function event_value()
    {
        return $this->get($this->event_field());
    }

    public function event_label()
    {
        return '' . $this;
    }

    public static function today($format = Dato::FORMAT)
    {
        return Dato::today($format);
    }

    public static function date($date = null, $format = Dato::FORMAT)
    {
        return Dato::format($date, $format);
    }
}
