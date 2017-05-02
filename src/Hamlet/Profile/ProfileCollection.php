<?php

namespace Hamlet\Profile;

use Exception;
use stdClass;

class ProfileCollection
{

    private $path;

    public function __construct()
    {
        $config = $_SERVER + $_ENV;
        if (isset($config['HOME'])) {
            $homePath = $config['HOME'];
        } elseif (isset($config['HOMEDRIVE']) && isset($config['HOMEPATH'])) {
            $homePath = $config['HOMEDRIVE'] . $config['HOMEPATH'];
        } else {
            $homePath = '/vagrant';
        }
        if (substr($homePath, -1) == DIRECTORY_SEPARATOR) {
            $separator = '';
        } else {
            $separator = DIRECTORY_SEPARATOR;
        }
        $suffix = ['.hamlet', 'profiles.json'];
        $this->path = $homePath . $separator . join(DIRECTORY_SEPARATOR, $suffix);
        $directoryPath = dirname($this->path);
        if (is_file($directoryPath)) {
            throw new Exception("File already exists '{$directoryPath}'");
        } else if (!file_exists($directoryPath)) {
            if (!mkdir($directoryPath, 700, true)) {
                throw new Exception("Cannot create directory '{$directoryPath}'");
            }
        }
    }

    public function getProfileNames()
    {
        return array_keys((array)$this->readSettings());
    }

    private function readSettings()
    {
        $json = json_decode(file_get_contents($this->path));
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        } else {
            throw new Exception("Couldn't parse JSON in " . $this->path);
        }
    }

    public function getProfile($profileName)
    {
        return $this->readSettings()->{$profileName} ?? new stdClass();
    }

    public function setProfile($profileName, $profileSettings)
    {
        $settings = $this->readSettings();
        $settings->{$profileName} = $profileSettings;
        $this->writeSettings($settings);
    }

    private function writeSettings($settings)
    {
        file_put_contents($this->path, json_encode($settings, JSON_PRETTY_PRINT));
    }

    public function deleteProfile($profileName)
    {
        $settings = $this->readSettings();
        unset($settings->{$profileName});
        $this->writeSettings($settings);
    }
}
