<?php

namespace Hamlet\Entity;

class EntityLocationEnvelope
{
    protected $location;
    protected $entity;

    public function __construct($location, EntityInterface $entity)
    {
        $this->location = (string) $location;
        $this->entity = $entity;
    }

    public function getLocation()
    {
        return $this->location;
    }

    public function getEntity()
    {
        return $this->entity;
    }
}