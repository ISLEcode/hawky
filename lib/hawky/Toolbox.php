<?php

namespace Hawky;

use \Hawky\Set as HawkySet;

trait Toolbox {

    // Return location arguments from current HTTP request, modify existing arguments
    public function w3getargs ($key, $value) {

        $args = ''; $found = false; $sep = w3argsep ();
        foreach (explode ('/', w3httpget ('LOCATION_ARGUMENTS')) as $token) {
            if (!preg_match ("/^(.*?)$sep(.*)$/", $token, $matches)) continue;
            if ($matches[1] == $key) { $matches[2] = $value; $found = true; }
            if (empty ($matches[1]) || is_empty ($matches[2])) continue;
            if (!empty ($args)) $args .= '/';
            $args .= "$matches[1]:$matches[2]";
        }

        if (!$found && !empty ($key) && !is_empty ($value)) { if (!empty ($args)) $args .= '/'; $args .= "$key:$value"; }

        if (!empty($args)) {
            $args = w3urlencode ($args, false, false);
            if (!$this->isLocationArgumentsPagination($args)) $args .= '/';
        }

        return $args;

    }

    // Return location arguments from current HTTP request, convert form parameters
    public function w3getfargs () {
        $args = "";
        foreach (array_merge($_GET, $_POST) as $key=>$value) {
            if (!empty($key) && !is_empty($value)) {
                if (!empty($args)) $args .= "/";
                $key = str_replace(array("/", ":", "="), array("\x1c", "\x1d", "\x1e"), $key);
                $value = str_replace(array("/", ":", "="), array("\x1c", "\x1d", "\x1e"), $value);
                $args .= "$key:$value";
            }
        }
        if (!empty($args)) {
            $args = $this->w3urlencode ($args, false, false);
            if (!$this->isLocationArgumentsPagination($args)) $args .= "/";
        }
        return $args;
    }

    // Create text description, with or without HTML
    public function createTextDescription($text, $lengthMax = 0, $removeHtml = true, $endMarker = "", $endMarkerText = "") {
        $output = "";
        $elementsBlock = array("blockquote", "br", "div", "h1", "h2", "h3", "h4", "h5", "h6", "hr", "li", "ol", "p", "pre", "ul");
        $elementsVoid = array("area", "br", "col", "embed", "hr", "img", "input", "param", "source", "wbr");
        if ($lengthMax==0) $lengthMax = mb_strlen($text);
        if ($removeHtml) {
            $offsetBytes = 0;
            while (true) {
                $elementFound = preg_match("/<(\/?)([\!\?\w]+)(.*?)(\/?)>/s", $text, $matches, PREG_OFFSET_CAPTURE, $offsetBytes);
                $elementBefore = $elementFound ? substr($text, $offsetBytes, $matches[0][1] - $offsetBytes) : substr($text, $offsetBytes);
                $elementRawData = isset($matches[0][0]) ? $matches[0][0] : "";
                $elementStart = isset($matches[1][0]) ? $matches[1][0] : "";
                $elementName = isset($matches[2][0]) ? $matches[2][0] : "";
                if (!is_empty($elementBefore)) {
                    $rawText = preg_replace("/\s+/s", " ", html_entity_decode($elementBefore, ENT_QUOTES, "UTF-8"));
                    if (empty($elementStart) && in_array(strtolower($elementName), $elementsBlock)) $rawText = rtrim($rawText)." ";
                    if (mb_substr($rawText, 0, 1)==" " && (empty($output) || mb_substr($output, -1)==" ")) $rawText = ltrim($rawText);
                    $output .= getTextTruncated($rawText, $lengthMax);
                    $lengthMax -= mb_strlen($rawText);
                }
                if (!empty($elementRawData) && $elementRawData==$endMarker) {
                    $output .= $endMarkerText;
                    $lengthMax = 0;
                }
                if ($lengthMax<=0 || !$elementFound) break;
                $offsetBytes = $matches[0][1] + strlen($matches[0][0]);
            }
            $output = preg_replace("/\s+\…$/s", "…", $output);
        } else {
            $elementsOpen = array();
            $offsetBytes = 0;
            while (true) {
                $elementFound = preg_match("/&.*?\;|<(\/?)([\!\?\w]+)(.*?)(\/?)>/s", $text, $matches, PREG_OFFSET_CAPTURE, $offsetBytes);
                $elementBefore = $elementFound ? substr($text, $offsetBytes, $matches[0][1] - $offsetBytes) : substr($text, $offsetBytes);
                $elementRawData = isset($matches[0][0]) ? $matches[0][0] : "";
                $elementStart = isset($matches[1][0]) ? $matches[1][0] : "";
                $elementName = isset($matches[2][0]) ? $matches[2][0] : "";
                $elementEnd = isset($matches[4][0]) ? $matches[4][0] : "";
                if (!is_empty($elementBefore)) {
                    $output .= getTextTruncated($elementBefore, $lengthMax);
                    $lengthMax -= mb_strlen($elementBefore);
                }
                if (!empty($elementRawData) && $elementRawData==$endMarker) {
                    $output .= $endMarkerText;
                    $lengthMax = 0;
                }
                if ($lengthMax<=0 || !$elementFound) break;
                if (!empty($elementName) && empty($elementEnd) && !in_array(strtolower($elementName), $elementsVoid)) {
                    if (empty($elementStart)) {
                        array_push($elementsOpen, $elementName);
                    } else {
                        array_pop($elementsOpen);
                    }
                }
                $output .= $elementRawData;
                if ($elementRawData[0]=="&") --$lengthMax;
                $offsetBytes = $matches[0][1] + strlen($matches[0][0]);
            }
            $output = preg_replace("/\s+\…$/s", "…", $output);
            for ($i=count($elementsOpen)-1; $i>=0; --$i) {
                $output .= "</".$elementsOpen[$i].">";
            }
        }
        return trim($output);
    }

