<?php

class Helper_headers extends Helper {
	protected $head_html = '';
	protected $foot_html = '';
	protected $head_js_tags = array();
	protected $http_headers = array();
	protected $jquery = FALSE;
	protected $js_tags = array();
	protected $link_tags = array();
	protected $meta_tags = array();
	protected $do_query = false;
	protected $page_titles = array();

	protected $meta_comma_separated = array('robots','googlebot','msnbot');
	protected $meta_html5 = array('charset');
	protected $meta_http_equiv = array(
		'accept','allow','cache-control','content-encoding','content-language',
		'content-length','content-type','date','expires','last-modified','location',
		'pragma','refresh','set-cookie','window-target','www-authenticate',
	);

	function addHTTP($string,$replace=TRUE,$response_code=NULL) {
		$this->http_headers[] = array($string,$replace,$response_code);
	}
	
	function addFootHTML($string) {
		$this->foot_html .= "$string\n";
	}
	
	function addLink($rel,$href,$type=NULL,$title=NULL,$attrs=array()) {
		if (!array_key_exists($rel,$this->link_tags)) {
			$this->link_tags[$rel] = array();
		}
		$this->link_tags[$rel][md5($href)] = array($href,$type,$title,$attrs);
	}
	
	function addJS($href,$inHeader=FALSE) {
		$tags = $inHeader ? 'head_js_tags' : 'js_tags';
		if (!in_array($href,$this->$tags)) {
			$this->{$tags}[] = $href;
		}
	}

	function addHeadHTML($string) {
		$this->head_html .= "$string\n";
	}
	
	function addMeta($name,$content) {
		$name = strtolower($name);
		if (!empty($this->meta_tags[$name]) && in_array($name,$this->meta_comma_separated)) {
			$this->meta_tags[$name] .= ",$content";
		} else {
			$this->meta_tags[$name] = $content;
		}
	}
	
	function addNotification($string,$status='message') {
		$types = array('message','warning','success','error');
		if (!in_array($status,$types)) {
			$status = 'message';
		}
		$session = Load::Session();
		$session->setFlash('header_notifications',json_encode(array($string,$status)),TRUE);
	}

	function addPageTitle($title) {
		if(is_scalar($title)) {
			$this->page_titles[] = $title;
		}
	}

	function setPageTitle($title) {
		$this->page_titles = array();
		if(is_scalar($title)) {
			$this->page_titles[] = $title;
		}
	}

	function getFooters() {
		return $this->getJS().$this->getFootHTML();
	}

	function getFootHTML() {
		$response = $this->foot_html;
		$this->foot_html = '';
		return $response;
	}

	function getHeaders() {
		return $this->getLinkTags()
			. $this->getMetaTags()
			. $this->getJS(TRUE)
			. $this->getHeadHTML()
			. $this->getJQuery()
		;
	}

	function getHeadHTML() {
		$response = $this->head_html;
		$this->head_html = '';
		return $response;
	}

	function getJQuery() {
		$CFG = Load::CFG();
		$ua = Load::UserAgent();
		if (empty($this->jquery)) { return false; }
		$response = '<script type="text/javascript" '
			. 'src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js">'
			. "</script>\n";
		if (!is_array($this->jquery)) {
			$this->jquery = FALSE;
			return $response;
		}
		if (in_array('ui',$this->jquery)) {
			$response .= '<script type="text/javascript" '
			. 'src="http://ajax.googleapis.com/ajax/libs/jqueryui/1/jquery-ui.min.js">'
			. "</script>\n";
		}
		$this->jquery = FALSE;
		return $response;
	}

	function getJS($atHeader=FALSE) {
		$ua = Load::UserAgent();
		$response = '';
		$tags = $atHeader ? 'head_js_tags' : 'js_tags';
		foreach($this->$tags as $js) {
			$response .= '<script type="text/javascript" src="'.$js."\" ></script>\n";
		}
		$this->$tags = array();
		return $response;
	}

