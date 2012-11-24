<?php

class Helper_useragent extends Helper {
	function getClasses() {
		static $result;
		if (is_null($result)) {
            $result = array();
			$all_browsers = array('ie','firefox','safari','chrome','iphone','android','opera');
			$ua = $_SERVER['HTTP_USER_AGENT'];
			$browser = ''; $version = '';
            $phone = false;
            $tablet = false;
			$subversion = NULL;
            if (preg_match('#chrome/(\d+)(?:.(\d+))?.*googletv#i',$ua,$match)) {
                $result[] = 'googletv';
                $browser = 'chrome';
                $version = '$match[1]';
            } elseif (preg_match('#(Fennec|Windows CE|Smartphone|IEMobile|Opera Mini|BlackBerry)#i',$ua,$match)) {
                $browser = 'mobile';
                $version = '';
            } elseif (preg_match('#msie (\d+)#i',$ua,$match)) {
                $browser = 'ie';
                $version = $match[1];
            } elseif (preg_match('#chrome/(\d+)(?:.(\d+))?#i',$ua,$match)) {
                $browser = 'chrome';
                $version = $match[1];
                if (preg_match('#Android.*Chrome/[.0-9]* Mobile#i',$ua)) {
                    $phone = true;
                } elseif (preg_match('#Android.*Chrome#i',$ua)) {
                    $tablet = true;
                }
            } elseif (preg_match('#android (\d+)#i',$ua,$match)) {
                $browser = 'android';
                $version = $match[1];
                if (preg_match('#Mobile#i',$ua)) {
                    $phone = true;
                } else {
                    $tablet = true;
                }
            } elseif (preg_match('#CriOS/(\d+)(?:.(\d+))?#i',$ua,$match)) {
                $browser = 'chrome';
                $version = $match[1];
                if (preg_match('#iPad#i',$ua)) {
                    $tablet = true;
                } else {
                    $phone = true;
                }
            } elseif (preg_match('#(?:iphone|ipod|ipad).*version/(\d+)#i',$ua,$match)) {
                $browser = 'ios';
                $version = $match[1];
                if (preg_match('#iPad#i',$ua)) {
                    $tablet = true;
                } else {
                    $phone = true;
                }
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
            }

			$result[] = $browser;
            $result[] = $browser.$version;
			if (!is_null($subversion)) {
				$result[] = $browser.$version.'-'.$subversion;
			}
            if ($browser=='ie') {
                foreach(array(6,7,8,9,10) as $v) {
                    if ($version<$v) {
                        $result[] = "ie-lt$v";
                    }
                    if ($version<=$v) {
                        $result[] = "ie-lte$v";
                    }
                    if ($version>=$v) {
                        $result[] = "ie-gte$v";
                    }
                    if ($version>$v) {
                        $result[] = "ie-gt$v";
                    }
                }
            }
            foreach($all_browsers as $b) {
                if ($b!='mobile' && $browser!=$b) {
                    $result[] = "no-$b";
                }
            }
            if ($phone || $tablet || in_array($browser,array('ios','android','mobile'))) {
                $result[] = 'mobile';
            } else {
                $result[] = 'no-mobile';
            }
            if ($phone) {
                $result[] = 'phone';
            }
            if ($tablet) {
                $result[] = 'tablet';
            }
            $result = array_unique($result);
		}
		return $result;
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