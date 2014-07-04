<?php

namespace Hamlet\Template;

interface TemplateRendererInterface
{
    /**
     * @param mixed $data
     * @param string $path
     */
    public function render($data, $path);
}