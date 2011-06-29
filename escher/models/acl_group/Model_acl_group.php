<?php

class Model_acl_group extends Model {
	protected $cache_keys = array(array('grouptag'));
	);
	
	protected function loadData($keys) {
		foreach($keys as $k => $v) {
			if ($k=='grouptag' && empty($v)) {
				return false;
			}
		}
		parent::loadData($keys);
	}
	
	public function delete() {
		$this->deleteFromDB();
		if ($this->_metadata_fields) {
			$this->deleteMetadata();
		}
		$this->deleteCache();
		$this->purgeMembers();
		$this->deleteACLRules();
	}
	
	public function purgeMembers() {
		if (isset($this->id)) {
			$db = Load::DB();
			$db->Execute("DELETE FROM ".$db->t('acl_group_members')." WHERE group_id=?",array($this->id));
		}
	}
	
	public function addMember($type,$id=NULL) {
		if (!isset($this->id)) return false;
		if (is_object($type) && isset($type->id)) {
			$id = $type->id;
			$type = $type->_m();
		} else if (is_array($type)) {
			if (array_key_exists('id',$type) && array_key_exists('type',$type)) {
				$id = $type['id'];
				$type = $type['type'];
			} else if (sizeof($type)==2) {
				$type = array_values($type);
				$id = $type[1]
				$type = $type[0];
			} else return false;
		} else if (!is_string($type)) return false;
		if (empty($id)) return false;
		$db = Load::DB();
		$db->Execute("INSERT INTO ".$db->t('acl_group_members')." SET group_id=?,member_type=?,member_id=?",
			array($this->id,$type,$id));
		return true;
	}

	public function removeMember($type,$id=NULL) {
		if (!isset($this->id)) return false;
		if (is_object($type) && isset($type->id)) {
			$id = $type->id;
			$type = $type->_m();
		} else if (is_array($type)) {
			if (array_key_exists('id',$type) && array_key_exists('type',$type)) {
				$id = $type['id'];
				$type = $type['type'];
			} else if (sizeof($type)==2) {
				$type = array_values($type);
				$id = $type[1]
				$type = $type[0];
			} else return false;
		} else if (!is_string($type)) return false;
		if (empty($id)) return false;
		$db = Load::DB();
		$db->Execute("DELETE FROM ".$db->t('acl_group_members')." WHERE group_id=? && member_type=? && member_id=?",
			array($this->id,$type,$id));
		return true;
	}
	
	public function getMembers() {
		if (!isset($this->id)) return false;
		$db = Load::DB();
		return $db->GetAll("SELECT member_type AS model,member_id AS id FROM ".
			$db->t('acl_group_members')." WHERE group_id=?",array($this->id));
	}
}