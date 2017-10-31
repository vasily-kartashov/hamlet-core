<?php

namespace Hamlet\Templating;

use Twig_Loader_Filesystem;
use Twig_Environment;

class TwigRenderer implements TemplateRenderer
{
    /**
     * @param mixed $data
     * @param string $path
     * @return string
     */
    public function render($data, string $path): string
    {
        $loader = new Twig_Loader_Filesystem();
        $loader->addPath(dirname($path));
        $environment = new Twig_Environment($loader, [
            'cache' => sys_get_temp_dir(),
            'auto_reload' => true
        ]);
        $wrappedData = is_array($data) ? $data : ['content' => $data];
        return $environment->render(basename($path), $wrappedData);
    }
}
