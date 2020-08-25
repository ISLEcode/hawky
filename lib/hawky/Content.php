<?php

namespace Hawky;

use \Hawky\Pages as HawkyPages;

trait Content {

    // Scan file system on demand
    public function fsscan ($location) {
        if (!isset($this->pages[$location])) {
            exec_trace (2, "HawkyContent::fsscan  location:$location<br/>");
            $this->pages[$location] = array();
            $scheme = $this->cpproto;
            $address = $this->cpurl;
            $base = $this->cpbaseurl;
            if (empty($location)) {
                $rootLocations = $this->fspagepaths ();
                foreach ($rootLocations as $rootLocation) {
                    list($rootLocation, $fileName) = getTextList($rootLocation, " ", 2);
                    $page = new HawkyPage($this->hawky);
                    $page->setRequestInformation($scheme, $address, $base, $rootLocation, $fileName);
                    $page->parseData("", false, 0);
                    array_push($this->pages[$location], $page);
                }
            } else {
                $fileNames = $this->findChildrenFromLocation($location);
                foreach ($fileNames as $fileName) {
                    $page = new HawkyPage($this->hawky);
                    $page->setRequestInformation($scheme, $address, $base,
                        $this->findLocationFromFile($fileName), $fileName);
                    $page->parseData($this->fsread ($fileName, 4096), false, 0);
                    if (strlen($page->rawData)<4096) $page->statusCode = 200;
                    array_push($this->pages[$location], $page);
                }
            }
        }
        return $this->pages[$location];
    }

    // Return page from, null if not found
    public function find($location, $absoluteLocation = false) {
        $found = false;
        if ($absoluteLocation) $location = mb_substr ($location, mb_strlen($this->cpbaseurl));
        foreach ($this->fsscan ($this->getParentLocation($location)) as $page) {
            if ($page->location==$location) {
                if (!$this->isRootLocation($page->location)) {
                    $found = true;
                    break;
                }
            }
        }
        return $found ? $page : null;
    }

    // Return page collection with all pages
    public function index($showInvisible = false, $multiLanguage = false, $levelMax = 0) {
        $rootLocation = $multiLanguage ? '' : $this->getRootLocation($this->cpurl);
        return $this->getChildrenRecursive($rootLocation, $showInvisible, $levelMax);
    }

    // Return page collection with top-level navigation
    public function top($showInvisible = false, $showOnePager = true) {
        $rootLocation = $this->getRootLocation($this->cpurl);
        $pages = $this->getChildren($rootLocation, $showInvisible);
        if (count($pages)==1 && $showOnePager) {
            $scheme = $this->cpproto;
            $address = $this->cpaddress;
            $base = $this->cpbaseurl;
            $one = ($pages->offsetGet(0)->location!=$this->cpurl) ? $pages->offsetGet(0) : $this;
            preg_match_all("/<h(\d) id=\"([^\"]+)\">(.*?)<\/h\d>/i", $one->getContent(), $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                if ($match[1]==2) {
                    $page = new HawkyPage($this->hawky);
                    $page->setRequestInformation($scheme, $address, $base, $one->location."#".$match[2], $one->fileName);
                    $page->parseData("---\nTitle: $match[3]\n---\n", false, 0);
                    $pages->append($page);
                }
            }
        }
        return $pages;
    }

    // Return page collection with path ancestry
    public function path($location, $absoluteLocation = false) {
        $pages = new HawkyPages ($this->hawky);
        if ($absoluteLocation) $location = mb_substr($location, mb_strlen($this->cpbaseurl));
        if ($page = $this->find($location)) {
            $pages->prepend($page);
            for (; $parent = $page->getParent(); $page=$parent) {
                $pages->prepend($parent);
            }
            $home = $this->find($this->getHomeLocation($page->location));
            if ($home && $home->location!=$page->location) $pages->prepend($home);
        }
        return $pages;
    }

