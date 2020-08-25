<?php


namespace Hawky;

//use Hawky\Page  as HawkyPage;
use Hawky\Pages as HawkyPages;
use Hawky\Set   as HawkySet;

trait PageTrait {

    // Set request information
    public function setpagerequestinfo ($scheme, $address, $base, $location, $fileName) {
        $this->cpproto = $scheme;
        $this->cpaddress = $address;
        $this->cpbaseurl = $base;
        $this->cpurl = $location;
        $this->cpfsname = $fileName;
    }

    // Parse page data
    public function parsepagedata ($rawData, $cacheable, $statusCode, $pageError = "") {
        $this->cprawdata = $rawData;
        $this->cpparser = null;
        $this->cpparserdata = "";
        $this->cpavailable = $this->isAvailableLocation($this->cpurl, $this->cpfsname);
        $this->cpvisible = true;
        $this->cpactive = $this->isActiveLocation($this->cpurl, $this->cpurl);
        $this->cpcacheable = $cacheable;
        $this->cprevision = 0;
        $this->cpstatus = $statusCode;
        $this->parsepagemeta ($pageError);
    }

    // Parse page data update
    public function parsepagedataupdate () {
        if ($this->cpstatus==0) {
            $this->cprawdata = $this->fsread ($this->cpfsname);
            $this->cpstatus = 200;
            $this->parsepagemeta ();
        }
    }

    // Parse page meta data
    public function parsepagemeta ($pageError = '') {
        $this->_metadata = new HawkySet();
        if (!is_null($this->cprawdata)) {
            $this->cpset ('title', $this->createTextTitle($this->cpurl));
            $this->cpset ('language', $this->findLanguageFromFile($this->cpfsname, $this->rcget ('language')));
            $this->cpset ('modified', date('Y-m-d H:i:s', fsmtime ($this->cpfsname)));
            $this->parsepagemetaraw (array('site-name', 'author', 'layout', 'theme', 'parser', 'status'));
            $titleHeader = ($this->cpurl==$this->getHomeLocation($this->cpurl)) ?
                $this->cpget ('site-name') : $this->cpget ('title').' - '.$this->cpget ('site-name');
            if (!$this->havepage ('titleContent')) $this->cpset ('titleContent', $this->cpget ('title'));
            if (!$this->havepage ('titleNavigation')) $this->cpset ('titleNavigation', $this->cpget ('title'));
            if (!$this->havepage ('titleHeader')) $this->cpset ('titleHeader', $titleHeader);
            if ($this->cpget ('status')=='unlisted') $this->cpvisible = false;
            if ($this->cpget ('status')=='shared') $this->cpavailable = false;

            $p = $this->rcget ('site-protocol'); $d = $this->rcget ('site-domain'); $a = $this->rcget ('site-address');
            $this->cpset ('pageRead', absurl ($p, $d, $a, $this->cpurl));
            $this->cpset ('pageEdit', absurl ($p, $d, $a, rtrim($this->rcget ('editLocation'), '/').$this->cpurl));
            $this->setPage('main', $this);
        } else {
            $this->cpset ('type', $this->fsextension ($this->cpfsname));
            $this->cpset ('group', $this->fsgroup ($this->cpfsname, $this->rcget ('assets-homedir')));
            $this->cpset ('modified', date('Y-m-d H:i:s', fsmtime ($this->cpfsname)));
        }
        if (!empty($pageError)) $this->cpset ('pageError', $pageError);
        foreach ($this->_plugins as $key=>$value) {
            if (method_exists($value['object'], 'onParseMeta')) $value['object']->onParseMeta($this);
        }
    }

    // Parse page meta data from raw data
    public function parsepagemetaraw ($defaultKeys) {
        foreach ($defaultKeys as $key) {
            $value = $this->rcget ($key);
            if (!empty($key) && !is_empty($value)) $this->cpset ($key, $value);
        }
        if (preg_match("/^(\xEF\xBB\xBF)?\-\-\-[\r\n]+(.+?)\-\-\-[\r\n]+/s", $this->cprawdata, $parts)) {
            $this->cpmetaoffset = strlen($parts[0]);
            foreach (preg_split("/[\r\n]+/", $parts[2]) as $line) {
                if (preg_match("/^\s*(.*?)\s*:\s*(.*?)\s*$/", $line, $matches)) {
                    if (!empty($matches[1]) && !is_empty($matches[2])) $this->cpset ($matches[1], $matches[2]);
                }
            }
        } elseif (preg_match("/^(\xEF\xBB\xBF)?([^\r\n]+)[\r\n]+=+[\r\n]+/", $this->cprawdata, $parts)) {
            $this->cpmetaoffset = strlen($parts[0]);
            $this->cpset ("title", $parts[2]);
        }
    }

