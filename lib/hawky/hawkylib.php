<?php

function webstatus ($sc, $short = false) {

    switch ($sc) {
        case 0:     $text = 'No data';              break;
        case 200:   $text = 'OK';                   break;
        case 301:   $text = 'Moved permanently';    break;
        case 302:   $text = 'Moved temporarily';    break;
        case 303:   $text = 'Reload please';        break;
        case 304:   $text = 'Not modified';         break;
        case 400:   $text = 'Bad request';          break;
        case 403:   $text = 'Forbidden';            break;
        case 404:   $text = 'Not found';            break;
        case 430:   $text = 'Login failed';         break;
        case 434:   $text = 'Not existing';         break;
        case 500:   $text = 'Server error';         break;
        case 503:   $text = 'Service unavailable';  break;
        default:    $text = "Unknown status code ($sc)";
    }

    if ($short) return $text;

    $proto = w3httpget  ('SERVER_PROTOCOL');
    if (!preg_match ('/^HTTP\//', $proto)) $proto = 'HTTP/1.1';
    return "$proto $sc $text";

}


function mime ($file) {
    // TODO why not use https://www.php.net/manual/en/function.mime-content-type.php#85879
    $contentType = '';
    $contentTypes = array(
        "css" => "text/css",
        "gif" => "image/gif",
        "html" => "text/html; charset=utf-8",
        "ico" => "image/x-icon",
        "js" => "application/javascript",
        "json" => "application/json",
        "jpg" => "image/jpeg",
        "md" => "text/markdown",
        "png" => "image/png",
        "svg" => "image/svg+xml",
        "txt" => "text/plain",
        "woff" => "application/font-woff",
        "woff2" => "application/font-woff2",
        "xml" => "text/xml; charset=utf-8");
    $fileType = fsextension ($file);
    if (empty($fileType)) {
        $contentType = $contentTypes["html"];
    } elseif (array_key_exists($fileType, $contentTypes)) {
        $contentType = $contentTypes[$fileType];
    }
    return $contentType;
}

// Return number of bytes
function tobytes ($string) {
    $bytes = intval ($string); switch (mb_strtoupper (mb_substr ($string, -1))) {
        case 'G': $bytes *= 1024 * 1024 * 1024; break;
        case 'M': $bytes *= 1024 * 1024;        break;
        case 'K': $bytes *= 1024;               break;
    }   return $bytes;
}

// Return lines from text, including newline
function getTextLines($text) {
    $lines = preg_split("/\n/", $text);
    foreach ($lines as &$line) {
        $line = $line."\n";
    }
    if (is_empty($text) || mb_substr($text, -1, 1)=="\n") array_pop($lines);
    return $lines;
}

// Return attributes from text
function getTextAttributes($text) {
    $tokens = array();
    $posStart = $posQuote = 0;
    $textLength = strlen($text);
    for ($pos=0; $pos<$textLength; ++$pos) {
        if ($text[$pos]==" " && !$posQuote) {
            if ($pos>$posStart) array_push($tokens, substr($text, $posStart, $pos-$posStart));
            $posStart = $pos+1;
        }
        if ($text[$pos]=="=" && !$posQuote) {
            if ($pos>$posStart) array_push($tokens, substr($text, $posStart, $pos-$posStart));
            array_push($tokens, "=");
            $posStart = $pos+1;
        }
        if ($text[$pos]=="\"") {
            if ($posQuote) {
                if ($pos>$posQuote) array_push($tokens, substr($text, $posQuote+1, $pos-$posQuote-1));
                $posQuote = 0;
                $posStart = $pos+1;
            } else {
                if ($pos==$posStart) $posQuote = $pos;
            }
        }
    }
    if ($pos>$posStart && !$posQuote) {
        array_push($tokens, substr($text, $posStart, $pos-$posStart));
    }
    $attributes = array();
    for ($i=0; $i<count($tokens); ++$i) {
        if ($i+2<count($tokens) && $tokens[$i+1]=="=") {
            $key = $tokens[$i];
            $value = $tokens[$i+2];
            $i += 2;
        } else {
            $key = $value = $tokens[$i];
        }
        if (!is_empty($key) && !is_empty($value)) {
            $attributes[$key] = $value;
        }
    }
    return $attributes;
}

