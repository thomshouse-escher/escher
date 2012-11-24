<?php

// Router allows arguments sent to the root controller
$allow_root_args = TRUE;

// Input processor
$input['type'] = 'default';

// Router
$router['type'] = 'static';

// Session
$session['type'] = 'default';

// Uploaded files directory
$uploadpath = 'files';

// User authentication
$userauth['local'] = array('type' =>'local');

// Reserved usernames
$reserved_usernames = array();

// Image resizes
$resized_images = array(
	'small' => array(200,200),
	'medium' => array(400,400),
	'large' => array(600,600),
);

// Maintenance mode defaults
$maintenance_message = 'This website is currently undergoing maintenance.';
$maintenance_root = array(
	'controller'=>'errors',
	'action'=>'maintenance',
	'args'=>array('message'=>$maintenance_message),
);
// Maintenance login available at /maintenance/
$maintenance_routes = array(
	'maintenance' => array('controller' => 'auth','action' => 'login')
);

// Placeholder for static routes
$static_routes = array();

include(ESCHER_DOCUMENT_ROOT.'/config.php');

// Predefined static routes...  Changing these might break expected system behavior
$static_routes = array_merge(
	array(
		'login' => array('controller' => 'auth','action' => 'login'),
		'logout' => array('controller' => 'auth','action' => 'logout'),
		'signup' => array('controller' => 'auth','action' => 'signup'),
		'lockout' => array('controller' => 'auth','action' => 'lockout'),
		'uploads' => array('controller' => 'uploads'),
	),
	$static_routes
);

$reserved_usernames = array_merge(
	$reserved_usernames,
	array('admin','administrator','system')
);

// Set datasource order
if (empty($datasource_order['all'])) {
	$datasource_order['all'] = array();
	foreach($datasource as $name => $ds) {
		if (!empty($ds['helper']) && $ds['helper']=='database') {
			$datasource_order['all'][] = $name;
		}
	}
	unset($ds,$name);
}

// Set cache order
if (empty($datasource_cache_order['all'])) {
	$datasource_cache_order['all'] = array();
	foreach($datasource as $name => $ds) {
		if (!empty($ds['helper']) && $ds['helper']=='cache') {
			$datasource_cache_order['all'][] = $name;
		}
	}
	unset($ds,$name);
}

// Add the request cache (static array)
$datasource['request'] = array(
	'helper' => 'cache',
	'type' => 'request',
);
//array_unshift($datasource_cache_order['all'],'request');

// Document root and Escher path
$document_root = ESCHER_DOCUMENT_ROOT;
$escher_path = ESCHER_REAL_PATH;