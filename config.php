<?php

// Title and subtitle
$title = 'My New Website';
$subtitle = "Powered by Escher";

// Maintenance mode
$maintenance_mode = false;

// WWW-accessible root.  Leave off the trailing slash!
$wwwroot = 'http://www.mywebsite.com';

// Database
$database['default'] = array(
	'type' => 'adodb',
	'adotype' => 'mysqli',
	'address' => '127.0.0.1',
	'username' => '',
	'password' => '',
	'database' => '',
	'prefix' => 'esc_'
);

// Site config variables
$root = array('controller' => 'page','id' => 1);
$theme = 'popup';

// Routes
$static_routes = array(
	'about' => array('controller' => 'page','id' => 2,'title' => 'About'),
	'blog' => array('controller' => 'blog','id' => 1,'title' => 'Blog')
	);

// Determines "active" plugins for running hooks
$active_plugins = array('tinymce');