    // Create title from text
    public function createTextTitle($text) {
        if (preg_match("/^.*\/([\pL\d\-\_]+)/u", $text, $matches)) $text = str_replace("-", " ", ucfirst($matches[1]));
        return $text;
    }

    // Create random text for cryptography
    public function mksalt ($length, $bcryptFormat = false) {
        $dataBuffer = $salt = "";
        $dataBufferSize = $bcryptFormat ? intval(ceil($length/4) * 3) : intval(ceil($length/2));
        if (empty($dataBuffer) && function_exists("random_bytes")) {
            $dataBuffer = @random_bytes($dataBufferSize);
        }
        if (empty($dataBuffer) && function_exists("mcrypt_create_iv")) {
            $dataBuffer = @mcrypt_create_iv($dataBufferSize, MCRYPT_DEV_URANDOM);
        }
        if (empty($dataBuffer) && function_exists("openssl_random_pseudo_bytes")) {
            $dataBuffer = @openssl_random_pseudo_bytes($dataBufferSize);
        }
        if (strlen($dataBuffer)==$dataBufferSize) {
            if ($bcryptFormat) {
                $salt = substr(base64_encode($dataBuffer), 0, $length);
                $base64Chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
                $bcrypt64Chars = "./ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
                $salt = strtr($salt, $base64Chars, $bcrypt64Chars);
            } else {
                $salt = substr(bin2hex($dataBuffer), 0, $length);
            }
        }
        return $salt;
    }

    // Create hash with random salt, bcrypt or sha256
    public function mkhash ($text, $algorithm, $cost = 0) {
        $hash = "";
        switch ($algorithm) {
            case "bcrypt":  $prefix = sprintf("$2y$%02d$", $cost);
                            $salt = $this->mksalt (22, true);
                            $hash = crypt($text, $prefix.$salt);
                            if (empty($salt) || strlen($hash)!=60) $hash = "";
                            break;
            case "sha256":  $prefix = "$5y$";
                            $salt = $this->mksalt (32);
                            $hash = "$prefix$salt".hash("sha256", $salt.$text);
                            if (empty($salt) || strlen($hash)!=100) $hash = "";
                            break;
        }
        return $hash;
    }

