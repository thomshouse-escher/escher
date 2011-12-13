<?php

abstract class Helper_ui extends Helper {
	protected $theme;
	protected $directOutput = FALSE;
	protected $siteTitle = 'Untitled Website';
	protected $siteSubtitle = '';
	protected $menus = array();
	protected $nullUrl = '#';
	protected $inputStatus = array();
	
	function __construct($args=array()) {
		parent::__construct($args);
		global $CFG;
		if (isset($args['menus'])) {
			$this->menus = $args['menus'];
		}
		if (!isset($args['menus']['main'])) {
			$this->menuLoadFromRoutes('main');
		}
		$this->theme = $CFG['theme'];
		if (!empty($CFG['title'])) {
			$this->siteTitle = $CFG['title'];
		}
		if (!empty($CFG['subtitle'])) {
			$this->siteSubtitle = $CFG['subtitle'];
		}
	}
	
	function directOutput($bool=NULL) {
		if (!is_null($bool)) {
			$this->directOutput = (bool)$bool;
		}
		return $this->directOutput;
	}
	
	function menuAddItem($title,$url,$menu='main') {
		if (!isset($this->menus[$menu])) { $this->menus[$menu] = array(); }
		$this->menus[$menu][] = array('title' => $title,'url' => $url);
	}
	function menuAddSubmenu($title,$url,$submenu,$menu='main') {
		if (!isset($this->menus[$menu])) { $this->menus[$menu] = array(); }
		if (empty($url)) { $url = $this->nullUrl; }
		$this->menus[$menu][] = array('title' => $title,'url' => $url,'submenu' => $submenu);
	}
	function menuDisplay($menu,$preMenu='<ul>',$postMenu='</ul>',$preItem='<li>',$postItem='</li>',$preSubmenu='<ul>',$postSubmenu='</ul>') {
		$result = $preMenu;
		if (is_array(@$this->menus[$menu])) {
			foreach($this->menus[$menu] as $item) {
				$result .= $preItem;
				$result .= '<a href="'.$item['url'].'">'.$item['title'].'</a>';
				if (!empty($item['submenu'])) {
					$result .= $this->menuDisplay($item['submenu'],$preSubmenu,$postSubmenu,$preItem,$postItem,$preSubmenu,$postSubmenu);
				}
				$result .= $postItem;
			}
		}
		if ($this->directOutput) { echo $result.$postMenu; }
		return $result.$postMenu;
	}
	function menuLoadFromRoutes($menu) {
		global $CFG;
		if (!isset($this->menus[$menu])) { $this->menus[$menu] = array(); }
		if (!empty($CFG['root']['title'])) {
			$this->menus[$menu][] = array('title'=>$CFG['root']['title'],'url'=>$CFG['wwwroot'].'/');
		}
		foreach($CFG['static_routes'] as $url => $r) {
			if (!empty($r['title']) && !preg_match('#(\*|\[[^\]]+\])#',$url)) {
				$this->menus[$menu][] = array(
					'title' => $r['title'],
					'url' => $CFG['wwwroot'].'/'.preg_replace(array('#^/#','#/$#'),'',$url).'/');
			}
		}
	}
	function theme($theme=NULL) {
		if (!is_null($theme)) {
			$this->theme = $theme;
		}
		return $this->theme;
	}
	function subtitle($out=NULL) {
		if (is_null($out)) { $out = $this->directOutput; }
		if ($out) { echo $this->siteSubtitle; }
		return $this->siteSubtitle;
	}
	function title($out=NULL) {
		if (is_null($out)) { $out = $this->directOutput; }
		if ($out) { echo $this->siteTitle; }
		return $this->siteTitle;
	}

	function setInputStatus($name,$status='message',$message=NULL) {
		$this->inputStatus[$name] = array('status' => $status);
		if (is_string($message)) {
			$this->inputStatus[$name]['message'] = $message;
		}
	}

	function getInputStatus($fields=array(),$format=NULL) {
		if (!empty($fields) && is_array($fields)) {
			$results = array_intersect_key($this->inputStatus,array_flip($fields));
		} else {
			$results = $this->inputStatus;
		}

		if ($format == 'json') {
			return json_encode($results);
		} else {
			return $results;
		}
	}
}