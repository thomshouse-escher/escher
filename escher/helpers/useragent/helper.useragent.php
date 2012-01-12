<?php

class Helper_useragent extends Helper {
	function getClasses() {
		static $result;
		if (is_null($result)) {
			$all_browsers = array('ie','firefox','safari','chrome','iphone','android','opera');
			$ua = $_SERVER['HTTP_USER_AGENT'];
			$browser = ''; $version = '';
			$subversion = NULL;
			if (preg_match('#(Fennec|Windows CE|Smartphone|IEMobile|Opera Mini|BlackBerry)#i',$ua,$match)) {
				$browser = 'mobile';
				$version = '';
			} elseif (preg_match('#msie (\d+)#i',$ua,$match)) {
				$browser = 'ie';
				$version = $match[1];
			} elseif (preg_match('#android (\d+)#i',$ua,$match)) {
				$browser = 'android';
				$version = $match[1];
			} elseif (preg_match('#chrome/(\d+)(?:.(\d+))?#i',$ua,$match)) {
				$browser = 'chrome';
				$version = $match[1];
			} elseif (preg_match('#(?:iphone|ipod).*version/(\d+)#i',$ua,$match)) {
				$browser = 'iphone';
				$version = $match[1];
			} elseif (preg_match('#version/(\d+).*safari/#i',$ua,$match)) {
				$browser = 'safari';
				$version = $match[1];
			} elseif (preg_match('#safari/(\d+)#i',$ua,$match)) {
				$browser = 'safari';
				$version = (int)$match[1]>=412 ? 2 : 1;
			} elseif (preg_match('#(firefox|opera)/(\d+)(?:.(\d+))?#i',$ua,$match)) {
				$browser = strtolower($match[1]);
				$version = $match[2];
				if ($browser=='firefox' && $version==1) {
					$subversion = $match[3]==5 ? 5 : 0;
				}
				if ($browser=='firefox' && $version==3) {
					$subversion = in_array($match[3],array(5,6)) ? $match[3] : 0;
				}
			} else {
				$browser = 'unknown';
			}
			$result = array($browser,$browser.$version);
			if (!is_null($subversion)) {
				$result[] = $browser.$version.'-'.$subversion;
			}
			foreach($all_browsers as $b) {
				if ($browser!=$b) {
					$result[] = "no-$b";
				}
			}
			if (in_array($browser,array('iphone','android'))) {
				$result[] = 'mobile';
			}
			if (!in_array('mobile',$result)) {
				$result[] = 'no-mobile';
			}
			if (preg_match('/\(Escher Client 1.0; ([^)]+?)\s?([\d.]+)\)/i',$ua,$match)) {
				$result[] = strtolower($match[1]);
				$result[] = strtolower($match[1]).'-'.(float)$match[2];
			}
		}
		return array_unique($result);
	}
	
	function supports($feature) {
		switch($feature) {
			case 'html5': case 'video': case 'audio':
				if (self::match('ie','firefox','chrome','safari')) {
					return !self::match('ie6','ie7','ie8','firefox1','firefox2','chrome1','chrome2','safari1','safari2','safari3');
				}
				return self::match('iphone','android');
				break;
			default: return false;
		}
	}

	function match() {
		$args = func_get_args();
		$uaclasses = self::getClasses();
		foreach ($args as $arg) {
			if (is_array($arg) && call_user_func_array(array(__CLASS__,'match'),$arg)) {
				return true;
			}
			if (in_array($arg,$uaclasses)) {
				return true;
			}
		}
		return false;
	}
}