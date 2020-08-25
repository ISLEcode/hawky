<?php

namespace Hawky;

use \Hawky\Set          as HawkySet;

class Kernel {

    use \Hawky\ZipsTrait;
    use \Hawky\ConfTrait;
    use \Hawky\Content;
    use \Hawky\LangTrait;                         // Multi-lingual support and regional customisations
    use \Hawky\Lookup;
    use \Hawky\PageTrait;
    use \Hawky\FileTrait;
    use \Hawky\PlugTrait;
    use \Hawky\Toolbox;
    use \Hawky\UserTrait;                        // Handling of user profiles and passwords
    use \Hawky\YAML;                        // Handling of YAML files and Markdown frontmatter

    const VERSION = "0.8.20";
    const RELEASE = "0.8.15";

    public $media;          // media files


    // Trait Hawky::Content
    public $pages;                  // Scanned pages

    // Trait Hawky::Page
    public $cpproto;            // server scheme
    public $cpaddress;          // server address
    public $cpbaseurl;          // base location
    public $cpurl;              // page location
    public $cpfsname;           // content file name
    public $cprawdata;          // raw data of page
    public $cpmetaoffset;       // meta data offset
    public $cppages;            // additional pages
    public $cpshared;           // shared pages
    public $cpparser;           // content parser
    public $cpparserdata;       // content data of page
    public $cpavailable;        // page is available? (boolean)
    public $cpvisible;          // page is visible location? (boolean)
    public $cpactive;           // page is active location? (boolean)
    public $cacheable;          // page is cacheable? (boolean)
    public $cprevision;         // last modification date
    public $cpstatus;           // status code


    // Trait Hawky::Config
    public    $layoutArguments;     // layout arguments                                 (ConfTrait.php)

    protected $_assets;             // assets (aka zip stuff) inventory                     (ZipsTrait.php)
    protected $_clihandler;         // name of command line interface handler               (ConfTrait.php)
    protected $_config;             // configuration settings (aka run commands (rc))       (ConfTrait.php)
    protected $_config_ts;          // configuration settings last update                   (ConfTrait.php)
    protected $_currentlang;        // current (active) language                            (LangTrait.php)
    protected $_currentuser;        // current (active) user                                (UserTrait.php)
    protected $_defaults;           // essential and default configuration settings         (ConfTrait.php)
    protected $_header;             // pending response header                              (PageTrait.php)
    protected $_metadata;           // pending repsonde meta data                           (PageTrait.php)
    protected $_nls;                // language and regional database (aka NLS database)    (LangTrait.php)
    protected $_nls_ts;             // NLS database last update                             (LangTrait.php)
    protected $_nlsdefaults;        // essential and default NLS configuration settings     (LangTrait.php)
    protected $_output;             // pending response body content                        (PageTrait.php)
    protected $_plugins;            // plugins database                                     (PlugTrait.php)
    protected $_plugins_ts;         // plugins database last update                         (PlugTrait.php)
    protected $_users;              // user database                                        (UserTrait.php)
    protected $_users_ts;           // user database last update                            (UserTrait.php)
    protected $_webhandler;         // name of online request handler                       (ConfTrait.php)