    // Verify that text matches hash
    public function ckhash ($text, $algorithm, $hash) {
        $hashCalculated = "";
        switch ($algorithm) {
            case "bcrypt":  if (substr($hash, 0, 4)=="$2y$" || substr($hash, 0, 4)=="$2a$") {
                                $hashCalculated = crypt($text, $hash);
                            }
                            break;
            case "sha256":  if (substr($hash, 0, 4)=="$5y$") {
                                $prefix = "$5y$";
                                $salt = substr($hash, 4, 32);
                                $hashCalculated = "$prefix$salt".hash("sha256", $salt.$text);
                            }
                            break;
        }
        return $this->verifyToken($hashCalculated, $hash);
    }

    // Verify that token is not empty and identical, timing attack safe string comparison
    public function verifyToken($tokenExpected, $tokenReceived) {
        $ok = false;
        $lengthExpected = strlen($tokenExpected);
        $lengthReceived = strlen($tokenReceived);
        if ($lengthExpected!=0 && $lengthReceived!=0) {
            $ok = $lengthExpected==$lengthReceived;
            for ($i=0; $i<$lengthReceived; ++$i) {
                $ok &= $tokenExpected[$i<$lengthExpected ? $i : 0]==$tokenReceived[$i];
            }
        }
        return $ok;
    }

    // Return meta data from raw data
    public function getMetaData($rawData, $key) {
        $value = "";
        if (preg_match("/^(\xEF\xBB\xBF)?\-\-\-[\r\n]+(.+?)\-\-\-[\r\n]+(.*)$/s", $rawData, $parts)) {
            $key = lcfirst($key);
            foreach ($this->getTextLines($parts[2]) as $line) {
                if (preg_match("/^\s*(.*?)\s*:\s*(.*?)\s*$/", $line, $matches)) {
                    if (lcfirst($matches[1])==$key && !is_empty($matches[2])) {
                        $value = $matches[2];
                        break;
                    }
                }
            }
        }
        return $value;
    }

    // Set meta data in raw data
    public function setMetaData($rawData, $key, $value) {
        if (preg_match("/^(\xEF\xBB\xBF)?\-\-\-[\r\n]+(.+?)\-\-\-[\r\n]+(.*)$/s", $rawData, $parts)) {
            $found = false;
            $key = lcfirst($key);
            $rawDataMiddle = "";
            foreach ($this->getTextLines($parts[2]) as $line) {
                if (preg_match("/^\s*(.*?)\s*:\s*(.*?)\s*$/", $line, $matches)) {
                    if (lcfirst($matches[1])==$key) {
                        $rawDataMiddle .= "$matches[1]: $value\n";
                        $found = true;
                        continue;
                    }
                }
                $rawDataMiddle .= $line;
            }
            if (!$found) $rawDataMiddle .= (mb_strpos($key, "/") ? $key : ucfirst($key)).": $value\n";
            $rawDataNew = $parts[1]."---\n".$rawDataMiddle."---\n".$parts[3];
        } else {
            $rawDataNew = $rawData;
        }
        return $rawDataNew;
    }

    // @fn w3requestor
    // @brief Returns the requestor's URL
    // @param $wantarray (optional) If true returns the URL components in a named array; false by default.
    // @param $dironly   (optional) If true excludes the page's name from the returned URL; true by default.
    //
    // This function determines the full URL of the Hawky page from which the request originated. In practice however, this
    // function is mostly used to construct the return URL without the name of the invoking page — this is why `$dironly` is
    // set to _true_ by default.
    //
    // Alternatively this function can be used to collect the various components of the requestor's URL. In this case an
    // associative array is returned with the following keys: _protocol_, _domain_, _port_, _path_, and _name_.