// Return array of specific size from text
function getTextList($text, $separator, $size) {
    $tokens = explode($separator, $text, $size);
    return array_pad($tokens, $size, null);
}

// Return array from text, space separated
function getTextArguments($text, $optional = "-", $sizeMin = 9) {
    $text = preg_replace("/\s+/s", " ", trim($text));
    $tokens = str_getcsv($text, " ", "\"");
    foreach ($tokens as $key=>$value) {
        if ($value==$optional) $tokens[$key] = "";
    }
    return array_pad($tokens, $sizeMin, null);
}

// Return text from array, space separated
function getTextString($array, $padchar = '-') {
    $text = ''; foreach ($array as $item) {
        if (preg_match ('/\s/', $item)) $item = "\"$item\"";
        if (empty ($item)) $item = $padchar;
        if (!empty ($text)) $text .= ' ';
        $text .= $item;
    }   return $text;
}

// Return number of words in text
function getTextWords($text) {
    $text = preg_replace("/([\p{Han}\p{Hiragana}\p{Katakana}]{3})/u", "$1 ", $text);
    $text = preg_replace("/(\pL|\p{N})/u", "x", $text);
    return str_word_count($text);
}

// Return text truncated at word boundary
function getTextTruncated($text, $lengthMax) {
    if (mb_strlen($text)>$lengthMax-1) {
        $text = mb_substr($text, 0, $lengthMax);
        $pos = mb_strrpos($text, " ");
        $text = mb_substr($text, 0, $pos ? $pos : $lengthMax-1)."…";
    }
    return $text;
}

//! @fn     fssplit
//! @brief  Split a filesystem path into its various components
//! @param  $path The path to be split
//! @return An associative array of all found components
//!
//! Rather than issuing multiple regular expressions to parse the various components of a filesystem path, we provide this
//! utility function; it will identify all components and accordingly populate an associative array with the following keys:
//! _path_ (the path), _tag_ (the sorting prefixed, suffixed with a dash), _name_ (the basename), and _ext_ (the extension,
//! prefixed with a dot).
//!
//! The tag component is here to support a common practice, especially for blogs, whereby a numeric prefix such as a
//! timestamp is used to have automatically sorted lists (using the underlying filesystem's sorting scheme).
//!
//! This function also allows to detect URI-like bookmarks introduced by a hash mark, or query strings introduced by a
//! question mark. If detected the returned associated array with have either the _bookmark_ or _query_ string populated.

// Return location arguments from current HTTP request
function w3getargs () { return w3httpget ("LOCATION_ARGUMENTS"); }

function w3cookie  ($key) { return isset($_COOKIE[$key]) ? $_COOKIE[$key] : ''; }
function w3httpget ($key) { return isset($_SERVER[$key]) ? $_SERVER[$key] : ''; }

function fssplit ($path) {

    // Enact the split through a unique regular expression
    preg_match ('/^(?:(.*)\/)?(?:(\d+(?:[.-]\d+)*)-)?([^\/#?]*)\.([^\/.#?]*)?(?:#(.*)|\?(.*))?$/', $path, $matches);

    // Populate an associative array accordingly
    $parts = array ();
    $parts ['path'] = $matches [1]; $parts ['tag'] = $matches [2]; $parts ['bookmark'] = $matches [5];
    $parts ['name'] = $matches [3]; $parts ['ext'] = $matches [4]; $parts ['query'   ] = $matches [6];

    // We're done... return results to caller
    return $parts;

}

// Normalise URL, make absolute URL
function absurl ($scheme, $address, $base, $location, $filterStrict = true) {
    if (!preg_match("/^\w+:/", $location)) {
        $url = "$scheme://$address$base$location";
    } else {
        if ($filterStrict && !preg_match("/^(http|https|ftp|mailto):/", $location)) $location = "error-xss-filter";
        $url = $location;
    }
    return $url;
}

// Return URL information
function urlinfo ($url) {
    $scheme = $address = $base = "";
    if (preg_match("#^(\w+)://([^/]+)(.*)$#", rtrim($url, "/"), $matches)) {
        $scheme = $matches[1];
        $address = $matches[2];
        $base = $matches[3];
    }
    return array($scheme, $address, $base);
}

