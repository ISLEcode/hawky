<?php

namespace Hawky;

use \Hawky\Set as HawkySet;

trait UserTrait {

    // Save user settings to file
    protected function uappend ($id, $profile, $path = '') {

        if (empty ($path)) $path = $this ->udbpath ();

        $this->_users_ts = time();
        $field = $this ->rcget ('user-idfield');

        $record = new HawkySet();
        $record[$field] = $id;
        foreach ($profile as $key=>$value) {
            if (empty ($key) || empty ($value)) continue;
            $this->usetkey ($key, $value, $id);
            $record[$key] = $value;
        }

        $data = fsread  ($path);
        $data = $this->yamlset ($data, $field, $id, $record);
        return fswrite   ($path, $data);

    }

    // Set current user
    protected function ucurrent ($id = '')
        { $this->_currentuser = $id; }

    // Load user settings from file
    protected function udbload ($path = '') {

        if (empty ($path)) $path = $this ->udbpath ();

        exec_trace (2, "HawkyUser::load file:$path<br/>");

        $this->_users_ts = fsmtime ($path);
        $data = fsread ($path);
        $this->_users = $this->yamlget ($data, $this ->rcget ('user-idfield'));

    }

    protected function udbpath () {
        $path = rtrim ($this ->rcget ('config-homedir'), '/');
        $file = $this ->rcget ('config-userdb');
        return $path . '/' . $file;
    }

    // Remove user settings from file
    protected function udelete ($id, $path = '') {

        // Make sure we have a valid user id to process
        if (!isset ($id) || !isset ($this->_users [$id])) return true;

        if (empty ($path)) $path = $this ->udbpath ();

        // Delete user and update inmemory database's timestamp
        $this->_users_ts = time(); unset ($this->_users [$id]);

        $field = $this ->rcget    ('user-idfield');
        $data  = fsread    ($path);
        $data  = $this->yamlunset ($data, $field, $id);
        return fswrite      ($path, $data);
    }

    // Check if user exists
    protected function uexists ($id = '')
        { if (empty ($id)) $id = $this->_currentuser; return isset ($this->_users[$id]); }

    // Return user setting
    protected function ugetkey ($key, $id = '', $encode = false) {
        if (empty ($id)) $id = $this->_currentuser;
        $value = isset ($this->_users[$id]) && isset ($this->_users[$id][$key]) ? $this->_users[$id][$key] : '';
        return $encode ? htmlspecialchars ($value) : $value;
    }

    // Check if user setting exists
    protected function uhaskey ($key, $id = '')
        { if (empty ($id)) $id = $this->_currentuser; return isset ($this->_users[$id]) && isset ($this->_users[$id][$key]); }

    protected function urecord ($id = '') {
        $settings = array();
        if (empty ($id)) $id = $this->_currentuser;
        if (isset ($this->_users[$id])) $settings = $this->_users[$id]->getArrayCopy();
        return $settings;
    }

    // Return user settings modification date, Unix time or HTTP format
    protected function urevision ($encode = false)
        { return $encode ? strw3time ($this->_users_ts) : $this->_users_ts; }

    // Set user setting
    protected function usetkey ($key, $value, $id) {
        if (!isset($this->_users[$id])) $this->_users[$id] = new HawkySet();
        $this->_users[$id][$key] = $value;
    }

}

// vim: nospell
