<?php

class Controller_uploads extends Controller {
	protected $isACLRestricted = TRUE;
	function action_index() {
		$input = Load::Input();
		$popup = !empty($input->get['popup']);
		$type = !empty($input->get['type']) ? $input->get['type'] : 'file';
		$selectfuncs = array();
		if (!empty($_FILES)) {
			foreach($_FILES['uploads']['name'] as $k => $name) {
				$upload = Load::Model('upload');
				$upload->parseUpload(array(
                    'name'     => $name,
                    'type'     => $_FILES['uploads']['type'][$k],
                    'tmp_name' => $_FILES['uploads']['tmp_name'][$k],
                    'errors'   => $_FILES['uploads']['errors'][$k],
                    'size'     => $_FILES['uploads']['size'][$k],
                ));
			}
			$headers = Load::Headers();
			$headers->redirect('./?'.($popup?'popup=true&':'')."type=$type");
		}
		if ($popup) {
			$ui = Load::UI();
			$ui->theme('popup');
			$hooks = Load::Hooks();
			$selectfuncs = $hooks->runEvent('rte_file_popup');
		}
		$uploads = array();
        $model = Load::Model('upload');
		if ($results = $model->find(array(),array('limit'=>0,'fetch'=>'all'))) {
            foreach($results as $r) {
                $r = Load::Model('upload',$r['upload_id']);
                $r->url = $r->getURL();
                if (!empty($r->resized_images)) {
                    $r->sizes = array();
                    $unsorted = array();
                    $byarea = array();
                    foreach($r->resized_images as $k => $v) {
                        $size = ($k===0) ? 'Original' : ucwords($k);
                        $unsorted[$size] = array(
                            'w' => $v[0], 'h'=> $v[1], 'filesize' => $v[2],
                            'size'=>$size, 'url' => $r->getURL($k)
                        );
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
        }
		$this->data['type'] = $type;
		$this->data['popup'] = $popup;
		$this->data['selectfuncs'] = $selectfuncs;
		$this->data['uploads'] = $uploads;
	}
}