// Return file or directory that matches token
function findFileDirectory($path, $token, $fileExtension, $directory, $default, &$found, &$invalid) {
    if (cfilename ($token, $fileExtension)!=$token) $invalid = true;
    if (!$invalid) {
        $regex = "/^[\d\-\_\.]*".str_replace("-", ".", $token)."$/";
        foreach (fslist ($path, $regex, false, $directory, false) as $entry) {
            if (cfilename ($entry, $fileExtension)==$token) {
                $token = $entry;
                $found = true;
                break;
            }
        }
    }
    if ($directory) $token .= "/";
    return ($default || $found) ? $token : "";
}

// Return default file in directory
function findFileDefault($path, $fileDefault, $fileExtension, $includePath = true) {
    $token = $fileDefault;
    if (!is_file($path."/".$fileDefault)) {
        $fileFolder = cfilename (basename($path), $fileExtension);
        $regex = "/^[\d\-\_\.]*($fileDefault|$fileFolder)$/";
        foreach (fslist ($path, $regex, true, false, false) as $entry) {
            if (cfilename ($entry, $fileExtension)==$fileDefault) {
                $token = $entry;
                break;
            }
            if (cfilename ($entry, $fileExtension)==$fileFolder) {
                $token = $entry;
                break;
            }
        }
    }
    return $includePath ? "$path/$token" : $token;
}

// Check if clean URL is requested
function isRequestCleanUrl($location) {
    return isset($_REQUEST["clean-url"]) && mb_substr($location, -1, 1)=="/";
}

// Check if location is specifying root
function isRootLocation($location) {
    return mb_substr($location, 0, 1)!="/";
}

// TODO This practice should be avoided
// Check if location is specifying file or directory
function isFileLocation($location) {
    return mb_substr($location, -1, 1)!="/";
}

// Normalise array, make keys with same upper/lower case
function normaliseUpperLower($input) {
    $array = array();
    foreach ($input as $key=>$value) {
        if (empty($key) || is_empty($value)) continue;
        $keySearch = mb_strtolower($key);
        foreach ($array as $keyNew=>$valueNew) {
            if (mb_strtolower($keyNew)==$keySearch) {
                $key = $keyNew;
                break;
            }
        }
        $array[$key] += $value;
    }
    return $array;
}

// Return directory location
function getDirectoryLocation($location) {
    return ($pos = mb_strrpos($location, "/")) ? mb_substr($location, 0, $pos+1) : "/";
}

// Normalise file/directory token
function cfilename ($path, $ext = '', $noext = false) {
    if (!empty ($ext)) $path = ($i = mb_strrpos ($path, '.')) ? mb_substr ($path, 0, $i) : $path;
    if (preg_match ('/^[\d\-\_\.]+(.*)$/', $path, $matches) && !empty($matches[1])) $path = $matches[1];
    return preg_replace ('/[^\pL\d\-\_]/u', '-', $path) . ($noext ? '' : $ext);
}

// Normalise name
function normaliseName($text, $removePrefix = false, $noext = false, $filterStrict = false) {
    if ($noext) $text = ($pos = mb_strrpos($text, ".")) ? mb_substr($text, 0, $pos) : $text;
    if ($removePrefix && preg_match("/^[\d\-\_\.]+(.*)$/", $text, $matches) && !empty($matches[1])) $text = $matches[1];
    if ($filterStrict) $text = mb_strtolower($text);
    return preg_replace("/[^\pL\d\-\_]/u", "-", $text);
}

// Normalise prefix
function normalisePrefix($text) {
    $prefix = "";
    if (preg_match("/^([\d\-\_\.]*)(.*)$/", $text, $matches)) $prefix = $matches[1];
    if (!empty($prefix) && !preg_match("/[\-\_\.]$/", $prefix)) $prefix .= "-";
    return $prefix;
}

