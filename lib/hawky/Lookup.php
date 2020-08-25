<?php

namespace Hawky;

trait Lookup {

    // Return location from file path
    public function findLocationFromFile($fileName) {
        $invalid = false;
        $location = "/";
        $pathBase = $this->rcget ('pages-rootdir');
        $pathRoot = $this->rcget ("pages-defaultdir");
        $pathHome = $this->rcget ("pages-homedir");
        $fileDefault = $this->rcget ("site-indexfile");
        $fileExtension = $this->rcget ("pages-extension");

        if (mb_substr($fileName, 0, mb_strlen($pathBase))==$pathBase && mb_check_encoding($fileName, "UTF-8")) {
            $fileName = mb_substr($fileName, mb_strlen($pathBase));
            $tokens = explode("/", $fileName);
            if (!empty($pathRoot)) {
                $token = cfilename ($tokens[0])."/";
                if ($token!=$pathRoot) $location .= $token;
                array_shift($tokens);
            }
            for ($i=0; $i<count($tokens)-1; ++$i) {
                $token = cfilename ($tokens[$i])."/";
                if ($i || $token!=$pathHome) $location .= $token;
            }
            $token = cfilename ($tokens[$i], $fileExtension);
            $fileFolder = cfilename ($tokens[$i-1], $fileExtension);
            if ($token!=$fileDefault && $token!=$fileFolder) {
                $location .= cfilename ($tokens[$i], $fileExtension, true);
            }
            $extension = ($pos = mb_strrpos($fileName, ".")) ? mb_substr($fileName, $pos) : "";
            if ($extension!=$fileExtension) $invalid = true;
        } else {
            $invalid = true;
        }
        if (exec_traced (2)) {
            $debug = ($invalid ? "INVALID" : $location)." <- $pathBase$fileName";
            echo "HawkyLookup::findLocationFromFile $debug<br/>\n";
        }
        return $invalid ? "" : $location;
    }

    // Return file path from location
    public function findFileFromLocation($location, $directory = false) {
        $found = $invalid = false;
        $path = $this->rcget ('pages-rootdir');
        $pathRoot = $this->rcget ("pages-defaultdir");
        $pathHome = $this->rcget ("pages-homedir");
        $fileDefault = $this->rcget ("site-indexfile");
        $fileExtension = $this->rcget ("pages-extension");
        $tokens = explode("/", $location);
        if (isRootLocation($location)) {
            if (!empty($pathRoot)) {
                $token = (count($tokens)>2) ? $tokens[1] : rtrim($pathRoot, "/");
                $path .= findFileDirectory($path, $token, "", true, true, $found, $invalid);
            }
        } else {
            if (!empty($pathRoot)) {
                if (count($tokens)>2) {
                    if (cfilename ($tokens[1])==cfilename (rtrim($pathRoot, "/"))) $invalid = true;
                    $path .= findFileDirectory($path, $tokens[1], "", true, false, $found, $invalid);
                    if ($found) array_shift($tokens);
                }
                if (!$found) {
                    $path .= findFileDirectory($path, rtrim($pathRoot, "/"), "", true, true, $found, $invalid);
                }
            }
            if (count($tokens)>2) {
                if (cfilename ($tokens[1])==cfilename (rtrim($pathHome, "/"))) $invalid = true;
                for ($i=1; $i<count($tokens)-1; ++$i) {
                    $path .= findFileDirectory($path, $tokens[$i], "", true, true, $found, $invalid);
                }
            } else {
                $i = 1;
                $tokens[0] = rtrim($pathHome, "/");
                $path .= findFileDirectory($path, $tokens[0], "", true, true, $found, $invalid);
            }
            if (!$directory) {
                if (!is_empty($tokens[$i])) {
                    $token = $tokens[$i].$fileExtension;
                    $fileFolder = $tokens[$i-1].$fileExtension;
                    if ($token==$fileDefault || $token==$fileFolder) $invalid = true;
                    $path .= findFileDirectory($path, $token, $fileExtension, false, true, $found, $invalid);
                } else {
                    $path .= $this->findFileDefault($path, $fileDefault, $fileExtension, false);
                }
                if (exec_traced (2)) {
                    $debug = "$location -> ".($invalid ? "INVALID" : $path);
                    echo "HawkyLookup::findFileFromLocation $debug<br/>\n";
                }
            }
        }
        return $invalid ? "" : $path;
    }

    // Return children from location
    public function findChildrenFromLocation($location) {
        $fileNames = array();
        $fileDefault = $this->rcget ("site-indexfile");
        $fileExtension = $this->rcget ("pages-extension");
        if (!isFileLocation($location)) {
            $path = $this->findFileFromLocation($location, true);
            foreach (fslist ($path, "/.*/", true, true, false) as $entry) {
                $token = $this->findFileDefault($path.$entry, $fileDefault, $fileExtension, false);
                array_push($fileNames, $path.$entry."/".$token);
            }
            if (!isRootLocation($location)) {
                $fileFolder = cfilename (basename($path), $fileExtension);
                $regex = "/^.*\\".$fileExtension."$/";
                foreach (fslist ($path, $regex, true, false, false) as $entry) {
                    if (cfilename ($entry, $fileExtension)==$fileDefault) continue;
                    if (cfilename ($entry, $fileExtension)==$fileFolder) continue;
                    array_push($fileNames, $path.$entry);
                }
            }
        }
        return $fileNames;
    }

