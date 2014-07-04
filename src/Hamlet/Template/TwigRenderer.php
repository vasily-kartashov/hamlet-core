<?php

namespace Hamlet\Template;

use Twig_Loader_Filesystem;
use Twig_Environment;

class TwigRenderer implements TemplateRendererInterface
{

    /**
     * @param mixed $data
     * @param string $path
     * @return string
     */
    public function render($data, $path)
    {
        $loader = new Twig_Loader_Filesystem();
        $loader->addPath(dirname($path));
        $environment = new Twig_Environment($loader, array(
            'cache' => sys_get_temp_dir(),
        ));
        $wrappedData = (is_array($data)) ? $data : array('content' => $data);
        return $environment->render(basename($path), $wrappedData);
    }
}
