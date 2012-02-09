<?php

class Helper_network extends Helper {
	protected $proxies = array('127.0.0.1');
	protected $local_ips = array('10.*','172.16-31.*','192.168.*');

	function getRemoteIP() {
		// If we don't have a remote address, return false
		if (!isset($_SERVER['REMOTE_ADDR'])) { return false; }
		// If there are no proxies or X-forwards, return REMOTE_ADDR
		if (empty($this->proxies) || empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			return $_SERVER['REMOTE_ADDR'];
		}

		// Start with REMOTE ADDR
		$ip = $_SERVER['REMOTE_ADDR'];
		// Split X-forwards to an array
		$fwds = preg_split('/\s*,\s*/',$_SERVER['HTTP_X_FORWARDED_FOR'],
			-1, PREG_SPLIT_NO_EMPTY);
		// Loop until we have our final ip
		while (in_array($ip,$this->proxy_ips) && !empty($fwds)) {
			$next = array_pop($fwds);
			if($this->isValid($next)) { $ip = $next; }
		}
		return $ip;
	}
	
	function isIntranet($ip=NULL) {
		$ip = $this->getRemoteIP();
	}

	function isValidIP($ip,$range=NULL) {
		if (preg_match('/[^\d.]/',trim($ip))) { return false; }
		$ip = preg_split('/\./',trim($ip),-1,PREG_SPLIT_NO_EMPTY);
		if (sizeof($ip)!=4) { return false; }

		if (is_null($range)) { $range = array('*'); }
		if (!is_array($range)) { $range = array($range); }

		foreach($range as $r) {
			$r = array_slice(
				preg_split('/\./',trim($r),-1,PREG_SPLIT_NO_EMPTY),
				0, 4);
			foreach($r as $pos => $o) {
				if ($o=='*') { return true; }
				if (strpos($o,'-')!==FALSE) {
					$o = explode('-',$o,2);
					if (!is_numeric($o[0]) || !is_numeric($o[1])) {
						continue 2;
					}
					if ($ip[$pos] < $o[0] || $ip[$pos] > $o[1]) {
						continue 2;
					}
				} elseif ($ip[$pos]!=$o) {
					continue 2;
				}
			}
			return true;
		}
		return false;
	}
}