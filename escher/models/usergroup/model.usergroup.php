<?php

class Model_usergroup extends Model {
	protected $_schemaFields = array(
		'group_tag' => 'resource',
		'title'    => 'string',
	);
	protected $_schemaKeys = array(
		'group_tag' => array('type' => 'unique','fields' =>'group_tag'),
	);

	protected static $_ascendHits = array();

	const BIAS_NONE = 0;
	const BIAS_NARROW = -1;
	const BIAS_BROAD = 1;

	public function getGroups($entity,$bias=self::BIAS_NONE,$sort='generation') {
		if (is_a($entity,'Model') && !empty($entity->id)) {
			$entity = array($entity->_m(),$entity->id);
		}
		if (!is_array($entity) || sizeof($entity)!=2) { return false; }
		$tree = $this->_ascendNode($entity,$bias);
		if ($sort=='path') {
			return $tree;
		} elseif ($sort=='flat') {
			$groups = array();
			foreach($tree as $path) {
				foreach($path as $g => $v) {
					$groups[] = $v;
				}
			}
			$groups = array_unique($groups);
			return $groups;
		} else {
			$gens = array();
			foreach($tree as $path) {
				foreach($path as $g => $v) {
					$gens[$g][] = $v;
				}
			}
			foreach($gens as $k => $v) {
				$gens[$k] = array_unique($v);
			}
			return $gens;
		}
	}

	protected function _ascendNode($entity,$bias,$break=array()) {
		if ($entity[0]=='usergroup'
			&& array_key_exists($entity[1],self::$_ascendHits)
		) {
			$parents = self::$_ascendHits[$entity[1]];
		} else {
			$gm = Load::Model('usergroup_member');
			$parents = $gm->find(
				array(
					'member_type' => $entity[0],
					'member_id'   => $entity[1],
				),
				array('select'=>'group_id')
			);
			if ($entity[0]=='usergroup') {
				self::$_ascendHits[$entity[1]] = $parents;
			}
		}
		$tree = array();
		if (!empty($parents)) {
			$parents = array_diff($parents,$break);
			foreach($parents as $p) {
				$as = $this->_ascendNode(
					array('usergroup',$p),$bias,array_merge($break,array($p))
				);
				if (empty($as)) {
					$tree[] = array($p);
				} else {
					if ($bias && sizeof($as)>1) {
						$as = array_map('json_encode',$as);
						$roots = array();
						$discard = array();
						foreach($as as $a) {
							$root = end(json_decode($a));
							if (!array_key_exists($root,$roots)) {
								$roots[$root] = $a;
							} else {
								$product = $bias*(strlen($a)-strlen($roots[$root]));
								if ($product > 0) {
									$discard[] = $a;
								} elseif ($product < 0) {
									$discard[] = $roots[$root];
									$roots[$root] = $a;
								}
							}
						}
						$as = array_map('json_decode',array_diff($as,$discard));
					}
					foreach($as as $a) {
						array_unshift($a,$p);
						$tree[] = $a;
					}
				}
			}
		}
		return $tree;
	}
}