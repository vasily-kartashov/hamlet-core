<?php

namespace Hamlet\Template {

    interface TemplateRenderer {

        public function render($data, string $path);
    }
}