<?php

namespace Hawky;

trait PlugTrait {

    // Load extensions
    public function loadplugin ($path) {
        foreach (fslist ($path, "/^.*\.php$/", true, false) as $entry) {
            exec_trace (3, "HawkyExtension::load file:$entry<br/>");
            $this->_plugins_ts = max($this->_plugins_ts, filemtime($entry));
            require_once ($entry);
            $name = $this->hawky->lookup->normaliseName(basename($entry), true, true);
            $this->register(lcfirst($name), "Hawky".ucfirst($name));
        }
        $callback = function ($a, $b) {
            return $a["priority"] - $b["priority"];
        };
        uasort($this->_plugins, $callback);
        foreach ($this->_plugins as $key=>$value) {
            if (method_exists($this->_plugins[$key]["object"], "onLoad")) $this->_plugins[$key]["object"]->onLoad($this->hawky);
        }

    }

    // Register extension
    public function registerplugin ($key, $class) {
        if (!$this->isplugin ($key) && class_exists($class)) {
            $this->_plugins[$key] = array();
            $this->_plugins[$key]["object"] = $class=="HawkyCore" ? new stdClass : new $class;
            $this->_plugins[$key]["class"] = $class;
            $this->_plugins[$key]["version"] = defined("$class::VERSION") ? $class::VERSION : 0;
            $this->_plugins[$key]["priority"] = defined("$class::PRIORITY") ? $class::PRIORITY : count($this->_plugins) + 10;
        }
    }

    // Return extension
    public function getplugin ($key) {
        return $this->_plugins[$key]["object"];
    }

    // Return extensions modification date, Unix time or HTTP format
    public function pluginrevision ($httpFormat = false) {
        return $httpFormat ? strw3time ($this->_plugins_ts) : $this->_plugins_ts;
    }

    // Check if extension exists
    public function isplugin ($key) {
        return isset($this->_plugins[$key]);
    }
}

// vim: nospell