    public function __construct () {
        $this->selfcheck ();


        // Trait Hawky::Content
        $this->pages            = array();

        // Trait Hawky::Page
        $this->_metadata   = new HawkySet ();
        $this->cppages      = array();
        $this->cpshared     = array();
        $this->_header = array();

        $this->_assets      = array();
        $this->_config      = new HawkySet();
        $this->_config_ts   = 0;
        $this->_currentlang = '';
        $this->_currentuser = '';
        $this->_defaults    = new HawkySet();
        $this->_nls         = new HawkySet();
        $this->_nls_ts      = 0;
        $this->_nlsdefaults = new HawkySet();
        $this->_plugins     = array();
        $this->_plugins_ts  = 0;
        $this->_users       = new HawkySet();
        $this->_users_ts    = 0;

        $this->rcset ('assets-homedir',             'zip',                  true);
        $this->rcset ('assets-webpath',             'zip',                  true);
        $this->rcset ('config-homedir',             'etc/hawky',            true);
        $this->rcset ('config-mainfile',            'system.ini',           true);
        $this->rcset ('config-rootdir',             'etc',                  true);
        $this->rcset ('config-userdb',              'user.ini',             true);
        $this->rcset ('downloads-extension',        '.download',            true);
        $this->rcset ('downloads-homedir',          'var/downloads',        true);
        $this->rcset ('downloads-webpath',          'var/downloads',        true);
        $this->rcset ('images-homedir',             'zip/img',              true);
        $this->rcset ('images-webpath',             'zip/img',              true);
        $this->rcset ('language',                   'en',                   true);
        $this->rcset ('layouts-default',            'default',              true);
        $this->rcset ('layouts-extension',          'php',                  true);
        $this->rcset ('layouts-homedir',            'lib/layouts',          true);
        $this->rcset ('nslfile',                    'language.ini',         true);
        $this->rcset ('pages-defaultdir',           'default',              true);
        $this->rcset ('pages-extension',            '.md',                  true);
        $this->rcset ('pages-homedir',              'home',                 true);
        $this->rcset ('pages-markup',               'markdown',             true);
        $this->rcset ('pages-pagination',           'page',                 true);
        $this->rcset ('pages-rootdir',              'pub',                  true);
        $this->rcset ('pages-sharedir',             'shared',               true);
        $this->rcset ('plugins-homedir',            'lib/plugins',          true);
        $this->rcset ('plugins-webpath',            'lib/plugins',          true);
        $this->rcset ('site-author',                'Hawky',                true);
        $this->rcset ('site-cachedir',              'var/cache',            true);
        $this->rcset ('site-email',                 'webmaster',            true);
        $this->rcset ('site-errorfile',             'error-(.*).md',        true);
        $this->rcset ('site-indexfile',             'index.md',             true);
        $this->rcset ('site-libdir',                'lib',                  true);
        $this->rcset ('site-logfile',               'hawky.log',            true);
        $this->rcset ('site-multilingual',          '0',                    true);
        $this->rcset ('site-name',                  'Hawky',                true);
        $this->rcset ('site-trash',                 'var/trash',            true);
        $this->rcset ('site-url',                   'auto',                 true);
        $this->rcset ('static-homedir',             'public',               true); # quid
        $this->rcset ('static-webpath',             'public',               true); # quid
        $this->rcset ('status',                     'public',               true);
        $this->rcset ('themes-default',             'default',              true);
        $this->rcset ('themes-homedir',             'lib/themes',           true);
        $this->rcset ('themes-webpath',             'lib/themes',           true);
        $this->rcset ('site-timezone',              'UTC',                  true);
        $this->rcset ('user-idfield',               'email',                true);

        $this->setnlsdefault ('fdate');
        $this->setnlsdefault ('fdatelong');
        $this->setnlsdefault ('fdatashort');

    }

    public function __destruct() {
        $this->shutdown();
    }

    // Check requirements
    public function selfcheck() {

        $troubleshooting = PHP_SAPI!="cli" ? "<a href=\"https://datenstrom.se/hawky/help/troubleshooting\">See troubleshooting</a>." : "";

        version_compare(PHP_VERSION, "5.6", ">=") || die("Hawky requires PHP 5.6 or higher! $troubleshooting\n");
        extension_loaded("curl") || die("Hawky requires PHP curl extension! $troubleshooting\n");
        extension_loaded("gd") || die("Hawky requires PHP gd extension! $troubleshooting\n");
        extension_loaded("exif") || die("Hawky requires PHP exif extension! $troubleshooting\n");
        extension_loaded("mbstring") || die("Hawky requires PHP mbstring extension! $troubleshooting\n");
        extension_loaded("zip") || die("Hawky requires PHP zip extension! $troubleshooting\n");
        mb_internal_encoding("UTF-8");

        if (exec_traced ()) { ini_set("display_errors", 1); error_reporting(E_ALL); }
        error_reporting(E_ALL ^ E_NOTICE); // TODO: remove later, for backwards compatibility
    }