// Return files and directories
function fslist ($path, $regex = '/.*/', $sort = true, $show_dirs = true, $show_path = true) {
    $items  = array();
    $handle = @opendir ($path);

    if (!$handle) return $items;

    $path = rtrim ($path, '/');

    while (($name = readdir ($handle)) !== false) {

        if (mb_substr ($name, 0, 1) ==  '.') continue;
        if (mb_substr ($name, 0, 2) == '..') continue;

        $name = tounicode ($name);
        if (!preg_match ($regex, $name)) continue;

        $full = "$path/$name";
        $item = $show_path ? $full : $name;

        if (!is_dir ($full)) { array_push ($items, $item); continue; }
        if (!$show_dirs) continue;
        array_push ($items, $item);

    }

    closedir ($handle);

    if ($sort) natcasesort ($items);

    return $items;

}

// Return files and directories recursively
function fsrlist ($path, $regex = "/.*/", $sort = true, $directories = true, $levelMax = 0) {
    --$levelMax;
    $entries = fslist ($path, $regex, $sort, $directories);
    if ($levelMax!=0) {
        foreach (fslist ($path, "/.*/", $sort, true) as $entry) {
            $entries = array_merge($entries, fslist ($entry, $regex, $sort, $directories, $levelMax));
        }
    }
    return $entries;
}

// Read file, empty string if not found
function fsread ($path, $limit = 0) {
    $fh   = @fopen ($path, 'rb');
    if ($fh) clearstatcache (true, $path); else return '';
    $size = $limit ? $limit : filesize ($path);
    $data = $size ? fread($fh, $size) : '';
    fclose ($fh);
    return $data;
}

// Create file
function fswrite ($path, $data, $mkdir = false) {
    if ($mkdir) { $path = dirname ($path); if (!empty ($path) && !is_dir ($path)) @mkdir ($path, 0777, true); }
    $fh = @fopen ($path, 'wb');
    if ($fh) clearstatcache (true, $path); else return false;
    if (flock ($fh, LOCK_EX)) { ftruncate ($fh, 0); fwrite ($fh, $data); flock ($fh, LOCK_UN); }
    fclose ($fh);
    return true;
}

// Append file
function fsappend ($path, $data, $mkdir = false) {
    $ok = false;
    if ($mkdir) { $path = dirname ($path); if (!empty ($path) && !is_dir ($path)) @mkdir ($path, 0777, true); }
    $fh = @fopen ($path, 'ab');
    if ($fh) clearstatcache (true, $path); else return false;
    if (flock ($fh, LOCK_EX)) { fwrite ($fh, $data); flock ($fh, LOCK_UN); }
    fclose($fh);
    return true;
}

// Copy file
function fscopy ($from, $to, $mkdir = false) {
    clearstatcache ();
    if ($mkdir) { $path = dirname ($to); if (!empty ($path) && !is_dir ($path)) @mkdir ($path, 0777, true); }
    return @copy ($from, $to);
}

// Rename file
function fsrename ($from, $to, $mkdir = false) {
    clearstatcache ();
    if ($mkdir) { $path = dirname ($to); if (!empty ($path) && !is_dir ($path)) @mkdir ($path, 0777, true); }
    return @rename ($from, $to);
}

// Rename directory
function fsmvdir ($from, $to, $mkdir = false) {
    return $from == $to || fsrename ($from, $to, $mkdir);
}

// Delete file
function fsunlink ($file, $trashdir = '') {
    clearstatcache ();
    if (empty ($trashdir)) $ok = @unlink($file);
    if (!is_dir ($trashdir)) @mkdir ($trashdir, 0775, true);
    $to = $trashdir . pathinfo ($file, PATHINFO_FILENAME) . '-'
        . str_replace(array (' ', ':'), '-', date ('Y-m-d H:i:s', filemtime ($file))) . '.'
        . pathinfo ($file, PATHINFO_EXTENSION);
    return @rename($file, $to);
}

