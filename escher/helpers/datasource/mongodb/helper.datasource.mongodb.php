<?php

/**
 * Helper_datasource_mongodb.php
 * 
 * Datasource (MongoDB) Helper class
 * @author Andrew Detwiler <adetwiler@adidamnetworks.com>
 * @version 1.0
 * @package Escher
 */

/**
 * Datasource MongoDB Helper class
 * Note: The Escher MongoDB Datasource provides passive support for MongoDB.
 * @package Escher
 */

 class Helper_datasource_mongodb extends Helper_datasource {
	protected $mongodb;
	function __construct($args) {
		parent::__construct($args);
		$m = new Mongo("mongodb://".$args['host'].":".$args['port']);
		$this->mongodb = $m->selectDB($args['database']);
	}
	function set($model,$attrs=array(),$values=NULL,$options=array()) {
		unset($options['this']); extract($options);
		if (is_object($model)) {
			$collection = $model->_m();
			$id = (int)@$model->id;
			if (isset($attrs[$model->_m()."_id"])) { unset($attrs[$model->_m()."_id"]); $attrs['id'] = $id; }
			foreach ($model as $k => $v) {
				$attrs[$k] = $v;
			}
			$values = array();
			if (!empty($attrs)) {
				foreach($attrs as $k => $v) {
					if (!isset($model->$k)) {
						unset($attrs[$k]);
					} else {
						$values[$k] = $model->$k;
					}
				}
			}
		} elseif (is_string($model)) {
			$collection = $model;
		} else { return false; }

		// If an id is present, attempt to update
		if (isset($id)) {
			$attrs[$model->_m()."_id"] = $id;
			$this->mongodb->selectCollection($collection)->update(array($collection.'_id'=>$id),$attrs);
			$result = $this->mongodb->selectCollection($collection)->findOne(array($collection.'_id'=>$id));
			if (empty($result)) {
				$result = false;
			} else {
				$result = true;
			}
		}
		
		if (!isset($id) || !$result) {
			$m = $this->mongodb->selectCollection('last_ids')->findOne(array('collection'=>$collection));
			if (empty($m)) {
				$this->mongodb->selectCollection('last_ids')->insert(array(
					'collection'=>$collection,
					'last'=>0)
				);
				$m = $this->mongodb->selectCollection('last_ids')->findOne(array('collection'=>$collection));
			}
			$attrs[$collection.'_id'] = $id = ++$m['last'];
			$this->mongodb->selectCollection('last_ids')->update(array(
					'collection'=>$collection
				),
				array(
					'collection'=>$collection,
					'last'=>$id
				)
			);
			$this->mongodb->selectCollection($collection)->insert($attrs);
			$result = $this->mongodb->selectCollection($collection)->findOne(array($collection.'_id'=>$id));
			if (empty($result)) {
				$result = false;
			} else {
				$result = true;
			}

			if ($result) {
				if (is_object($model)) {
					$model->id = $id;	
				}
			}
		}

		if ($result) {
			// Return id on success
			return $id;
		}
		// Return false on failure
		return false;
	}

	function get($model,$conditions=array(),$options=array()) {
		unset($options['this']); extract($options);
		if (is_object($model)) {
			$collection = $model->_m();
		} elseif (is_string($model)) {
			$collection = $model;
		} else { return false; }
		
		if (array_key_exists('id',$conditions)) { $conditions[$collection.'_id'] = $conditions['id']; unset($conditions['id']); }
		if (!empty($limit) && !is_array($limit)) {
			$l = $limit;
			$limit=array();
			$limit[0] = 0;
			$limit[1] = $l;
		} else if (empty($limit)) {
			$limit=array();
			$limit[0] = 0;
			$limit[1] = 1;
		}
		$data = $this->mongodb->selectCollection($collection)->find($conditions)->skip($limit[0])->limit($limit[1]);
		$result=array();

		foreach ($data as $d) {
			unset($d['_id']);
			$d['id'] = $d[$collection.'_id']; unset($d[$collection.'_id']);
			$result[] = $d;
		}
		if (is_object($model) && !empty($result)) {
			$model->assignVars($result[0]);
		}
		return $result;
	}

	function delete($model,$id=NULL) {
		if (is_object($model)) {
			$id = $model->id;
			$model = $model->_m();
		}
		if (is_null($id)) {
			return false;
		}
		$result = $this->mongodb->selectCollection($model)->remove(array(
				$modeldbn.'_id'=>$id
			)
		);
		if ($result) {
			return true;
		}
		return false;
	}

	function getSchema($model) {
		return false;
	}
 }