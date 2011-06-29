<?php define('CFG_LOADED',true); error_reporting(E_ALL); ini_set('display_errors',1);

// Title and subtitle
$CFG['title'] = 'My New Website';
$CFG['subtitle'] = "Powered by Escher";

// Maintenance mode
$CFG['maintenance_mode'] = false;

// WWW-accessible root.  Leave off the trailing slash!
$CFG['wwwroot'] = 'http://www.mywebsite.com';

// Database
$CFG['database']['default'] = array(
	'type' => 'adodb',
	'adotype' => 'mysqli',
	'address' => '127.0.0.1',
	'username' => '',
	'password' => '',
	'database' => '',
	'prefix' => 'esc_'
);

// Site config variables
$CFG['root'] = array('controller' => 'page','id' => 1);
$CFG['theme'] = 'popup';

// Routes
$CFG['static_routes'] = array(
	'about' => array('controller' => 'page','id' => 2,'title' => 'About'),
	'blog' => array('controller' => 'blog','id' => 1,'title' => 'Blog')
	);

// Determines "active" plugins for running hooks
$CFG['active_plugins'] = array('tinymce');

// Automatically sets the root file path of this installation
$CFG['fileroot'] = dirname(__FILE__);