    // Parse page content on demand
    public function parsepagecontent ($sizeMax = 0) {
        if (!is_null($this->cprawdata) && !is_object($this->cpparser)) {
            if ($this->isplugin ($this->cpget ("pages-markup"))) {
                $value = $this->_plugins[$this->cpget ("pages-markup")];
                if (method_exists($value["object"], "onParseContentRaw")) {
                    $this->cpparser = $value["object"];
                    $this->cpparserdata = $this->getpagecontent (true, $sizeMax);
                    $this->cpparserdata = preg_replace("/@pageRead/i", $this->cpget ("pageRead"), $this->cpparserdata);
                    $this->cpparserdata = preg_replace("/@pageEdit/i", $this->cpget ("pageEdit"), $this->cpparserdata);
                    $this->cpparserdata = $this->cpparser->onParseContentRaw($this, $this->cpparserdata);
                    $this->cpparserdata = $this->normaliseData($this->cpparserdata, "html");
                    foreach ($this->_plugins as $key=>$value) {
                        if (method_exists($value["object"], "onParseContentHtml")) {
                            $output = $value["object"]->onParseContentHtml($this, $this->cpparserdata);
                            if (!is_null($output)) $this->cpparserdata = $output;
                        }
                    }
                }
            } else {
                $this->cpparserdata = $this->getpagecontent (true, $sizeMax);
                $this->cpparserdata = preg_replace("/\[hawky error\]/i", $this->cpget ("pageError"), $this->cpparserdata);
            }
            if (!$this->havepage ("description")) {
                $description = $this->createTextDescription($this->cpparserdata, 150);
                $this->cpset ("description", !empty($description) ? $description : $this->cpget ("title"));
            }
            exec_trace (3, "HawkyPage::parseContent location:".$this->cpurl."<br/>");
        }
    }

    // Parse page content shortcut
    public function parsepagecontentshortcut ($name, $text, $type) {
        $output = null;
        foreach ($this->_plugins as $key=>$value) {
            if (method_exists($value["object"], "onParseContentShortcut")) {
                $output = $value["object"]->onParseContentShortcut($this, $name, $text, $type);
                if (!is_null($output)) break;
            }
        }
        if (is_null($output)) {
            if ($name=="hawky" && $type=="inline") {
                if ($text=="about") {
                    $output = "Datenstrom Hawky ".HawkyCore::RELEASE."<br />\n";
                    $dataCurrent = $this->_plugins;
                    uksort($dataCurrent, "strnatcasecmp");
                    foreach ($dataCurrent as $key=>$value) {
                        $output .= ucfirst($key)." ".$value["version"]."<br />\n";
                    }
                }
                if ($text=="error") $output = $this->cpget ("pageError");
                if ($text=="log") {
                    $fileName = $this->rcget ("plugins-homedir").$this->rcget ("site-logfile");
                    $fileHandle = @fopen($fileName, "r");
                    if ($fileHandle) {
                        $dataBufferSize = 512;
                        fseek($fileHandle, max(0, filesize($fileName) - $dataBufferSize));
                        $dataBuffer = fread($fileHandle, $dataBufferSize);
                        if (strlen($dataBuffer)==$dataBufferSize) {
                            $dataBuffer = ($pos = mb_strpos($dataBuffer, "\n")) ? mb_substr($dataBuffer, $pos+1) : $dataBuffer;
                        }
                        fclose($fileHandle);
                    }
                    $output = str_replace("\n", "<br />\n", htmlspecialchars($dataBuffer));
                }
            }
        }

        if (!empty ($name)) exec_trace (4, "HawkyPage::parseContentShortcut name:$name type:$type<br/>");
        return $output;

    }

