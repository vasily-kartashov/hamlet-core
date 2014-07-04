<?php
namespace Hamlet\Request;

interface RequestInterface
{
    /**
     * @return string
     */
    public function getMethod();
}