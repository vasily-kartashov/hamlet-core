<?php

namespace Hamlet\Templating;

use Exception;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class TwigRenderer implements TemplateRenderer
{
    /**
     * @param mixed $data
     * @param string $path
     * @return string
     * @throws Exception
     */
    public function render($data, string $path): string
    {
        $loader = new FilesystemLoader();
        $loader->addPath(dirname($path));
        $environment = new Environment($loader, [
            'cache' => sys_get_temp_dir(),
            'auto_reload' => true
        ]);
        $wrappedData = is_array($data) ? $data : ['content' => $data];
        return $environment->render(basename($path), $wrappedData);
    }
}
