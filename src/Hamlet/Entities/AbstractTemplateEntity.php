<?php

namespace Hamlet\Entities;

use Hamlet\Templating\TemplateRenderer;

abstract class AbstractTemplateEntity extends AbstractEntity
{
    public function getContent(): string
    {
        return $this->getTemplateRenderer()
                    ->render($this->getTemplateData(), $this->getTemplatePath());
    }

    abstract protected function getTemplateRenderer(): TemplateRenderer;

    abstract protected function getTemplateData(): array;

    abstract protected function getTemplatePath(): string;
}
