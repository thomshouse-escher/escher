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
	function set($model,$data=array(),$options=array()) {
		if (is_object($model)) {
			$collection = $model->_m();
			$data = get_object_vars($model);
		} elseif (is_string($model)) {
			$collection = $model;
		} else { return false; }

		// If an id is present, attempt to upsert
		if (isset($data[$collection."_id"])) {
			$id = $data[$collection."_id"];
			$result = $this->mongodb->selectCollection($collection)->update(
				array($collection.'_id'=>$id),
				$data,
				array('upsert' => TRUE,'safe' => TRUE)
			);
			return empty($result['err']);
		} else {
			// Initialize the last_id for this collection
			$this->mongodb->selectCollection('last_ids')->insert(array(
				'_id'=>$collection,
				'last'=>0
			));

			// Get the next available key and increment
			$inc = $this->mongodb->command(array(
				'findandmodify' => 'last_ids',
				'query' => array('_id' => $collection),
				'update' => array('$inc' => array('last' => 1)),
				'new' => TRUE
			));

			$data[$collection.'_id'] = $inc['value']['last'];
			$result = $this->mongodb->selectCollection($collection)->insert($data,array('safe'=>TRUE));
			$result = empty($result['err']);
			if ($result) {
				if (is_object($model)) {
					$model->{$collection.'_id'} = $data[$collection.'_id'];	
				}
				return $data['_id'];
			}
			return false;
		}
	}

	function get($model,$conditions=array(),$options=array()) {
		// Clean up the options
		$options = array_merge(
			array(
				'select' => '*',
				'limit'  => 1,
				'order'  => '',
				'group'  => '',
				'joins'   => array(),
			),
			$options
		);
		if (is_object($model)) {
			$collection = $model->_m();
		} elseif (is_string($model)) {
			$collection = $model;
		} else { return false; }

		if (empty($limit)) {
			$limit=array(0,1);
		} elseif (!is_array($limit)) {
			$limit=array(0,$limit);
		}

		$data = $this->mongodb->selectCollection($collection)
			->find($conditions)
			->skip($limit[0])
			->limit($limit[1]);

		$result=array();
		foreach ($data as $d) {
			unset($d['_id']);
			$result[] = $d;
		}
		if (is_object($model) && !empty($result) && $limit[1]==1) {
			$model->assignVars($result[0]);
		}
		return $result;
	}

	function delete($model,$id=NULL) {
		if (is_object($model)) {
			$id = $model->{$model->_m().'_id'};
			$model = $model->_m();
		}
		if (is_null($id) || !is_string($model)) {
			return false;
		}
		$result = $this->mongodb->selectCollection($model)->remove(array(
				$model.'_id'=>$id
			)
		);
		return (bool)$result;
	}

	function getSchema($model) {
		return false;
	}
 }