<?php

abstract class Helper_config extends Helper implements ArrayAccess {
	protected $init;   // Loaded from config.php
	protected $stored; // Loaded from db/cache
	protected $CFG;
	protected $db;
	protected $cache;

	function __construct() {
        global $CFG;
		$this->init = $this->CFG = $CFG;
		$this->cache = Load::Cache();
		$this->db = Load::DB();
		$this->load();
    }

    function offsetSet($offset, $value) {
        if (is_string($offset) && !array_key_exists($offset,$this->init) && !array_key_exists($offset,$this->stored)) {
            $this->CFG[$offset] = $value;
        }
    }
    function offsetExists($offset) {
        return isset($this->CFG[$offset]);
    }
    function offsetUnset($offset) {
        if (is_string($offset) && !array_key_exists($offset,$this->init) && !array_key_exists($offset,$this->stored)) {
			unset($this->CFG[$offset]);
		}
    }
    function offsetGet($offset) {
        return isset($this->CFG[$offset]) ? $this->CFG[$offset] : null;
    }

	function save($name,$value) {
		if (is_string($name) && !array_key_exists($name,$this->init)) {
			if (is_scalar($value)) {
				$this->db->replace('escher_config',array('name'=>$name,'value'=>$value,'serialized'=>0));
			} else {
				$this->db->replace('escher_config',array('name'=>$name,'value'=>serialize($value),'serialized'=>1));
			}
			$this->cache->delete('escher_config');
			$this->load();
		}
	}

	protected function load() {
		$stored_cfg = $this->cache ? $this->cache->get('escher_config') : FALSE;
		if (!$stored_cfg) {
			$result = $this->db->getAssoc('SELECT * FROM '.$this->db->t('escher_config').' ORDER BY name ASC');
			if (!empty($result)) {
				$stored_cfg = array();
				foreach($result as $k => $r) {
					if ($r['serialized']) {
						$stored_cfg[$k] = unserialize($r['value']);
					} else {
						$stored_cfg[$k] = $r['value'];
					}
				}
				if ($this->cache) {
					$this->cache->set('escher_config',$stored_cfg);
				}
			}
		}
		if ($stored_cfg) {
			$this->stored = array_diff_key($stored_cfg,$this->init);
			$this->CFG = array_merge($this->init,$this->stored,array_diff_key($this->CFG,$this->init,$this->stored));
		}
	}
}