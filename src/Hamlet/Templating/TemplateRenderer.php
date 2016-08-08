<?php

namespace Hamlet\Templating {

    interface TemplateRenderer {

        public function render($data, string $path);
    }
}
