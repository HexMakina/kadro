<?php

namespace HexMakina\kadro\Models\Interfaces;

// implementation in Models\Abilities\Event
interface EventInterface
{
  public function event_label(); // event label: what is the event about ?
  public function event_value(); // event date: when is the event ?
  public function event_field(); // event date field name
}