    // Handle initialisation
    public function load() {
        $this->rcload($this->rcget('config-homedir').$this->rcget("coreConfigFile"));
        $this->udbload ();
        $this->loadlangs ($this->rcget("plugins-homedir").".*\.txt");
        $this->loadlangs ($this->rcget('config-homedir').$this->rcget("coreLanguageFile"));
        $this->loadplugin ($this->rcget("plugins-homedir"));
        date_default_timezone_set($this->rcget ("site-timezone"));
        $this->fsconfigure();
        $this->startup();
    }

    // Handle request
    public function webmain () {

        $this->timer ($time); $status = 0;

        ob_start();

        list ($protocol, $domain, $rootdir, $subpath, $name) = $this->getRequestInformation();

        $this->setpagerequestinfo ($protocol, $domain, $rootdir, $subpath, $name);
        foreach ($this->_plugins as $key=>$value) {
            if (method_exists($value["object"], "onRequest")) {
                $this->_webhandler = $key;
                $status = $value["object"]->onRequest($protocol, $domain, $rootdir, $subpath, $name);
                if ($status!=0) break;
            }
        }

        if ($status==0) {
            $this->_webhandler = 'core';
            $status = $this->processRequest ($protocol, $domain, $rootdir, $subpath, $name, true);
        }

        if ($this->havepage("pageError")) $status = $this->processRequestError();
        ob_end_flush();

        printf ('Hawky::request %s', $name);

        $this->timer ($time, false);

        if ($this->inpagesrepo ($name)) exec_trace ('Hawky::request status: %s time: %s ms', $status, $time);

        // We're done... return status to caller
        return $status;

    }

    // Process request
    public function processRequest($scheme, $address, $base, $location, $fileName, $cacheable) {

        $statusCode = 0;

        if (is_readable($fileName)) {
            if ($this->isRequestCleanUrl($location)) {
                $location = $location.$this->w3getfargs ();
                $location = $this->normaliseUrl($scheme, $address, $base, $location);
                $statusCode = $this->sendStatus(303, $location);
            }
        } else {
            if ($this->isRedirectLocation($location)) {
                $location = $this->getRedirectLocation($location);
                $location = $this->normaliseUrl($scheme, $address, $base, $location);
                $statusCode = $this->sendStatus(301, $location);
            }
        }

        if ($statusCode==0) {
            if ($this->inpagesrepo ($fileName) || !is_readable($fileName)) {
                $fileName = $this->readPage($scheme, $address, $base, $location, $fileName, $cacheable,
                    max(is_readable($fileName) ? 200 : 404, $this->cpstatus), $this->cpget ("pageError"));
                $statusCode = $this->sendPage();
            }
            else $statusCode = $this->sendFile(200, $fileName, true);
        }

        if ($this->inpagesrepo ($fileName)) exec_trace ("Hawky::processRequest file:$fileName");

        return $statusCode;
    }

    // Process request with error
    public function processRequestError() {
        ob_clean();
        $fileName = $this->readPage($this->cproto, $this->cpaddress, $this->cpbaseurl, $this->cpcurl, $this->cpfsname,
            $this->cpcacheable, $this->cpstatus, $this->cpget ('pageError'));
        $statusCode = $this->sendPage();
        exec_trace ("Hawky::processRequestError file:$fileName");
        return $statusCode;
    }

    // Read page
    public function readPage ($scheme, $address, $base, $location, $fileName, $cacheable, $statusCode, $pageError) {

        if ($statusCode>=400) {
            $locationError = $this->getHomeLocation($this->cpurl).$this->rcget("pages-sharedir");
            $fileNameError = $this->findFileFromLocation($locationError, true).$this->rcget("coreContentErrorFile");
            $fileNameError = str_replace("(.*)", $statusCode, $fileNameError);
            if (is_file($fileNameError)) {
                $rawData = $this->fsread ($fileNameError);
            } else {
                $language = $this->findLanguageFromFile($fileName, $this->rcget("language"));
                $rawData = "---\nTitle: ".$this->getnlskey ("coreError${statusCode}Title", $language)."\n";
                $rawData .= "Layout: error\n---\n".$this->getnlskey ("coreError${statusCode}Text", $language);
            }
            $cacheable = false;
        }
        else $rawData = $this->fsread ($fileName);

        // TODO: reset env in lieu of $this->page = new HawkyPage ($this);
        $this->setpagerequestinfo ($scheme, $address, $base, $location, $fileName);
        $this->parsepagedata ($rawData, $cacheable, $statusCode, $pageError);
        $this->setlang ($this->cpget ("language"));
        $this->parsepagecontent ();
        return $fileName;

    }

