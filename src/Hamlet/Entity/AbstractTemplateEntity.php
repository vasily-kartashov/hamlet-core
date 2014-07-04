<?php
namespace Hamlet\Entity;

abstract class AbstractTemplateEntity extends AbstractEntity
{
    /**
     * Get absolute path to entity template
     * @return mixed
     */
    abstract protected function getTemplatePath();

    /**
     * Get template data
     * @return mixed
     */
    abstract protected function getTemplateData();

    /**
     * @return \Hamlet\Template\TemplateRendererInterface
     */
    abstract protected function getTemplateRenderer();

    /**
     * Get content
     * @return string
     */
    public function getContent()
    {
        return $this->getTemplateRenderer()->render($this->getTemplateData(), $this->getTemplatePath());
    }
}