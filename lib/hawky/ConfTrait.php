<?php

namespace Hawky;

use \Hawky\Set as HawkySet;

trait ConfTrait {

    // Load system settings from file
    public function rcload ($fileName) {
        exec_trace (2, "HawkySystem::load file:$fileName<br/>");
        $this->_config_ts = fsmtime ($fileName);
        $data = fsread ($fileName);
        $this->_config = $this->yamlget ($data, "");
        if (exec_traced (3)) {
            foreach ($this->_config as $key=>$value) {
                echo "HawkySystem::load ".ucfirst($key).":$value<br/>\n";
            }
        }
    }

    // Save system settings to file
    public function rcsave ($file, $settings) {
        $this->_config_ts = time();
        $settingsNew = new HawkySet();
        foreach ($settings as $key=>$value) {
            if (!empty($key) && !is_empty($value)) {
                $this->set($key, $value);
                $settingsNew[$key] = $value;
            }
        }
        $data = fsread ($file);
        $data = $this->setTextSettings($data, "", "", $settingsNew);
        return fswrite ($file, $data);
    }

    // Set system setting
    public function rcset ($key, $value, $default = false) {
        if ($default) $this->_defaults[$key] = $value; else $this->_config[$key] = $value;
    }

    // Return system setting
    public function rcget($key, $escaped = false) {

        $value = isset ($this ->_config   [$key]) ? $this->_config   [$key] :
                 isset ($this ->_defaults [$key]) ? $this->_defaults [$key] : '';

        return $escaped ? htmlspecialchars ($value) : $value;

    }

    // Return system settings
    public function rcsettings ($filterStart = "", $filterEnd = "") {
        $settings = array();
        if (empty($filterStart) && empty($filterEnd)) {
            $settings = array_merge($this->_defaults->getArrayCopy(), $this->_config->getArrayCopy());
        } else {
            foreach (array_merge($this->_defaults->getArrayCopy(), $this->_config->getArrayCopy()) as $key=>$value) {
                if (!empty($filterStart) && mb_substr($key, 0, mb_strlen($filterStart))==$filterStart) $settings[$key] = $value;
                if (!empty($filterEnd) && mb_substr($key, -mb_strlen($filterEnd))==$filterEnd) $settings[$key] = $value;
            }
        }
        return $settings;
    }

    // Return supported values for system setting, empty if not known
    public function rcvalues ($key) {
        $values = array();

        switch ($key) {

        case 'email':
            foreach ($this->user->_config as $userKey=>$userValue) array_push ($values, $userKey);
            break;

        case 'language':
            foreach ($this->language->_config as $languageKey=>$languageValue) array_push ($values, $languageKey);
            break;

        case 'layout':
            $path = $this->rcget("coreLayoutDirectory");
            foreach (fslist ($path, "/^.*\.html$/", true, false, false) as $entry)
                array_push ($values, lcfirst(mb_substr($entry, 0, -5)));
            break;

        case 'theme':
            $path = $this->rcget("themes-homedir");
            foreach (fslist ($path, "/^.*\.css$/", true, false, false) as $entry)
                array_push($values, lcfirst(mb_substr($entry, 0, -4)));
            break;
        }

        return $values;

    }

    // Return system settings modification date, Unix time or HTTP format
    public function rcrevision ($httpFormat = false) {
        return $httpFormat ? $this->getHttpDateFormatted($this->_config_ts) : $this->_config_ts;
    }

    // Check if system setting exists
    public function rctest ($key) {
        return isset($this->_config[$key]);
    }
}

// vim: nospell