// Delete directory
function fsrmdir ($path, $trashdir = '') {
    clearstatcache ();

    if (empty ($trashdir)) {
        $itrat = new RecursiveDirectoryIterator ($path, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator  ($itrat, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $f) $f ->getType () == 'dir' ? @rmdir ($f ->getPathname ()) : @unlink ($f ->getPathname ());
        return @rmdir($path);
    }

    if (!is_dir ($trashdir)) @mkdir($trashdir, 0777, true);
    $to = $trashdir . basename ($path) . '-' . str_replace (array (' ', ':'), '-', date ('Y-m-d H:i:s', filemtime ($path)));
    return @rename($path, $pathDestination);

}

// Set file modification date, Unix time
function fstouch ($path, $modified)
    { clearstatcache (true, $path); return @touch ($path, $modified); }

// Return file modification date, Unix time
function fsmtime ($path)
    { return is_file ($path) ? filemtime ($path) : 0; }

// Return file type
function fsextension($path)
    { return mb_strtolower(($pos = mb_strrpos($path, ".")) ? mb_substr($path, $pos+1) : ""); }

// Return file group
function fsgroup ($file, $path)
    { return preg_match ("#^$path(.+?)\/#", $file, $matches) ? mb_strtolower ($matches[1]) : 'none'; }


// Normalise text into UTF-8 NFC
function tounicode ($text) {
    if (PHP_OS !== 'Darwin') return $text;
    if (mb_check_encoding ($text, 'ASCII')) return $text;
    $utf8nfc = preg_match ('//u', $text) && !preg_match ('/[^\\x00-\\x{2FF}]/u', $text);
    if (!$utf8nfc) $text = iconv ('UTF-8-MAC', 'UTF-8', $text);
    return $text;
}

// Normalise location arguments
function w3urlencode ($uri, $append_slash = true, $strict = true) {

    // If so requested, append a terminal slash to the URI string
    if ($append_slash) $uri .= '/';

    // If strict mode requested, ensure URI string is all lowercased and convert all spaces to dashes
    if ($strict) $uri = str_replace (' ', '-', mb_strtolower ($uri));

    // Use platform-aware argument seperators
    $uri = str_replace(':', w3argsep (), $uri);

    // Encode URI string according to RFC 3986, except for slash, colon and equal signs
    return str_replace(array('%2F','%3A','%3D'), array('/',':','='), rawurlencode($uri));

}

// Normalise path or location, take care of relative path tokens
function cpath ($input, $slash_prefix = false) {

    $output = ''; if ($slash_prefix && mb_substr ($input, 0, 1) != '/') $output .= '/'; $len = strlen ($input);

    for ($i = 0; $i < $len; ++ $i) {

        if (!(($input[$i] == '/' || $i == 0) && $i + 1 < $len)) { $output .= $input[$i]; continue; }

        if ($input[$i + 1] == '/') continue;

        if ($input[$i + 1] == '.') {
            $j = $i + 1; while ($input[$j] == '.') ++$j;
            if ($input[$j] == '/' || $input[$j] == '') { $i = $j - 1; continue; }
        }

        $output .= $input[$i];

    }

    return $output;

}

// Return location arguments separator
function w3argsep() { return (mb_strtoupper (mb_substr (PHP_OS, 0, 3)) != 'WIN') ? ':' : "="; }

function strw3time ($time) { return gmdate('D, d M Y H:i:s', $time).' GMT'; }

// Detect server timezone
function timezone () {
    $tz = @date_default_timezone_get(); if (!(PHP_OS == 'Darwin' || $tz == 'UTC')) return $tz;
    return preg_match ('#zoneinfo/(.*)#', @readlink ('/etc/localtime'), $matches) ? $matches[1] : $tz;
}


// Check if string is empty
function is_empty ($string) { return is_null($string) || $string===""; }

function exec_traced ($level = 1) {

    // Make sure execution tracing has been configured
    if (!defined ('DEBUG')) return 0; return (DEBUG >= $level);

}

// exec_trace (3, "%s", 'Hello world');
// exec_trace ('Hello world');
function exec_trace () {

    // Make sure execution tracing has been configured
    if (!defined ('DEBUG')) return;

    // Collect command line arguments, and make sure these appear sensible
    $argc = func_num_args (); $argv = func_get_args (); if ($argc == 0 || ($argc == 1 && is_int ($argv[0]))) return;

    // Assert the requested trace's verbosity level
    $level = is_int ($argv[0]) ? array_shift ($argv) : 1; if (DEBUG < $level) return;

    // Output the requested execution trace
    $format = array_shift ($argv); error_log (vsprintf ('trace('.$level.'): '.$format, $argv));

}