    public function w3requestor ($wantarray = false, $dironly = true) {

        // Determine the protocol with which we have been invoked
        $protocol = (w3httpget ('REQUEST_SCHEME'        ) === 'https'
                  || w3httpget ('HTTP_X_FORWARDED_PROTO') === 'https'
                  || w3httpget ('HTTPS')                  === 'on')     ? 'https' : 'http';

        // Determine under what port we are running
        $port = w3httpget ('SERVER_PORT') || $protocol === 'http' ? 80 : 443;

        // Determine our domain name
        $domain = w3httpget ('SERVER_NAME');

            // `SERVER_NAME` provides the name of the server host under which the current script is executing.
            // If the script is running on a virtual host, this will be the value defined for that virtual host.
            //
            // Important: Under Apache 2, you must set `UseCanonicalName = On` and set `ServerName`.
            // Otherwise, this value reflects the hostname supplied by the client, which can be spoofed.

        // Determine the current page's path
        $name = $path = rtrim (w3httpget ('SCRIPT_NAME'), '/');

            // `SCRIPT_NAME` contains the current script's path. This is useful for pages which need to point to themselves.
            // The `__FILE__` constant contains the full path and filename of the current (i.e. included) file.

        // Split the path into the page's directory and its name
        if (!empty ($name) && preg_match ('/^(.*)\/([^\/]*)$/', $path, $parts)) { $path = $matches[1]; $name = $matches[2]; }

        // Make sure we have a non-empty page name
        if (empty ($name)) $name = $this ->rcget ('site-indexfile');

        // If caller requests individual parts, return them in an array
        if ($wantarray)
            return array ('protocol' => $protocol, 'domain' => $domain, 'port' => $port, 'path' => $path, 'name' => $name);

        // Append port to domain name where necessary
        if (!(($protocol === 'http' && $port === 80) || ($protocol === 'https' && $port === 443))) $domain .= ":$port";

        // We're done... return URL to caller
        $url = "$protocol://$domain$path/"; return $dironly ? $url : $url . '/' . $name;

    }

    // Detect server location
    public function w3requesturi () {

        if (!isset ($_SERVER['REQUEST_URI'])) return w3httpget ('LOCATION');

        $uri = $_SERVER['REQUEST_URI'];
        $uri = cpath (rawurldecode (($pos = mb_strpos ($uri, '?')) ? mb_substr ($uri, 0, $pos) : $uri), true);
        $sep = w3argsep ();

        if (preg_match ('/^(.*?\/)([^\/]+$sep.*)$/', $uri, $parts))
            { $_SERVER['LOCATION'] = $uri; $_SERVER['LOCATION_ARGUMENTS'] = ''; return w3httpget ('LOCATION'); }

        $_SERVER['LOCATION'] = $uri = $parts[1];
        $_SERVER['LOCATION_ARGUMENTS'] = $parts[2];

        foreach (explode ('/', $parts[2]) as $token) {
            if (!preg_match ('/^(.*?)$sep(.*)$/', $token, $matches)) continue;
            if (empty ($matches[1]) || is_empty ($matches[2])) continue;
            $matches[1] = str_replace (array ('\x1c', '\x1d', '\x1e'), array ('/', ':', '='), $matches[1]);
            $matches[2] = str_replace (array ('\x1c', '\x1d', '\x1e'), array ('/', ':', '='), $matches[2]);
            $_REQUEST[$matches[1]] = $matches[2];
        }

        return w3httpget ('LOCATION');

    }

    // @fn w3software
    // @brief Detect server name and version
    //
    // Server identification string, given in the headers when responding to requests.

    public function w3software () {

        $sw = w3httpget ('SERVER_SOFTWARE');
        if (preg_match ('/^(\S+)\/(\S+)/', $sw, $m)) return array ($m[1], $m[2],   PHP_VERSION);
        if (preg_match ('/^(\pL+)/u',      $sw, $m)) return array ($m[1], 'x.x.x', PHP_VERSION);
        return array ('CLI', PHP_VERSION, PHP_VERSION);

    }

    // Detect browser language
    public function ualang ($langs, $lang) {
        foreach (preg_split('/\s*,\s*/', w3httpget ('HTTP_ACCEPT_LANGUAGE')) as $string) {
            list ($l, $dummy) = getTextList ($string, ';', 2);
            if (!empty ($l) && in_array ($l, $langs)) { $lang = $l; break; }
        }
        return $lang;
    }

