<?php

// Site defaults
$title = 'Install Wizard';
$subtitle = 'Escher - A PHP MVC Framework';
$wwwroot = (!empty($_SERVER['HTTPS']) ? 'https://' : 'http://')
	.rtrim($_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']),'/');

// Set root to install wizard
$root = array('controller' => 'install');
$theme = 'escher';

// Data sources (read/write priority)
$datasource_order['all'] = array('database');

// Router allows arguments sent to the root controller
$allow_root_args = TRUE;

// Input processor
$input['type'] = 'default';

// Router
$router['type'] = 'static';

// Session
$session['type'] = 'default';

// User authentication
$userauth['default'] = array('type' =>'local');

// Reserved usernames
$reserved_usernames = array();

// Maintenance mode defaults
$maintenance_mode = FALSE;

// Static routes
$static_routes = array();

// Document root and Escher path
$document_root = ESCHER_DOCUMENT_ROOT;
$escher_path = ESCHER_REAL_PATH;