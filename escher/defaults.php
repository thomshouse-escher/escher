<?php

// Data sources (read/write priority)
if (!isset($CFG['datasource_order']['all'])) { $CFG['datasource_order']['all'] = array('db'); }
// Translate Cache datasource to default Cache
if (!isset($CFG['cache']) && !empty($CFG['datasource_cache_order']['all']) && is_array($CFG['datasource_cache_order']['all'])) {
	$CFG['cache'] = $CFG['datasource_cache_order']['all'][0];
}
// Image resizes
if (!isset($CFG['resized_images'])) { $CFG['resized_images'] = array(
	'small' => array(200,200),'medium' => array(400,400),'large' => array(600,600)
); }
// Router allows arguments sent to the root controller
if (!isset($CFG['allow_root_args'])) { $CFG['allow_root_args'] = TRUE; }
// Input processor
if (!isset($CFG['input'])) { $CFG['input']['type'] = 'default'; }
// Router
if (!isset($CFG['router']) || !isset($CFG['router']['type'])) { $CFG['router']['type'] = 'static'; }
// Session
if (!isset($CFG['session'])) { $CFG['session']['type'] = 'default'; }
// Uploaded files directory
if (!isset($CFG['uploadpath'])) { $CFG['uploadpath'] = 'files'; }
// User authentication
if (!isset($CFG['userauth']['default'])) { $CFG['userauth']['default'] = array('type' =>'local'); }
// Reserved usernames
if (!isset($CFG['reserved_usernames'])) { $CFG['reserved_usernames'] = array(); }
$CFG['reserved_usernames'] = array_merge($CFG['reserved_usernames'],
	array('admin','administrator','system'));

// Predefined routes...  Changing these values might break expected system behavior
$CFG['predefined_routes'] = array(
	'login' => array('controller' => 'auth','action' => 'login'),
	'logout' => array('controller' => 'auth','action' => 'logout'),
	'signup' => array('controller' => 'auth','action' => 'signup'),
	'lockout' => array('controller' => 'auth','action' => 'lockout'),
	'uploads' => array('controller' => 'uploads')
);

// Maintenance mode message
if (!isset($CFG['maintenance_message'])) { $CFG['maintenance_message'] = 'This website is currently undergoing maintenance.'; }
// Maintenance mode routing...  login available at /maintenance/
$CFG['maintenance_mode_routes'] = array(
	'maintenance' => array('controller' => 'auth','action' => 'login')
);