<?php

namespace Hawky;

class Set extends \ArrayObject {

    public function __construct () { parent::__construct(array()); }

    // Hawky interface
    public function set    ($key, $val) {        $this->offsetSet    ($key, $val); }
    public function get    ($key)       { return $this->offsetExists ($key) ? $this->offsetGet ($key) : ''; }
    public function exists ($key)       { return $this->offsetExists ($key); }

    // ArrayObject-compatible interface
    public function offsetGet    ($key)       { if (is_string ($key)) $key = lcfirst ($key); return parent::offsetGet    ($key); }
    public function offsetSet    ($key, $val) { if (is_string ($key)) $key = lcfirst ($key); parent::offsetSet     ($key, $val); }
    public function offsetUnset  ($key)       { if (is_string ($key)) $key = lcfirst ($key); parent::offsetUnset         ($key); }
    public function offsetExists ($key)       { if (is_string ($key)) $key = lcfirst ($key); return parent::offsetExists ($key); }

    // isExisting       exists

}

// vim: nospell
