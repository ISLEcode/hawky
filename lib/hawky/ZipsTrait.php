<?php

namespace Hawky;

trait ZipsTrait {

    // Scan file system on demand
    public function zscan  ($w3path) {
        exec_trace (2, 'Hawky::zscan  location:', $w3path);

        // We're done if this path has already been scanned
        if (isset ($this->_assets [$w3path])) return $this->_assets [$w3path];

        $this->_assets [$w3path] = array();
        $proto = $this->cpproto;
        $domain = $this->cpdomain;
        $base = $this->rcget ('coreServerBase');
        if (empty($w3path)) {
            $fileNames = array($this->rcget ('assets-homedir'));
        } else {
            $fileNames = array();
            $path = mb_substr($w3path, 1);
            foreach ($this->fslist ($path, '/.*/', true, true, true) as $entry) array_push($fileNames, $entry.'/');
            foreach ($this->fslist ($path, '/.*/', true, false, true) as $entry) array_push($fileNames, $entry);
        }
        foreach ($fileNames as $fileName) {
            $file = new HawkyPage($this);
            $file->setRequestInformation($proto, $domain, $base, '/'.$fileName, $fileName);
            $file->parseData(null, false, 0);
            array_push($this->_assets[$w3path], $file);
        }

        // We're done
        return $this->_assets [$w3path];

    }

    // Return page with media file information, null if not found
    public function zfind ($location, $absoluteLocation = false) {
        $found = false;
        if ($absoluteLocation) $location = mb_substr($location, mb_strlen($this->rcget ("coreServerBase")));
        foreach ($this->zscan ($this->zparentdir ($location)) as $file) {
            if ($file->location==$location) {
                if ($this->isFileLocation($file->location)) {
                    $found = true;
                    break;
                }
            }
        }
        return $found ? $file : null;
    }

    // Return page collection with all media files
    public function zindex ($showInvisible = false, $multiPass = false, $levelMax = 0) {
        return $this->zsiblings ("", $showInvisible, $levelMax);
    }

    // Return page collection that's empty
    public function zclean () {
        return new HawkyPageCollection($this);
    }

    // Return child files
    public function zchildren ($location, $showInvisible = false) {
        $files = new HawkyPageCollection($this);
        foreach ($this->zscan ($location) as $file) {
            if ($file->isAvailable() && ($file->isVisible() || $showInvisible)) {
                if ($this->isFileLocation($file->location)) $files->append($file);
            }
        }
        return $files;
    }

    // Return child files recursively
    public function zsiblings ($location, $showInvisible = false, $levelMax = 0) {
        --$levelMax;
        $files = new HawkyPageCollection($this);
        foreach ($this->zscan ($location) as $file) {
            if ($file->isAvailable() && ($file->isVisible() || $showInvisible)) {
                if ($this->isFileLocation($file->location)) $files->append($file);
            }
            if (!$this->isFileLocation($file->location) && $levelMax!=0) {
                $files->merge($this->zsiblings ($file->location, $showInvisible, $levelMax));
            }
        }
        return $files;
    }

    // Return home location
    public function zhomedir ($location) {
        return $this->rcget ('assets-webpath');
    }

    // Return parent location
    public function zparentdir ($location) {
        $token = rtrim($this->rcget ('assets-webpath'), "/");
        if (preg_match("#^($token.*\/).+?$#", $location, $matches)) {
            if ($matches[1]!="$token/" || $this->isFileLocation($location)) $parentLocation = $matches[1];
        }
        if (empty($parentLocation)) $parentLocation = "";
        return $parentLocation;
    }

    // Return top-level location
    public function ztopdir ($location) {
        $token = rtrim($this->rcget ('assets-webpath'), "/");
        if (preg_match("#^($token.+?\/)#", $location, $matches)) $parentTopLocation = $matches[1];
        if (empty($parentTopLocation)) $parentTopLocation = "$token/";
        return $parentTopLocation;
    }
}

// vim: nospell
