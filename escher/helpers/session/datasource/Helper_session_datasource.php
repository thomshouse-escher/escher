<?php

class Helper_session_datasource extends Helper_session {
	protected $useCustomHandler = TRUE;

	
	function openHandler($save_path,$session_name) { return true; }
	function readHandler($session_id) {
		$session = Load::Model('session',array('session_id'=>$session_id));
		if ($session) {
			return $session->data;
		} else {
			return '';
		}
	}
	function writeHandler($session_id,$data) {
		$session = Load::Model('session');
		$session->load(array('session_id'=>$session_id));
		$session->touch();
		if (!empty($session->data) && !empty($session->session_id)
			&& $data==$session->data && $session_id==$session->session_id) {
				$session->cache();
		} else {
			$session->assignVars(array('session_id'=>$session_id,'data'=>$data));
			$session->save();
		}
	}
	function closeHandler() { return true; }
	function destroyHandler($id) {
		$session = Load::Model('session',array('session_id'=>$session_id));
		if ($session && $session->delete()) {
			$this->garbageHandler($this->daysToPersist*24*60*60);
			return true;
		}
		return false;
	}
	function garbageHandler($lifetime) {
		$so = Load::Model('session');
		$sessions = $so->find(array(
			'mtime'=>array('<'=>date('Y-m-d H:i:s',strtotime("-$lifetime seconds",NOW)))));
		foreach($sessions as $s) {
			$s = Load::Model('session',$s['id']);
			$s->delete();
		}
	}
	function regenerate() {
		$session = Load::Model('session',array('session_id'=>session_id()));
		$session->expire();
		session_regenerate_id();
		$session->session_id = session_id();
		$session->save();
	}
}