<?php

namespace Hamlet\Entity {

    use Hamlet\Template\{TemplateRenderer, TwigRenderer};

    abstract class AbstractTwigEntity extends AbstractTemplateEntity {

        public function getTemplateRenderer() : TemplateRenderer {
            return new TwigRenderer();
        }

        public function getMediaType() : string {
            return 'text/html;charset=UTF-8';
        }
    }
}