    // Parse page
    public function parsepage () {
        $this->parsepagelayout ($this->cpget ("layout"));
        if (!$this->ispagecacheable ()) $this->setpageheader ("Cache-Control", "no-cache, no-store");
        if (!$this->ispageheader ("Content-Type")) $this->setpageheader ("Content-Type", "text/html; charset=utf-8");
        if (!$this->ispageheader ("Content-Modified")) $this->setpageheader ("Content-Modified", $this->getpagemodified (true));
        if (!$this->ispageheader ("Last-Modified")) $this->setpageheader ("Last-Modified", $this->getpagerevision (true));
        $fileNameTheme = $this->rcget ("themes-homedir").normaliseName($this->cpget ("theme")).".css";

        if (!is_file($fileNameTheme))
            $this->pageerror (500, "Theme '".$this->cpget ("theme")."' does not exist!");

        if (!is_object($this->cpparser))
            $this->pageerror (500, "Parser '".$this->cpget ("pages-markup")."' does not exist!");

        if (!$this->islang ($this->cpget ("language"))) 
            $this->pageerror (500, "Language '".$this->cpget ("language")."' does not exist!");

        if ($this->isNestedLocation($this->cpurl, $this->cpfsname, true))
            $this->pageerror (500, "Folder '".dirname($this->cpfsname)."' may not contain subfolders!");

        if ($this->webhandler () == 'core' && $this->cpstatus == 200) {

            if ($this->havepage ('redirect')) {
                $location = $this->normaliseLocation ($this->cpget ('redirect'), $this->cpurl);
                $location = absurl ($this->cpproto, $this->cpaddress, '', $location);
                $this->cleanpage(301, $location);
            }

            if (!$this->ispageavailable ()) $this->pageerror (404);

        }

        if ($this->havepage ("pageClean")) $this->_output = null;
        foreach ($this->_plugins as $key=>$value) {
            if (method_exists($value["object"], "onParsePageOutput")) {
                $output = $value["object"]->onParsePageOutput($this, $this->_output);
                if (!is_null($output)) $this->_output = $output;
            }
        }

    }

    // Parse page layout
    public function parsepagelayout ($name) {
        foreach ($this->getShared($this->cpurl) as $page) {
            $this->cpshared[basename($page->cpurl)] = $page;
            $page->cpshared["main"] = $this;
        }

        $this->_output = null;
        foreach ($this->_plugins as $key => $plugin) {
            if (!method_exists ($plugin['object'], 'onlayout')) continue;
            exec_trace (2, '%s: invoking %s->onlayout()', $name, $plugin);
            $plugin ['object'] ->onlayout ($this, $name);
        }

        if (!is_null($this->_output)) return true;

        // Render page and store its output in `_output` buffer
        ob_start(); $this->ploadlayout ($name); $this->_output = ob_get_contents(); ob_end_clean();

    }

    // Include page layout
    protected function ploadlayout ($name) {
        exec_trace ('%s: using this default layout', $name);

        $homedir   = rtrim ($this->rcget ('layouts-homedir'), '/') . '/';
        $extension = $this->rcget ('layouts-extension');
        $basename  = normaliseName ($name);
        $theme     = normaliseName ($this->cpget ('theme'));
        $default   = $homedir .                $basename . $extension;
        $layout    = $homedir . $theme . '-' . $basename . $extension;

        if (is_file ($layout)) {
            exec_trace (2, 'Hawky::ploadlayout file: %s', $layout);
            $this->setpagerevision (filemtime ($layout));
            require ($layout);
            return true;
        }

        if (is_file ($default)) {
            exec_trace (2, 'Hawky::ploadlayout  file: %s', $default);
            $this->setpagerevision (filemtime ($default));
            require ($default);
            return true;
        }

        $this->pageerror (500, "Layout '$name' does not exist!");
        exec_trace ('%s: layout not found or not accessible', $name);

    }

    // Set page setting
    public function cpset ($key, $value) {
        $this->_metadata[$key] = $value;
    }

    // Return page setting
    public function cpget ($key, $encoded = false) {
        $val = $this->havepage ($key) ? $this->_metadata[$key] : '';
        return $encoded ? htmlspecialchars ($val) : $val;
    }

    // Return page setting as language specific date
    public function getpagedate ($key, $fmt = '', $encoded = false) {
        $fmt = $this->getnlskey (empty ($fmt) ? 'fdate' : $fmt);
        $val = $this->getnlsdate (strtotime ($this->cpget ($key)), $fmt);
        return $encoded ? htmlspecialchars ($val) : $val;
    }

