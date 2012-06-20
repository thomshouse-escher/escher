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
				return $data[$collection.'_id'];
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
				'order'  => array(),
				'group'  => '',
				'joins'   => array(),
			),
			$options
		);
		if (!is_array($options['select'])) {
			$options['select'] = explode(',',$options['select']);
		}
		
		if (is_object($model)) {
			$collection = $model->_m();
		} elseif (is_string($model)) {
			$collection = $model;
		} else { return false; }
		
		// Get Mongo order clause
		if (is_array($options['order'])) {
			// Interpret array notation
			$o=array();
			foreach($options['order'] as $k => $v) {
				if ($v>0) { $o[$k] = 1; }
				elseif ($v<0) { $o[$k] = -1; }
				else { $o[] = $k; }
			}
			$options['order'] = $o;
		}

		if (is_array($options['limit'])) {
			// Convert array to skip,limit
			$skip = (int)$options['limit'][0];
			$limit = (int)$options['limit'][1];
			$options['limit'] = (int)$options['limit'][1];
		} elseif ($options['limit']>0) {
			$skip = 0;
			$limit = (int)$options['limit'];
		} else { 
			$skip=0;
			$limit = 0; 
		}

		$data = $this->mongodb->selectCollection($collection)
			->find($conditions)
			->skip($skip)
			->limit($limit)
			->sort($options['order']);

		$result=array();
		foreach ($data as $d) {
			unset($d['_id']);
			$result[] = $d;
		}

		if (empty($result)) { return false; }

		if (empty($options['fetch'])) {
		    if (sizeof($options['select'])==1 && !preg_match('/[*]/',$options['select'][0])) {
		        $options['fetch'] = $options['limit']==1 ? 'one' : 'col';
		    } else {
		        $options['fetch'] = $options['limit']==1 ? 'row' : 'all';
		    }
		}

		switch ($options['fetch']) {
		    case 'one': return reset($result[0]); break;
		    case 'row': return $result[0]; break;
		    case 'col':
				if (array_key_exists(reset($options['select']),$result[0])) {
				    $k = reset($options['select']);
				} else {
				    reset($result[0]);
				    $k = key($result[0]);
				}
				$col = array();
				foreach ($result as $r) {
				   $col[] = $r[$k];
				}
				return $col;
		    break;
		    case 'all': return $result; break;
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
