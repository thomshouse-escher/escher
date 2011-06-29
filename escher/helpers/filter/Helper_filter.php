<?php

abstract class Helper_filter extends Helper {
	function filter($value,$type='string',$default=NULL) {
		if (is_object($value)) {
			$value = get_object_vars($value);
		}
		if (is_array($value)) {
			array_walk_recursive($value,array($this,'filter_recursive'),array('type'=>$type,'default'=>$default));
			return $value;
		}
		if (!is_null($default) && (is_null($value) || $value==='')) {
			$value = $default;
		}
		if (is_array($type)) {
			foreach($type as $t) {
				$value = $this->perform_filter($value,$t);
			}
			return $value;
		} elseif (!is_string($type)) { return false; }
		$value = $this->perform_filter($value,$type);
		return $value;
	}

	protected function perform_filter($value,$type) {	
		if ($type=='none') {
			return $value;
		} elseif ($type=='string') {
			return trim((string)$value);
		
		} elseif ($type=='int') {
			if (!is_numeric($value)) {
				return NULL;
			} else {
				return (string)(int)(float)$value;
			}
		
		} elseif ($type=='decimal') {
			if (!is_numeric($value)) {
				return NULL;
			} else {
				return (float)$value;
			}

		} elseif ($type=='encode') {
			return trim(htmlentities((string)$value,ENT_QUOTES,'UTF-8',TRUE));
		} elseif ($type=='decode') {
			$utf8 = trim(html_entity_decode((string)$value,ENT_QUOTES,'UTF-8'));
			$ascii = trim(html_entity_decode((string)$value,ENT_QUOTES,'ISO-8859-1'));
			return strlen($utf8)<strlen($ascii) ? $utf8 : $ascii;
		} elseif ($type=='nohtml') {
			$value = preg_replace('/[ \n\r]+/',' ',$value);
			$value = preg_replace(array('#</p>#i','#<br( /)?>#i'),array("\n\n","\n"),$value);
			$value = trim(strip_tags($value));
			$value = preg_replace('/\n +/',"\n",$value);
			return $value;
		} elseif ($type=='nosmart') {
			$search1=array("\xe2\x80\x98","\xe2\x80\x99","\xe2\x80\x9c","\xe2\x80\x9d","\xe2\x80\x93","\xe2\x80\x94","\xe2\x80\xa6");
			$search2=array(chr(212),chr(213),chr(210),chr(211),chr(208),chr(209),chr(201));
			$search3=array(chr(145),chr(146),chr(147),chr(148),chr(150),chr(151),chr(133));
			$replace=array("'","'",'"','"','-','--','...');
			$value = str_replace($search1, $replace, $value);
			$value = str_replace($search2, $replace, $value);
			$value = str_replace($search3, $replace, $value);
			return $value;
		} elseif (in_array($type,array('timestamp','date','time','datetime','timediff'))) {
			if (is_numeric($value)) {
				$ts = (int)(float)$value;
			} else {
				$ts = strtotime($value);
			}
			if ($ts===FALSE || $ts==-1) {
				return NULL;
			} elseif ($type=='timestamp') {
				return $ts;
			} elseif ($type=='date') {
				return date('Y-m-d',$ts);
			} elseif ($type=='time') {
				return date('H:i:s',$ts);
			} elseif ($type=='timediff') {
				$units = array(
					'year' => 60*60*24*365,
					'month' => 60*60*24*30,
					'week' => 60*60*24*7,
					'day' => 60*60*24,
					'hour' => 60*60,
					'minute' => 60,
					'second' => 1);
				$td = NOW-$ts;
				foreach($units as $u => $secs) {
					$ud = floor($td/abs($secs));
					if ($ud>0) {
						return "$ud $u".($ud>1?'s ':' ').($td>0?'ago':'from now');
					}
				}
				return "right now";
			} else {
				return date('Y-m-d H:i:s',$ts);
			}
		} elseif ($type=='filesize') {
			$units = explode(' ','B KB MB GB TB PB');
			for ($i = 0; $value > 1024; $i++) {
			    $value /= 1024;
			}
			return round($value, 2).' '.$units[$i];
		} elseif ($type=='html5down') {
			$tags = "header|nav|section|article|aside|footer";
			$regex = $replace = array();
			$regex[] = "#<!doctype html>#i";
			$regex[] = "#<($tags)"."((?:\s+\w+(?:\s*=\s*(?:\".*?\"|'.*?'|[^'\">\s]+))?)+\s*|\s*)".
				"(?:\s+class\s*=\s*(?:\"(.*?)\"|'(.*?)'|([^'\">\s]+)))".
				"((?:\s+\w+(?:\s*=\s*(?:\".*?\"|'.*?'|[^'\">\s]+))?)+\s*|\s*)".">#i";
			$regex[] = "#<($tags)"."((?:\s+\w+(?:\s*=\s*(?:\".*?\"|'.*?'|[^'\">\s]+))?)+\s*|\s*)".">#i";
			$regex[] = "#<\/($tags)\s*>#i";
			$replace[] = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
			$replace[] = '<div$2 class="$1 $3$4$5"$6>';
			$replace[] = '<div class="$1"$2>';
			$replace[] = '</div>';
			return preg_replace($regex,$replace,$value);
		} else {
			$hooks = Load::Hooks();
			return $hooks->runFilter($type,$value);
		}
	}
	
	protected function filter_recursive(&$value,$key,$args) {
		$value = $this->filter($value,$args['type'],$args['default']);
	}
}