    // Return language from file path
    public function findLanguageFromFile($fileName, $languageDefault) {
        $language = $languageDefault;
        $pathBase = $this->rcget ('pages-rootdir');
        $pathRoot = $this->rcget ("pages-defaultdir");
        if (!empty($pathRoot)) {
            $fileName = mb_substr($fileName, mb_strlen($pathBase));
            if (preg_match("/^(.+?)\//", $fileName, $matches)) {
                $name = cfilename ($matches[1]);
                if (mb_strlen($name)==2) $language = $name;
            }
        }
        return $language;
    }

    // Return file path from media location
    public function zipdirof ($url) {

        if (!isFileLocation ($url)) return null;

        // Collect the URL base path to assets (and determine its length)
        $w3dir = rtrim ($this->rcget ('assets-webpath'), '/') . '/'; $l = mb_strlen ($w3dir);

        // Make sure that are URL is on the ZIPURL path
        if (mb_substr ($url, 0, $l) != $w3dir) return null;
        $fsdir = rtrim ($this->rcget ('assets-homedir'), '/') . '/';

        return $fsdir .mb_substr($url, $l);

    }

    // Return file path from system location
    public function findFileFromSystem($location) {
        $fileName = null;
        if (preg_match("/\.(css|gif|ico|js|jpg|png|svg|woff|woff2)$/", $location)) {
            $extensionLocationLength = mb_strlen($this->rcget ("plugins-webpath"));
            $themeLocationLength = mb_strlen($this->rcget ("themes-webpath"));
            if (mb_substr($location, 0, $extensionLocationLength)==$this->rcget ("plugins-webpath")) {
                $fileName = $this->rcget ("plugins-homedir").mb_substr($location, $extensionLocationLength);
            } elseif (mb_substr($location, 0, $themeLocationLength)==$this->rcget ("themes-webpath")) {
                $fileName = $this->rcget ("themes-homedir").mb_substr($location, $themeLocationLength);
            }
        }
        return $fileName;
    }

    // Normalise location, make absolute location
    public function normaliseLocation($location, $pageLocation, $filterStrict = true) {
        if (!preg_match("/^\w+:/", trim(html_entity_decode($location, ENT_QUOTES, "UTF-8")))) {
            $pageBase = $this->cpbaseurl;
            $mediaBase = $this->rcget ("coreServerBase").$this->rcget ('assets-webpath');
            if (!preg_match("/^\#/", $location)) {
                if (!preg_match("/^\//", $location)) {
                    $location = $this->getDirectoryLocation($pageBase.$pageLocation).$location;
                } elseif (!preg_match("#^($pageBase|$mediaBase)#", $location)) {
                    $location = $pageBase.$location;
                }
            }
            $location = str_replace("/./", "/", $location);
            $location = str_replace(":", $this->w3argsep (), $location);
        } else {
            if ($filterStrict && !preg_match("/^(http|https|ftp|mailto):/", $location)) $location = "error-xss-filter";
        }
        return $location;
    }

    // Return redirect location
    public function getRedirectLocation($location) {
        if (isFileLocation($location)) {
            $location = "$location/";
        } else {
            $languageDefault = $this->rcget ("language");
            $language = $this->ualang ($this->getLanguages(), $languageDefault);
            $location = "/$language/";
        }
        return $location;
    }

    // Check if location can be redirected into directory
    public function isRedirectLocation($location) {
        $redirect = false;
        if (isFileLocation($location)) {
            $redirect = is_dir($this->findFileFromLocation("$location/", true));
        } elseif ($location=="/") {
            $redirect = $this->rcget ("site-multilingual");
        }
        return $redirect;
    }

    // Check if location contains nested directories
    public function isNestedLocation($location, $fileName, $checkHomeLocation = false) {
        $nested = false;
        if (!$checkHomeLocation || $location==$this->getHomeLocation($location)) {
            $path = dirname($fileName);
            if (count(fslist ($path, "/.*/", true, true, false))) $nested = true;
        }
        return $nested;
    }

    // Check if location is available
    public function isAvailableLocation($location, $fileName) {
        $available = true;
        $pathBase = $this->rcget ('pages-rootdir');
        if (mb_substr($fileName, 0, mb_strlen($pathBase))==$pathBase) {
            $sharedLocation = $this->getHomeLocation($location).$this->rcget ("pages-sharedir");
            if (mb_substr($location, 0, mb_strlen($sharedLocation))==$sharedLocation) $available = false;
        }
        return $available;
    }

    // Check if location is within current HTTP request
    public function isActiveLocation($location, $currentLocation) {
        if (isFileLocation($location)) {
            $active = $currentLocation==$location;
        } else {
            if ($location==$this->getHomeLocation($location)) {
                $active = $this->getDirectoryLocation($currentLocation)==$location;
            } else {
                $active = mb_substr($currentLocation, 0, mb_strlen($location))==$location;
            }
        }
        return $active;
    }

}


// vim: nospell