    // Send page response
    public function sendPage() {
        $this->parsepage ();
        $statusCode = $this->cpstatus;
        $lastModifiedFormatted = $this->getpageheader ("Last-Modified");
        if ($statusCode==200 && $this->ispagecacheable () && $this->isNotModified($lastModifiedFormatted)) {
            $statusCode = 304;
            @header(webstatus ($statusCode));
        }
        else {
            @header(webstatus ($statusCode));
            foreach ($this->_header as $key=>$value) @header("$key: $value");
            if (!is_null($this->cpoutputdata)) echo $this->cpoutputdata;
        }
        if (exec_traced ()) {
            foreach ($this->_header as $key=>$value) echo "Hawky::sendPage $key: $value<br/>\n";
            $language = $this->cpget ("language");
            $layout = $this->cpget ("layout");
            $theme = $this->cpget ("theme");
            $parser = $this->cpget ("pages-markup");
            echo "Hawky::sendPage language:$language layout:$layout theme:$theme parser:$parser<br/>\n";
        }
        return $statusCode;
    }

    // Send file response
    public function sendFile($statusCode, $fileName, $cacheable) {
        $lastModifiedFormatted = strw3time ($this->getFileModified($fileName));
        if ($statusCode==200 && $cacheable && $this->isNotModified($lastModifiedFormatted)) {
            $statusCode = 304;
            @header(webstatus ($statusCode));
        }
        else {
            @header(webstatus ($statusCode));
            if (!$cacheable) @header("Cache-Control: no-cache, no-store");
            @header("Content-Type: ".$this->getMimeContentType($fileName));
            @header("Last-Modified: ".$lastModifiedFormatted);
            echo $this->fsread ($fileName);
        }
        return $statusCode;
    }

    // Send data response
    public function sendData($statusCode, $rawData, $fileName, $cacheable) {
        @header(webstatus ($statusCode));
        if (!$cacheable) @header("Cache-Control: no-cache, no-store");
        @header("Content-Type: ".$this->getMimeContentType($fileName));
        @header("Last-Modified: ". strw3time (time()));
        echo $rawData;
        return $statusCode;
    }

    // Send status response
    public function sendStatus($sc, $url = '') {

        if (!empty ($url)) $this->cleanpage ($sc, $url);

        @header (webstatus ($sc));

        foreach ($this->_header as $key => $value) @header ("$key: $value");

        if (exec_traced ())
            foreach ($this->_header as $key=>$value) echo "Hawky::sendStatus $key: $value<br/>\n";

        return $sc;

    }

    // Handle command
    public function climain ($line = "") {
        $statusCode = 0;
        $this->timer ($time);
        list($command, $text) = $this->getCommandInformation($line);

        foreach ($this->_plugins as $key=>$value) {
            if (method_exists($value["object"], "onCommand")) {
                $this->_clihandler = $key;
                $statusCode = $value["object"]->onCommand($command, $text);
                if ($statusCode!=0) break;
            }
        }
        if ($statusCode==0 && empty($text)) {
            $lineCounter = 0;
            echo "Hawky is for people who make small websites.\n";
            foreach ($this->getCommandHelp() as $line) echo(++$lineCounter>1 ? "        " : "Syntax: ")."php hawky.php $line\n";
            $statusCode = 200;
        }
        if ($statusCode==0) {
            $this->_clihandler = "core";
            $statusCode = 400;
            echo "Hawky $command: Command not found\n";
        }
        $this->timer ($time, false);
        exec_trace ("Hawky::command status:$statusCode time:$time ms");
        return $statusCode<400 ? 0 : 1;
    }

