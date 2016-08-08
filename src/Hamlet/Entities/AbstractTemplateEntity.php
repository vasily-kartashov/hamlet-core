<?php

namespace Hamlet\Entities {

    use Hamlet\Templating\TemplateRenderer;

    abstract class AbstractTemplateEntity extends AbstractEntity {

        abstract protected function getTemplatePath() : string;

        abstract protected function getTemplateData();

        abstract protected function getTemplateRenderer() : TemplateRenderer;

        public function getContent() : string {
            return $this -> getTemplateRenderer()
                         -> render($this -> getTemplateData(), $this -> getTemplatePath());
        }
    }
}