    // Return page collection with multiple languages
    public function multi($location, $absoluteLocation = false, $showInvisible = false) {
        $pages = new HawkyPages ($this->hawky);
        if ($absoluteLocation) $location = mb_substr($location, mb_strlen($this->cpbaseurl));
        $locationEnd = mb_substr($location, mb_strlen($this->getRootLocation($location)) - 4);
        foreach ($this->fsscan ("") as $page) {
            if ($content = $this->find(mb_substr($page->location, 4).$locationEnd)) {
                if ($content->isAvailable() && ($content->isVisible() || $showInvisible)) {
                    if (!$this->isRootLocation($content->location)) $pages->append($content);
                }
            }
        }
        return $pages;
    }

    // Return page collection that's empty
    public function clean() { return new HawkyPages ($this->hawky); }

    // Return languages in multi language mode
    public function getLanguages($showInvisible = false) {
        $languages = array();
        foreach ($this->fsscan ("") as $page) {
            if ($page->isAvailable() && ($page->isVisible() || $showInvisible)) array_push($languages, $page->get("language"));
        }
        return $languages;
    }

    // Return child pages
    public function getChildren($location, $showInvisible = false) {
        $pages = new HawkyPages ($this->hawky);
        foreach ($this->fsscan ($location) as $page) {
            if ($page->isAvailable() && ($page->isVisible() || $showInvisible)) {
                if (!$this->isRootLocation($page->location) && is_readable($page->fileName)) $pages->append($page);
            }
        }
        return $pages;
    }

    // Return child pages recursively
    public function getChildrenRecursive($location, $showInvisible = false, $levelMax = 0) {
        --$levelMax;
        $pages = new HawkyPages ($this);
        foreach ($this->fsscan ($location) as $page) {
            if ($page->isAvailable() && ($page->isVisible() || $showInvisible)) {
                if (!$this->isRootLocation($page->location) && is_readable($page->fileName)) $pages->append($page);
            }
            if (!$this->isFileLocation($page->location) && $levelMax!=0) {
                $pages->merge($this->getChildrenRecursive($page->location, $showInvisible, $levelMax));
            }
        }
        return $pages;
    }

    // Return shared pages
    public function getShared($location) {
        $pages = new HawkyPages ($this->hawky);
        $location = $this->getHomeLocation($location).$this->rcget ("pages-sharedir");
        foreach ($this->fsscan ($location) as $page) {
            if ($page->get("status")=="shared") $pages->append($page);
        }
        return $pages;
    }

    // Return root location
    public function getRootLocation($location) {
        $rootLocation = "root/";
        if ($this->rcget("site-multilingual")) {
            foreach ($this->fsscan ("") as $page) {
                $token = mb_substr($page->location, 4);
                if ($token!="/" && mb_substr($location, 0, mb_strlen($token))==$token) {
                    $rootLocation = "root$token";
                    break;
                }
            }
        }
        return $rootLocation;
    }

    // Return home location
    public function getHomeLocation($location) {
        return mb_substr($this->getRootLocation($location), 4);
    }

    // Return parent location
    public function getParentLocation($location) {
        $token = rtrim(mb_substr($this->getRootLocation($location), 4), "/");
        if (preg_match("#^($token.*\/).+?$#", $location, $matches)) {
            if ($matches[1]!="$token/" || $this->isFileLocation($location)) $parentLocation = $matches[1];
        }
        if (empty($parentLocation)) $parentLocation = "root$token/";
        return $parentLocation;
    }

    // Return top-level location
    public function getParentTopLocation($location) {
        $token = rtrim(mb_substr($this->getRootLocation($location), 4), "/");
        if (preg_match("#^($token.+?\/)#", $location, $matches)) $parentTopLocation = $matches[1];
        if (empty($parentTopLocation)) $parentTopLocation = "$token/";
        return $parentTopLocation;
    }

}


// vim: nospell
