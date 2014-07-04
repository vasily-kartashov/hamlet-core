<?php

namespace Hamlet\Entity;

use Hamlet\Template\TwigRenderer;

abstract class AbstractTwigEntity extends AbstractTemplateEntity
{
    public function getTemplateRenderer()
    {
        return new TwigRenderer();
    }

    public function getMediaType()
    {
        return 'text/html;charset=UTF-8';
    }
}