    public function imginfo ($file, $type = '') {

        $w = $h = 0; $fh = @fopen ($file, 'rb');

        // Make sure we have something to process
        if (!$fh) return array ($w, $h, $type);

        // Make sure we have a file type to analyse and handle it accordingly
        if (empty ($type)) $type = $this->getFileType ($file); switch ($type) {

        case 'gif':
            $magic = fread ($fh, 6);
            $about = fread ($fh, 7);
            if (!feof ($fh) && ($magic == 'GIF87a' || $magic == 'GIF89a')) {
                $w = (ord ($about[1]) << 8) + ord ($about[0]);
                $h = (ord ($about[3]) << 8) + ord ($about[2]);
            }
            break;

        case 'jpg':
            $max  = filesize ($file);
            $size = min ($max, 4096);
            if ($size) $buffer = fread ($fh, $size);
            $magic = substr ($buffer, 0, 4);
            if (!feof ($fh) && ($magic == "\xff\xd8\xff\xe0" || $magic == "\xff\xd8\xff\xe1")) {
                for ($pos = 2; $pos + 8 < $size; $pos += $length) {
                    if ($buffer[$pos] != "\xff") break;
                    if ($buffer[$pos + 1] == "\xc0" || $buffer[$pos + 1] == "\xc2") {
                        $w = (ord ($buffer[$pos + 7]) << 8) + ord ($buffer[$pos + 8]);
                        $h = (ord ($buffer[$pos + 5]) << 8) + ord ($buffer[$pos + 6]);
                        break;
                    }
                    $length = (ord ($buffer[$pos + 2]) << 8) + ord ($buffer[$pos + 3]) + 2;
                    while ($pos + $length + 8 >= $size) {
                        if ($size == $max) break;
                        $buffer = min ($max, $size * 2) - $size;
                        $size += $buffer;
                        $buffer = fread ($fh, $buffer);
                        if (feof ($fh) || $buffer === false) {
                            $size = 0;
                            break;
                        }
                        $buffer .= $buffer;
                    }
                }
            }
            break;

        case 'png':
            $magic = fread ($fh,  8);
            $about = fread ($fh, 16);
            if (!feof ($fh) && $magic == "\x89PNG\r\n\x1a\n") {
                $w = (ord ($about[10]) << 8) + ord ($about[11]);
                $h = (ord ($about[14]) << 8) + ord ($about[15]);
            }
            break;

        case 'svg':
            $max = filesize ($file);
            $size = min ($max, 4096);
            if ($size) $buffer = fread ($fh, $size);
            if (!feof ($fh) && preg_match ("/<svg(\s.*?)>/s", $buffer, $matches)) {
                if (preg_match ("/\swidth=\"(\d+)\"/s",  $matches[1], $tokens)) $w = $tokens[1];
                if (preg_match ("/\sheight=\"(\d+)\"/s", $matches[1], $tokens)) $h = $tokens[1];
            }
            break;

        }

        fclose ($fh);

        return array ($w, $h, $type);

    }

