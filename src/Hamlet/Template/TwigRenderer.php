<?php

namespace Hamlet\Template {

    use Twig_Loader_Filesystem;
    use Twig_Environment;

    class TwigRenderer implements TemplateRenderer {

        public function render($data, string $path) : string {
            $loader = new Twig_Loader_Filesystem();
            $loader -> addPath(dirname($path));
            $environment = new Twig_Environment($loader, [
                'cache' => sys_get_temp_dir(),
            ]);
            $wrappedData = is_array($data) ? $data : ['content' => $data];
            return $environment -> render(basename($path), $wrappedData);
        }
    }
}
