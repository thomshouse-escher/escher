<?php

// Convenience declarations
$title = 'My Website';
$subtitle = 'Powered by Escher';
$wwwroot = '';

// Data sources (read/write priority)
$datasource_order['all'] = array('db');

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
$userauth['default'] = array('type' =>'local');

// Reserved usernames
$reserved_usernames = array();

// Image resizes
$resized_images = array(
	'small' => array(200,200),
	'medium' => array(400,400),
	'large' => array(600,600),
);

// Maintenance mode defaults
$maintenance_mode = FALSE;
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

// Translate Cache datasource to default Cache
if (!isset($cache)
	&& !empty($datasource_cache_order['all'])
	&& is_array($datasource_cache_order['all'])
) {
	$cache = $datasource_cache_order['all'][0];
}

// Document root and Escher path
$document_root = ESCHER_DOCUMENT_ROOT;
$escher_path = ESCHER_REAL_PATH;