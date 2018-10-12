<?php

namespace Hamlet\Requests;

class MutableObject
{
    private $value;

    public function __construct($value)
    {
        $this->set($value);
    }

    public function set($value)
    {
        $this->value = $value;
    }

    public function get()
    {
        return $this->value;
    }
}
