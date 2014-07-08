<?php

namespace Hamlet\Entity;

use Hamlet\Template\SmartyRenderer;

abstract class AbstractSmartyEntity extends AbstractTemplateEntity
{
    public function getTemplateRenderer()
    {
        return new SmartyRenderer();
    }

    public function getMediaType()
    {
        return 'text/html;charset=UTF-8';
    }
}