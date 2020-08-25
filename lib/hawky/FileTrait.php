<?php namespace Hawky; /**

@class FileTrait
@brief Collection of system-related methods, primarily focused on the filesystem

*/

trait FileTrait {

    // Return root locations
    public function fspagepaths ($inc_path = true) {
        $paths = array();
        $basedir = $this->rcget ('pages-rootdir');
        $rootdir = $this->rcget ("pages-defaultdir");

        if (empty ($rootdir)) { array_push ($paths, $inc_path ? "root $basedir" : 'root'); return $paths; }

        foreach (fslist ($basedir, '/.*/', true, true, false) as $entry) {
            $token = cfilename ($entry) . '/';
            if ($token == $rootdir) $token = '';
            array_push ($paths, $inc_path ? "root/$token $basedir$entry/" : "root/$token");
            exec_trace (2, "FileTrait::fspagepaths root/$token");
        }
        return $paths;

    }

    // Return file system information
    public function fsconfigure ($basedir = '', $homedir = '', $rootdir = '') {

        if (empty ($basedir)) $basedir = rtrim ($this ->rcget ('pages-rootdir'), '/');
        if (empty ($homedir)) $homedir = rtrim ($this ->rcget ('pages-homedir'), '/');
        if (empty ($rootdir)) $rootdir = $this->rcget ('site-multilingual') ? rtrim ($this ->rcget ('pages-defaultdir'), '/') : '';

        if (!empty ($rootdir)) {
            $first = ''; $token = $root = $rootdir; foreach (fslist ($basedir, '/.*/', true, true, false) as $entry) {
                if (empty ($first)) $first = $token = $entry;
                if (cfilename ($entry) !== $root) continue;
                $token = $entry; break;
            }
            $rootdir = cfilename ($token)."/";
            $basedir .= $first . '/';
        }

        if (!empty ($homedir)) {
            $first = ''; $token = $home = $homedir; foreach (fslist ($basedir, "/.*/", true, true, false) as $entry) {
                if (empty ($first)) $first = $token = $entry;
                if (cfilename ($entry) !== $home) continue;
                $token = $entry; break;
            }
            $homedir = cfilename ($token)."/";
        }

        $this->rcset ("pages-defaultdir", $rootdir);
        $this->rcset ("pages-homedir",    $homdir);

        return true;

    }

    // Check if assets file
    public function inassetsrepo ($path)
        { $a = $this ->rcget ('assets-homedir'); return mb_substr ($path, 0, mb_strlen ($a)) == $a; }

    // Check if file is valid
    public function inhawkyrepo ($path) {
        $a = $this ->rcget ('pages-rootdir' ); $x = mb_strlen ($a);
        $b = $this ->rcget ('assets-homedir'); $y = mb_strlen ($b);
        $c = $this ->rcget ('site-libdir'   ); $z = mb_strlen ($c);
        return mb_substr ($path, 0, $x) == $a || mb_substr ($path, 0, $y) == $b || mb_substr ($path, 0, $z) == $c;
    }

    // Check if system file
    public function inlibrepo ($path)
        { $a = $this ->rcget ('site-libdir'); return mb_substr ($path, 0, mb_strlen ($a)) == $a; }

    // Check if content file
    public function inpagesrepo ($path)
        { $a = $this ->rcget ('pages-rootdir'); return mb_substr ($path, 0, mb_strlen ($a)) == $a; }


}

// vim: nospell