    // Normalise elements and attributes in HTML/SVG data
    public function normaliseData($text, $type = "html", $filterStrict = true) {
        $output = "";
        $elementsHtml = array(
            "a", "abbr", "acronym", "address", "area", "article", "aside", "audio", "b", "bdi", "bdo", "big", "blink", "blockquote", "body", "br", "button", "canvas", "caption", "center", "cite", "code", "col", "colgroup", "content", "data", "datalist", "dd", "decorator", "del", "details", "dfn", "dir", "div", "dl", "dt", "element", "em", "fieldset", "figcaption", "figure", "font", "footer", "form", "h1", "h2", "h3", "h4", "h5", "h6", "head", "header", "hgroup", "hr", "html", "i", "iframe", "image", "img", "input", "ins", "kbd", "label", "legend", "li", "main", "map", "mark", "marquee", "menu", "menuitem", "meta", "meter", "nav", "nobr", "ol", "optgroup", "option", "output", "p", "pre", "progress", "q", "rp", "rt", "ruby", "s", "samp", "section", "select", "shadow", "small", "source", "spacer", "span", "strike", "strong", "style", "sub", "summary", "sup", "table", "tbody", "td", "template", "textarea", "tfoot", "th", "thead", "time", "title", "tr", "track", "tt", "u", "ul", "var", "video", "wbr");
        $elementsSvg = array(
            "svg", "altglyph", "altglyphdef", "altglyphitem", "animatecolor", "animatemotion", "animatetransform", "circle", "clippath", "defs", "desc", "ellipse", "feblend", "fecolormatrix", "fecomponenttransfer", "fecomposite", "feconvolvematrix", "fediffuselighting", "fedisplacementmap", "fedistantlight", "feflood", "fefunca", "fefuncb", "fefuncg", "fefuncr", "fegaussianblur", "femerge", "femergenode", "femorphology", "feoffset", "fepointlight", "fespecularlighting", "fespotlight", "fetile", "feturbulence", "filter", "font", "g", "glyph", "glyphref", "hkern", "image", "line", "lineargradient", "marker", "mask", "metadata", "mpath", "path", "pattern", "polygon", "polyline", "radialgradient", "rect", "stop", "switch", "symbol", "text", "textpath", "title", "tref", "tspan", "use", "view", "vkern");
        $attributesHtml = array(
            "accept", "action", "align", "allowfullscreen", "alt", "autocomplete", "background", "bgcolor", "border", "cellpadding", "cellspacing", "charset", "checked", "cite", "class", "clear", "color", "cols", "colspan", "content", "controls", "coords", "crossorigin", "datetime", "default", "dir", "disabled", "download", "enctype", "face", "for", "frameborder", "headers", "height", "hidden", "high", "href", "hreflang", "id", "integrity", "ismap", "label", "lang", "list", "loop", "low", "max", "maxlength", "media", "method", "min", "multiple", "name", "noshade", "novalidate", "nowrap", "open", "optimum", "pattern", "placeholder", "poster", "prefix", "preload", "property", "pubdate", "radiogroup", "readonly", "rel", "required", "rev", "reversed", "role", "rows", "rowspan", "spellcheck", "scope", "selected", "shape", "size", "sizes", "span", "srclang", "start", "src", "srcset", "step", "style", "summary", "tabindex", "target", "title", "type", "usemap", "valign", "value", "width", "xmlns");
        $attributesSvg = array(
            "accent-height", "accumulate", "additivive", "alignment-baseline", "ascent", "attributename", "attributetype", "azimuth", "basefrequency", "baseline-shift", "begin", "bias", "by", "class", "clip", "clip-path", "clip-rule", "color", "color-interpolation", "color-interpolation-filters", "color-profile", "color-rendering", "cx", "cy", "d", "datenstrom", "dx", "dy", "diffuseconstant", "direction", "display", "divisor", "dur", "edgemode", "elevation", "end", "fill", "fill-opacity", "fill-rule", "filter", "flood-color", "flood-opacity", "font-family", "font-size", "font-size-adjust", "font-stretch", "font-style", "font-variant", "font-weight", "fx", "fy", "g1", "g2", "glyph-name", "glyphref", "gradientunits", "gradienttransform", "height", "href", "id", "image-rendering", "in", "in2", "k", "k1", "k2", "k3", "k4", "kerning", "keypoints", "keysplines", "keytimes", "lang", "lengthadjust", "letter-spacing", "kernelmatrix", "kernelunitlength", "lighting-color", "local", "marker-end", "marker-mid", "marker-start", "markerheight", "markerunits", "markerwidth", "maskcontentunits", "maskunits", "max", "mask", "media", "method", "mode", "min", "name", "numoctaves", "offset", "operator", "opacity", "order", "orient", "orientation", "origin", "overflow", "paint-order", "path", "pathlength", "patterncontentunits", "patterntransform", "patternunits", "points", "preservealpha", "preserveaspectratio", "r", "rx", "ry", "radius", "refx", "refy", "repeatcount", "repeatdur", "restart", "result", "rotate", "scale", "seed", "shape-rendering", "specularconstant", "specularexponent", "spreadmethod", "stddeviation", "stitchtiles", "stop-color", "stop-opacity", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke", "stroke-width", "style", "surfacescale", "tabindex", "targetx", "targety", "transform", "text-anchor", "text-decoration", "text-rendering", "textlength", "type", "u1", "u2", "unicode", "values", "viewbox", "visibility", "vert-adv-y", "vert-origin-x", "vert-origin-y", "width", "word-spacing", "wrap", "writing-mode", "xchannelselector", "ychannelselector", "x", "x1", "x2", "xlink:href", "xml:id", "xml:space", "xmlns", "y", "y1", "y2", "z", "zoomandpan");
        $elementsSafe = $elementsHtml;
        $attributesSafe = $attributesHtml;
        if ($type=="svg") {
            $elementsSafe = array_merge($elementsHtml, $elementsSvg);
            $attributesSafe = array_merge($attributesHtml, $attributesSvg);
        }
        $offsetBytes = 0;
        while (true) {
            $elementFound = preg_match("/<(\/?)([\!\?\w]+)(.*?)(\/?)>/s", $text, $matches, PREG_OFFSET_CAPTURE, $offsetBytes);
            $elementBefore = $elementFound ? substr($text, $offsetBytes, $matches[0][1] - $offsetBytes) : substr($text, $offsetBytes);
            $elementStart = $elementFound ? $matches[1][0] : "";
            $elementName = $elementFound ? $matches[2][0]: "";
            $elementMiddle = $elementFound ? $matches[3][0]: "";
            $elementEnd = $elementFound ? $matches[4][0]: "";
            $output .= $elementBefore;
            if (substr($elementName, 0, 1)=="!") {
                $output .= "<$elementName$elementMiddle>";
            } elseif (in_array(strtolower($elementName), $elementsSafe)) {
                $elementAttributes = $this->getTextAttributes($elementMiddle);
                foreach ($elementAttributes as $key=>$value) {
                    if (!in_array(strtolower($key), $attributesSafe) && !preg_match("/^(aria|data)-/i", $key)) {
                        unset($elementAttributes[$key]);
                    }
                }
                if ($filterStrict) {
                    $href = isset($elementAttributes["href"]) ? $elementAttributes["href"] : "";
                    if (preg_match("/^\w+:/", $href) && !preg_match("/^(http|https|ftp|mailto):/", $href)) {
                        $elementAttributes["href"] = "error-xss-filter";
                    }
                    $href = isset($elementAttributes["xlink:href"]) ? $elementAttributes["xlink:href"] : "";
                    if (preg_match("/^\w+:/", $href) && !preg_match("/^(http|https|ftp|mailto):/", $href)) {
                        $elementAttributes["xlink:href"] = "error-xss-filter";
                    }
                }
                $output .= "<$elementStart$elementName";
                foreach ($elementAttributes as $key=>$value) $output .= " $key=\"$value\"";
                if (!empty($elementEnd)) $output .= " ";
                $output .= "$elementEnd>";
            }
            if (!$elementFound) break;
            $offsetBytes = $matches[0][1] + strlen($matches[0][0]);
        }
        return $output;
    }

    // Normalise text lines, convert line endings
    public function fileformat ($text, $eol = 'lf') {
        return ($eol=="lf") ? preg_replace ('/\R/u', '\n', $text) : preg_replace ('/\R/u', '\r\n', $text);
    }

    public function timer (&$time, $start = true)
    { $time = $start ? microtime(true) : intval((microtime(true)-$time) * 1000); }

    // Check if there are location arguments in current HTTP request
    public function isLocationArguments($location = '') {
        if (empty($location)) $location = w3httpget ('LOCATION').w3httpget ('LOCATION_ARGUMENTS');
        $separator = w3argsep ();
        return preg_match("/[^\/]+$separator.*$/", $location);
    }

    // Check if there are pagination arguments in current HTTP request
    public function isLocationArgumentsPagination($location) {
        $separator = w3argsep ();
        return preg_match("/^(.*\/)?page$separator.*$/", $location);
    }

    // Check if unmodified since last HTTP request
    public function isNotModified($lastModifiedFormatted) {
        return w3httpget ('HTTP_IF_MODIFIED_SINCE') == $lastModifiedFormatted;
    }

}

// vim: nospell