    // Return page setting as language specific date, relative to today
    public function getpagerdate ($key, $fmt = '', $encoded = false, $limit = 30) {
        $fmt = $this->getnlskey (empty ($fmt) ? 'fdate' : $fmt);
        $val = $this->getnlsrdate (strtotime ($this->cpget ($key)), $fmt, $limit);
        return $encoded ? htmlspecialchars ($val) : $val;
    }

    // Return page content, HTML encoded or raw format
    public function getpagecontent ($rawFormat = false, $sizeMax = 0) {
        if ($rawFormat) {
            $this->parsepagedataupdate ();
            $text = substr($this->cprawdata, $this->cpmetaoffset);
        } else {
            $this->parsepagecontent ($sizeMax);
            $text = $this->cpparserdata;
        }
        return $sizeMax ? substr($text, 0, $sizeMax) : $text;
    }

    // Return parent page, null if none
    public function getpageparent () {
        $parentLocation = $this->getParentLocation($this->cpurl);
        return $this->findcontent ($parentLocation);
    }

    // Return top-level parent page, null if none
    public function getpagetopparent ($homeFallback = false) {
        $parentTopLocation = $this->getParentTopLocation($this->cpurl);
        if (!$this->findcontent ($parentTopLocation) && $homeFallback) {
            $parentTopLocation = $this->getHomeLocation($this->cpurl);
        }
        return $this->findcontent ($parentTopLocation);
    }

    // Return page collection with pages on the same level
    public function getpagesiblings ($showInvisible = false) {
        $parentLocation = $this->getParentLocation($this->cpurl);
        return $this->getChildren($parentLocation, $showInvisible);
    }

    /* TODO redundant
    // Return page collection with child pages
    public function getChildren($showInvisible = false) {
        return $this->getChildren($this->cpurl, $showInvisible);
    }

    // Return page collection with child pages recursively
    public function getChildrenRecursive($showInvisible = false, $levelMax = 0) {
        return $this->getChildrenRecursive($this->cpurl, $showInvisible, $levelMax);
    }
     */

    // Set page collection with additional pages
    public function setPages($key, $pages) {
        $this->cppages[$key] = $pages;
    }

    // Return page collection with additional pages
    public function getPages($key) {
        return isset($this->cppages[$key]) ? $this->cppages[$key] : new HawkyPages ($this->hawky);
    }

    // Set shared page
    public function setPage($key, $page) {
        $this->cpshared[$key] = $page;
    }

    // Return shared page
    public function getPage($key) {
        return isset($this->cpshared[$key]) ? $this->cpshared[$key] : new HawkyPage ($this->hawky);
    }

    // Return page URL
    public function getpagefullurl () {
        return absurl ($this->cpproto, $this->cpaddress, $this->cpbaseurl, $this->cpurl);
    }

    // Return page base
    public function getpagebaseurl ($multiLanguage = false) {
        return $multiLanguage ? rtrim($this->cpbaseurl.$this->getHomeLocation($this->cpurl), "/") :  $this->cpbaseurl;
    }

    // Return page location
    public function getpageurl ($absoluteLocation = false) {
        return $absoluteLocation ? $this->cpbaseurl.$this->cpurl : $this->cpurl;
    }

    // Set page request argument
    public function setpagerequest ($key, $value) {
        $_REQUEST[$key] = $value;
    }

    // Return page request argument
    public function getpagerequest ($key, $encode = false) {
        $val = isset ($_REQUEST[$key]) ? $_REQUEST[$key] : '';
        return $encode ? htmlspecialchars ($val) : $val;
    }

    // Set page response header
    public function setpageheader  ($key, $value) { $this->_header[$key] = $value; }

    // Return page response header
    public function getpageheader  ($key) { return $this->ispageheader ($key) ? $this->_header[$key] : ""; }

