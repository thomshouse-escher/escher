<?php

class Controller_uploads extends Controller {
	protected $isACLRestricted = TRUE;
	function action_index() {
		$input = Load::Input();
		$popup = !empty($input->get['popup']);
		$type = !empty($input->get['type']) ? $input->get['type'] : 'file';
		$selectfuncs = array();
		if (!empty($_FILES)) {
			foreach($_FILES as $f) {
				$upload = Load::Model('upload');
				$upload->saveUploadedFile($f);
			}
			$headers = Load::Headers();
			$headers->redirect($this->path->current.'/?'.($popup?'popup=true&':'')."type=$type");
		}
		if ($popup) {
			$ui = Load::UI();
			$ui->theme('popup');
			$hooks = Load::Hooks();
			$selectfuncs = $hooks->runEvent('rte_file_popup');
		}
		$ds = Load::Datasource();
		$uploads = array();
		$result = $ds->get('upload',array(),array('limit'=>0));
		foreach($result as $r) {
			$r = Load::Model('upload',$r['id']);
			$r->url = $r->getWWWFilename();
			if (!empty($r->resized_images)) {
				$r->sizes = array();
				$unsorted = array();
				$byarea = array();
				foreach($r->resized_images as $k => $v) {
					$size = ($k===0) ? 'Original' : ucwords($k);
					$unsorted[$size] = array(
						'w' => $v[0], 'h'=> $v[1], 'filesize' => $v[2],
						'size'=>$size, 'url' => $r->getWWWFilename($k));
					$byarea[$size] = $v[0]*$v[1];
					if ($k=='thumb') {
						$r->thumburl = $unsorted[$size]['url'];
					}
				}
				asort($byarea);
				foreach($byarea as $k => $v) {
					$r->sizes[$k] = $unsorted[$k];
				}
			}
			$uploads[] = $r;
		}
		$this->data['type'] = $type;
		$this->data['popup'] = $popup;
		$this->data['selectfuncs'] = $selectfuncs;
		$this->data['uploads'] = $uploads;
	}
}