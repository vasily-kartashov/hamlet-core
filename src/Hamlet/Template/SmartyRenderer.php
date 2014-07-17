<?php

namespace Hamlet\Template;

use Smarty;

class SmartyRenderer implements TemplateRendererInterface
{
    protected $pluginDirectoryPath;

    public function __construct($pluginDirectoryPath = null)
    {
        assert($pluginDirectoryPath == null || is_dir($pluginDirectoryPath));
        $this->pluginDirectoryPath = $pluginDirectoryPath;
    }

    public function render($data, $path)
    {
        $wrappedData = (is_array($data)) ? $data : ['content' => $data];
        $smarty = new Smarty();
        $smarty->setCacheDir(sys_get_temp_dir());
        $smarty->setCompileDir(sys_get_temp_dir());
        if ($this->pluginDirectoryPath != null) {
            $smarty->addPluginsDir($this->pluginDirectoryPath);
        }
        $smarty->assign($wrappedData);
        return $smarty->fetch($path);
    }
}