<?php

namespace HexMakina\kadro\Models\Abilities;

use HexMakina\Tempus\Dato;

trait Event
{

    public function event_field()
    {
        return 'occured_on';
    }

    public function event_value()
    {
        return $this->get($this->event_field());
    }

    public function event_label()
    {
        return $this->__toString();
    }

    public static function today($format = Dato::FORMAT)
    {
        return Dato::today($format);
    }

    public static function date($date = null, $format = Dato::FORMAT): string
    {
        return Dato::format($date, $format);
    }

}
