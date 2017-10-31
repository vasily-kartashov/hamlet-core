<?php

namespace Hamlet\Templating;

interface TemplateRenderer
{
    /**
     * @param mixed $data
     * @param string $path
     * @return string
     */
    public function render($data, string $path): string;
}