	function getLinkTags() {
		$response = '';
		foreach($this->link_tags as $rel => $links) {
			foreach($links as $link) {
				$response .= '<link rel="'.$rel.'" href="'.$link[0].'" ';
				if (!is_null($link[1])) {
					$response .= 'type="'.$link[1].'" ';
				}
				if (!is_null($link[2])) {
					$response .= 'title="'.$link[2].'" ';
				}
				foreach ($link[3] as $attr => $value) {
					if (!is_numeric($attr)) {
						$response .= $attr.'="'.$value.'" ';	
					}
				}
				$response .= "/>\n";
			}
		}
		$this->link_tags = array();
		return $response;
	}
	
	function getMetaTags() {
		$response = '';
		foreach($this->meta_tags as $name => $content) {
			if (in_array($name,$this->meta_html5)) {
				$response .= "<meta {$name}=\"$content\" />\n";
			} elseif (in_array($name,$this->meta_http_equiv)) {
				$response .= "<meta http-equiv=\"$name\" content=\"$content\" />\n";
			} else {
				$response .= "<meta name=\"$name\" content=\"$content\" />\n";
			}
		}
		$this->meta_tags = array();
		return $response;
	}
	
	function getNotifications($keep=FALSE) {
		$session = Load::Session();
		$notifications = $session->getFlash('header_notifications',$keep);
		if (empty($notifications)) { return false; }
		return array_map('json_decode',array_unique($notifications));
	}

	function getTitle($order=array('page','site','subtitle'),
			$del=' | ',$pagedel=' &raquo; ',$reversepages=FALSE) {
		$CFG = Load::Config();
		$titles = array();
		foreach($order as $t) {
			switch ($t) {
				case 'site':
					if (!empty($CFG['title'])) { $titles[] = $CFG['title']; }
					break;
				case 'subtitle':
					if (!empty($CFG['subtitle'])) { $titles[] = $CFG['subtitle']; }
					break;
				case 'page':
					if (!empty($this->page_titles)) {
						$pts = $this->page_titles;
						if ($reversepages) { $pts = array_reverse($pts); }
						$titles[] = implode($pagedel,$pts);
					}
					break;
			}
		}
		return implode($del,$titles);
	}

	function getReferer($crossSite=FALSE) {
		if (!isset($_SERVER['HTTP_REFERER'])) { return; }
		$router = Load::Router();
		if (strpos($_SERVER['HTTP_REFERER'],$router->getRootPath())===0) {
			return substr($_SERVER['HTTP_REFERER'],strlen($router->getRootPath()));
		}
		if ($crossSite) { return $_SERVER['HTTP_REFERER']; }
		return;
	}

	function isAJAX() {
		return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
			&& strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
	}

	function loadJQuery() {
		$args = func_get_args();
		if (empty($args) && !is_array($this->jquery)) {
			$this->jquery = TRUE;
		} else {
			if (is_array($this->jquery)) {
				$this->jquery = array_merge($this->jquery,$args);
			} else {
				$this->jquery = $args;
			}
		}
	}

	function redirect($url=NULL,$crossSite=FALSE) {
		$session = Load::Session();
		$session->preserveFlash = TRUE;
		$router = Load::Router();
		if (!empty($url)) {
			if (!$crossSite && strpos($url,':')) {
				$url = '/';
			}
			$url = $router->resolvePath($url);
		} elseif ($referer = $this->getReferer()
			&& $referer != $router->getCurrentPath(FALSE,TRUE,TRUE)
		) {
			$url = $_SERVER['HTTP_REFERER'];
		} else {
			$url = $router->resolvePath('~/'); // Site root
		}
		$this->addHTTP("Location: $url");
		$this->sendHTTP();
		exit();
	}

	function sendHTTP() {
		foreach($this->http_headers as $h) {
			header($h[0],$h[1],$h[2]);
		}
	}

	function close($response='') {
		$size = mb_strlen($response);
		if ($size < 256) {
			$response .= str_repeat(' ',256-$size);
			$size = 256;
		}
		ignore_user_abort(true);
		$this->addHTTP('Connection: close');
		$this->addHTTP('Content-Length: '.$size);
		$this->sendHTTP();
		$session = Load::Session();
		$session->close();
		echo $response;
	}
}