    // Return page extra data
    public function getpagextra ($name) {
        $output = "";
        foreach ($this->_plugins as $key=>$value) {
            if (method_exists($value["object"], "onParsePageExtra")) {
                $outputExtension = $value["object"]->onParsePageExtra($this, $name);
                if (!is_null($outputExtension)) $output .= $outputExtension;
            }
        }
        if ($name=="header") {
            $fileNameTheme = $this->rcget ("themes-homedir").normaliseName($this->get("theme")).".css";
            if (is_file($fileNameTheme)) {
                $locationTheme = $this->rcget ("coreServerBase").
                    $this->rcget ("themes-webpath").normaliseName($this->get("theme")).".css";
                $output .= "<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"$locationTheme\" />\n";
            }
            $fileNameScript = $this->rcget ("themes-homedir").normaliseName($this->get("theme")).".js";
            if (is_file($fileNameScript)) {
                $locationScript = $this->rcget ("coreServerBase").
                    $this->rcget ("themes-webpath").normaliseName($this->get("theme")).".js";
                $output .= "<script type=\"text/javascript\" src=\"$locationScript\"></script>\n";
            }
            $fileNameFavicon = $this->rcget ("themes-homedir").normaliseName($this->get("theme")).".png";
            if (is_file($fileNameFavicon)) {
                $locationFavicon = $this->rcget ("coreServerBase").
                    $this->rcget ("themes-webpath").normaliseName($this->get("theme")).".png";
                $output .= "<link rel=\"icon\" type=\"image/png\" href=\"$locationFavicon\" />\n";
            }
        }
        return $output;
    }

    // Overwrite rendered page with the provided content
    public function poverwrite ($content) { $this->_output = $content; }

    // Return page modification date, Unix time or HTTP format
    public function getpagemodified ($httpFormat = false) {
        $modified = strtotime($this->cpget("modified"));
        return $httpFormat ? webstatus ($modified) : $modified;
    }

    // Set last modification date, Unix time
    public function setpagerevision ($modified) {
        $this->cprevision = max($this->cprevision, $modified);
    }

    // Return last modification date, Unix time or HTTP format
    public function getpagerevision ($httpFormat = false) {
        $lastModified = max($this->cprevision, $this->getpagemodified (), $this->rcrevision(),
            $this->nlsrevision (), $this->pluginrevision());
        /* TODO I broke this logic :-(
        foreach ($this->cppages as $pages) $lastModified = max($lastModified, $pages->getModified());
        foreach ($this->cpshared as $page) $lastModified = max($lastModified, $page->cprevision ()); */

        return $httpFormat ? webstatus ($lastModified) : $lastModified;
    }

    // Return page status code, number or HTTP format
    public function getpagestatus ($httpFormat = false) {
        $statusCode = $this->cpstatus;
        if ($httpFormat) {
            $statusCode = webstatus ($statusCode);
            if ($this->havepage ("pageError")) $statusCode .= ": ".$this->get("pageError");
        }
        return $statusCode;
    }

    // Respond with error page
    public function pageerror ($statusCode, $pageError = "") {
        if (!$this->havepage ("pageError") && $statusCode>0) {
            $this->cpstatus = $statusCode;
            $this->cpset ("pageError", empty($pageError) ? "Layout error!" : $pageError);
        }
    }

    // Respond with status code, no page content
    public function cleanpage($statusCode, $location = "") {
        if (!$this->havepage ("pageClean") && $statusCode>0) {
            $this->cpstatus = $statusCode;
            $this->cprevision = 0;
            $this->_header = array();
            if (!empty($location)) {
                $this->setpageheader ("Location", $location);
                $this->setpageheader ("Cache-Control", "no-cache, no-store");
            }
            $this->cpset ("pageClean", (string)$statusCode);
        }
    }

    // Check if page is available
    public function ispageavailable () { return $this->cpavailable; }

    // Check if page is visible
    public function ispagevisible () { return $this->cpvisible; }

    // Check if page is within current HTTP request
    public function ispageactive() { return $this->cpactive; }

    // Check if page is cacheable
    public function ispagecacheable () { return $this->cpcacheable; }

    // Check if page with error
    public function ispageerror () { return $this->cpstatus>=400; }

    // Check if page setting exists
    public function havepage ($key) { return isset($this->_metadata[$key]); }

    // Check if request argument exists
    public function ispagerequest ($key) { return isset($_REQUEST[$key]); }

    // Check if response header exists
    public function ispageheader ($key) { return isset($this->_header[$key]); }

    // Check if shared page exists
    public function ispage ($key) { return isset($this->cpshared[$key]); }
}

// vim: nospell