    // Handle startup
    public function startup() {
        if ($this->isLoaded()) {
            foreach ($this->_plugins as $key=>$value)
                if (method_exists($value["object"], "onStartup")) $value["object"]->onStartup();
            foreach ($this->_plugins as $key=>$value)
                if (method_exists($value["object"], "onUpdate")) $value["object"]->onUpdate("startup");
        }
    }

    // Handle shutdown
    public function shutdown() {
        if ($this->isLoaded())
            foreach ($this->_plugins as $key=>$value)
                if (method_exists($value["object"], "onShutdown")) $value["object"]->onShutdown();
    }

    // Handle logging
    public function log($action, $message) {
        $statusCode = 0;
        foreach ($this->_plugins as $key=>$value) {
            if (method_exists($value["object"], "onLog")) {
                $statusCode = $value["object"]->onLog($action, $message);
                if ($statusCode!=0) break;
            }
        }
        if ($statusCode==0) {
            $line = date("Y-m-d H:i:s")." ".trim($action)." ".trim($message)."\n";
            $this->fsappend ($this->rcget("plugins-homedir").$this->rcget("site-logfile"), $line);
        }
    }

    // Include layout
    public function layout($name, $arguments = null) {
        $this->layoutArguments = func_get_args();
        $this->ploadlayout ($name);
    }

    // Return layout arguments
    public function getLayoutArguments($sizeMin = 9) {
        return array_pad($this->layoutArguments, $sizeMin, null);
    }

    public function getLayoutArgs($sizeMin = 9) { // TODO: remove later, for backwards compatibility
        return $this->getLayoutArguments($sizeMin);
    }

    // Return request information
    public function getRequestInformation ($protocol = '', $domain = '', $address = '') {

        if (empty ("$protocol$domain$address")) {

            $url = $this ->rcget ('site-url'); if ($url === 'auto' || $this ->climode ()) $url = $this ->w3requestor ();

            list ($protocol, $domain, $address) = urlinfo ($url);
            $this ->rcset ('site-protocol',  rtrim ($protocol, '://'));
            $this ->rcset ('site-domain',    rtrim ($domain,   '/'  ));
            $this ->rcset ('site-address',   rtrim ($address,  '/'  ));
            exec_trace (3, 'Hawky::getRequestInformation $protocol://$domain$address');

        }

        $location = mb_substr ($this->w3requesturi (), mb_strlen ($address));
        if (empty ($fileName)) $fileName = $this->findFileFromSystem($location);
        if (empty ($fileName)) $fileName = $this->zipdirof ($location);
        if (empty ($fileName)) $fileName = $this->findFileFromLocation($location);
        return array($protocol, $domain, $address, $location, $fileName);
    }

    // Return command information
    public function getCommandInformation($line = "") {
        if (empty($line)) {
            $line = getTextString(array_slice(w3httpget ("argv"), 1));
            exec_trace (3, "Hawky::getCommandInformation $line");
        }
        return getTextList($line, " ", 2);
    }

    // Return command help
    public function getCommandHelp() {
        $data = array();
        foreach ($this->_plugins as $key=>$value) {
            if (method_exists($value["object"], "onCommandHelp")) {
                foreach (preg_split("/[\r\n]+/", $value["object"]->onCommandHelp()) as $line) {
                    list($command, $dummy) = getTextList($line, " ", 2);
                    if (!empty($command) && !isset($data[$command])) $data[$command] = $line;
                }
            }
        }
        uksort($data, "strnatcasecmp");
        return $data;
    }

    // @fn webhandler
    // @brief Return the request handler (web interface)

    public function webhandler () { return $this->_webhandler; }

    // @fn clihandler
    // @brief Return the command line handler

    public function clihandler () { return $this->_clihandler; }

    // @fn climode
    // @brief Check if running through the command line interface

    public function climode () { return isset ($this->_clihandler); }

    // Check if all extensions loaded
    public function isLoaded() { return isset ($this->_plugins); }

}

// vim: nospell
