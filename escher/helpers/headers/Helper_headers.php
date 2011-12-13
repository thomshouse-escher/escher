<?php

abstract class Helper_headers extends Helper {
	protected $head_html = '';
	protected $head_js_tags = array();
	protected $http_headers = array();
	protected $js_tags = array();
	protected $link_tags = array();
	protected $meta_tags = array();
	protected $do_query = false;
	protected $page_titles = array();

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
		$this->meta_tags[$name] = $content;
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
		return $this->getLinkTags().$this->getMetaTags().$this->getJS(TRUE).$this->getHeadHTML().$this->getJQuery();
	}

	function getHeadHTML() {
		$response = $this->head_html;
		$this->head_html = '';
		return $response;
	}

	function getJQuery() {
		$CFG = Load::CFG();
		$ua = Load::UserAgent();
		if (!$this->jquery) { return false; }
		$response = '<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>';
		if (!is_array($this->jquery)) {
			$this->jquery = FALSE;
			return $response;
		}
		if (in_array('ui',$this->jquery)) {
			$response .= '<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1/jquery-ui.min.js"></script>';
		}
		if (in_array('theme',$this->jquery)) {
			$response .= '<link rel="stylesheet" href="'.$CFG['wwwroot'].'/public/jquery/css/escher/jquery-ui.css" />';
		}
		$this->jquery = FALSE;
		return $response;
	}

	function getJS($atHeader=FALSE) {
		$ua = Load::UserAgent();
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
			$response .= "<meta name=\"$name\" content=\"$content\" />";
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
		global $CFG;
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

	function redirect($url=NULL,$qsa=FALSE) {
		$session = Load::Session();
		$session->remember_current_request = FALSE;
		$session->preserveFlash = TRUE;
		$router = Load::Router();
		if (preg_match('/^:([\w-]+)/',$url,$match)) {
			$input = Load::Input();
			$get = $input->get($match[1]);
			if (!empty($get) && !preg_match('/[@:]/',$get)) {
				$url = $get;
			} else {
				unset($url);
			}
		}
		if (!empty($url)) {
			$url = $router->resolvePath($url);
		} elseif ($lastreq = $session->getFlash('last_request_url')) {
			$url = $lastreq;
		} else {
			$url = $router->getSitePath();
		}
		if ($qsa && !empty($_SERVER['QUERY_STRING'])) {
			$url .= ((strpos($url,'?')===FALSE) ? '?' : '&')
				.$_SERVER['QUERY_STRING'];
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
}