<?php

namespace Hamlet\Entities;

use Hamlet\Templating\TemplateRenderer;
use Hamlet\Templating\TwigRenderer;

abstract class AbstractTwigEntity extends AbstractTemplateEntity
{
    public function getTemplateRenderer(): TemplateRenderer
    {
        return new TwigRenderer();
    }

    public function getMediaType(): string
    {
        return 'text/html;charset=UTF-8';
    }
}
