<?php

class Controller_install extends Controller {
	protected $dbTypes = array(
		'mysql'    => 'tcp://127.0.0.1:3306',
		'postgres' => 'tcp://127.0.0.1:5432',
		// Not much chance of finding MSSQL on localhost... ;)
	);

	function action_index($args) {
		// Skip to setup if config.php is detected
		if (file_exists(ESCHER_DOCUMENT_ROOT.'/config.php')) {
			$this->headers->redirect('./setup/');
		}
		return TRUE;
	}

	function action_config($args) {
		// Handle download/install differently
		if (!empty($args)) {
			if ($args[0]=='download') {
				return $this->installConfig(TRUE);
			} if ($args[0]=='install') {
				return $this->installConfig();
			}
		}

		// Skip to setup if config.php is detected
		if (file_exists(ESCHER_DOCUMENT_ROOT.'/config.php')) {
			$this->headers->redirect('./setup/');
		}

		// If there is post data, test services
		if (!empty($this->input->post)) {
			$continue = TRUE;
			$_SESSION['config'] = $post = $this->input->post;

			// Test database settings
			$db = Load::Helper('database','pdo',$post['db']);
			// Check to see if we are connected
			if (!$connected = $db->getOne('SELECT 1')) {
				$this->headers->addNotification(
					'Could not connect to database.','error');
				$continue = FALSE;
			}

			// Test cache settings
			if ($post['cache']['type']=='memcached') {
				if (!class_exists('Memcache')) {
					$this->headers->addNotification(
						'Memcache class is not available.','error');
					$continue = FALSE;
				} else {
					$mc = Load::Helper('cache','memcached',$post['cache']);
					if ($mc->set('escherInstalled',1)) {
						$mc->delete('escherInstalled');
					} else {
						$this->headers->addNotification(
							'Could not connect to memcached.','error');
						$continue = FALSE;
					}
				}
			} elseif ($post['cache']['type']=='apc') {
				if (!function_exists('apc_store')) {
					$this->headers->addNotification(
						'APC is not available.','error');
					$continue = FALSE;
				} else {
					$mc = Load::Helper('cache','apc');
					if ($mc->set('escherInstalled',1)) {
						$mc->delete('escherInstalled');
					} else {
						$this->headers->addNotification(
							'Could not write to APC.','error');
						$continue = FALSE;
					}
				}
			}


			//Check to see if tables exist
			if ($connected) {
				if(sizeof(preg_grep('/^'.$post['db']['prefix'].'/i',
					$db->getCol('SHOW TABLES')))>0
				) {
					$this->headers->addNotification(
						'This database is not empty. To install a new copy of Escher, '
							.'choose a different table prefix.','error');
					$continue = FALSE;
				}
			}

			if ($continue) {
				$this->headers->redirect('./config/install/');
			} else {
				$this->headers->redirect('./config/');
			}
		}

		// Set up database options
		$dbs_detected = array();
		foreach($this->dbTypes as $type => $addr) {
			if (@stream_socket_client($addr,$foo,$bar,1)) {
				$dbs_detected[] = $type;
			}
		}
		if (!empty($dbs_detected)) {
			$db_values = array(
				'db[driver]'  => reset($dbs_detected),
				'db[host]'    => '127.0.0.1',
				'db[prefix]'  => 'esc_',
			);
		} else {
			$db_values = array('db[prefix]' => 'esc_');
		}

		// Set up cache options
		$caches_detected = array();
		if (@stream_socket_client('tcp://127.0.0.1:11211',$foo,$bar,1)) {
			$caches_detected[] = 'memcached';
		}
		if (function_exists('apc_store')) {
			$caches_detected[] = 'apc';
		}
		if (in_array('memcached',$caches_detected)) {
			$cache_values = array(
				'cache[type]'    => 'memcached',
				'cache[host]'    => '127.0.0.1',
				'cache[port]'    => '11211',
				'cache[prefix]'  => 'escher',
			);
		} elseif (in_array('apc',$caches_detected)) {
			$cache_values = array('cache[type]' => 'apc');
		} else {
			$cache_values = array('cache[prefix]' => 'escher');
		}

		// Repopulate config values with $_SESSION
		if (!empty($_SESSION['config']['db'])) {
			if (!empty($_SESSION['config']['db']['driver'])) {
				foreach($_SESSION['config']['db'] as $k => $v) {
					$db_values["db[$k]"] = $v;
				}
			}
		}
		if (!empty($_SESSION['config']['cache'])) {
			if (!empty($_SESSION['config']['cache']['type'])) {
				foreach($_SESSION['config']['cache'] as $k => $v) {
					$cache_values["cache[$k]"] = $v;
				}
			}
		}

		// Set output data
		$this->data = array(
			'values'          => array_merge($db_values,$cache_values),
			'dbs_detected'    => $dbs_detected,
			'caches_detected' => $caches_detected,
		);
	}

	function installConfig($download=FALSE) {
		// Consider success if config.php is detected
		if (file_exists(ESCHER_DOCUMENT_ROOT.'/config.php')) {
			unset($_SESSION['config']);
			$this->display('install');
		}

		// Make sure we have the config settings to work with
		if (empty($_SESSION['config'])) {
			$this->headers->redirect('./');
		}

		// Create the contents of config.php
		$file = "<?php\n\n"
			."/**\n"
			." * Escher configuration file.\n"
			." *\n"
			." * This file includes the low-level config settings for Escher.\n"
			." * Most config settings are stored in the database.\n"
			." *\n"
			." * This file was automatically generated by Escher.\n"
			." */\n\n";
		// Database datasource config
		$db = array_merge(
			array(
				'helper' => 'database',
				'type'   => 'pdo',
			),
			$_SESSION['config']['db']
		);
		$file .= '$datasource[\'database\'] = '.var_export($db,TRUE).";\n\n";
		// Cache datasource config (optional)
		$cache = $_SESSION['config']['cache'];
		if ($cache['type']=='memcached') {
			$cache['helper'] = 'cache';
			$file .= '$datasource[\'memcached\'] = '
				.var_export($cache,TRUE).";\n\n";
		} elseif ($cache['type']=='apc') {
			$file .= '$datasource[\'apc\'] = '
				.var_export(array(
					'helper' => 'cache',
					'type'   => 'apc',
				),TRUE).";\n\n";
		}

		// Register the session handler and router
		$file .= "\$session['type'] = 'datasource';\n\n"
			."\$router['type'] = 'dynamic';\n\n";

		// Clean up the file contents
		$file = preg_replace(
			array('/array \(/','/\n(\s\s)+array\(/',
				'/(?<=\n)(\s\s)/','/(?<=\t)(\s\s)/'),
			array('array(','array(',"\t","\t"),
			$file);

		// If we're downloading the file, do header magic
		if ($download) {
			$this->headers->addHTTP(
				'Content-Disposition: attachment; filename="config.php"'
			);
			$this->headers->sendHTTP();
			exit($file);
		}

		// Attempt to write the file to the server
		if(is_writable(ESCHER_DOCUMENT_ROOT)
			&& file_put_contents(ESCHER_DOCUMENT_ROOT.'/config.php',$file)
		) {
			unset($_SESSION['config']);
			$this->display('install');
		} else {
			$this->display('nomod');
		}
	}

	function action_setup($args) {
		// Check to see if config.php exists
		if (!file_exists(ESCHER_DOCUMENT_ROOT.'/config.php')) {
			$this->headers->redirect('./');
		}

		// Check to see if tables exist
		$db = Load::DB();
		if (sizeof(preg_grep('/^'.preg_quote($db->t('',0)).'/',
			$db->getCol('SHOW TABLES')))>0
		) {
			if (!empty($args)) {
				$this->headers->redirect('./setup/');
			} else {
				$this->display('notempty');
			}
		}

		if (!empty($args) && $args[0]=='complete') {
			$this->setupComplete();
			die('hmm');
		}

		if (!empty($this->input->post)) {
			$continue = TRUE;
			$_SESSION['setup'] = $post = $this->input->post;

			if (empty($post['config']['title'])) {
				$this->UI->setInputStatus('config[title]','error','Required');
				$continue = FALSE;
			}
			if (empty($post['config']['subtitle'])) {
				$this->UI->setInputStatus('config[subtitle]','error','Required');
				$continue = FALSE;
			}
			if (empty($post['admin']['username'])) {
				$this->UI->setInputStatus('admin[username]','error','Required');
				$continue = FALSE;
			}
			if (empty($post['admin']['display_name'])) {
				$this->UI->setInputStatus('admin[display_name]','error','Required');
				$continue = FALSE;
			}
			if (empty($post['admin']['password'])) {
				$this->UI->setInputStatus('admin[password]','error','Required');
				$continue = FALSE;
			} elseif ($post['admin']['password']!=$post['admin']['password2']) {
				$this->UI->setInputStatus('admin[password2]','error',
					'Passwords do not match'
				);
				$continue = FALSE;
			}
			if (empty($post['admin']['email'])) {
				$this->UI->setInputStatus('admin[email]','error','Required');
				$continue = FALSE;
			}
			if (empty($post['adminUrl'])) {
				$this->UI->setInputStatus('adminUrl','error','Required');
				$continue = FALSE;
			}

			if ($continue) {
				$this->headers->redirect('./setup/complete/');
			} else {
				$this->headers->redirect('./setup/');
			}
		}

		$config = Load::Config();

		$values = array(
			'config[wwwroot]'     => $config['wwwroot'],
			'config[subtitle]'    => 'Powered by Escher',
			'admin[username]'     => 'admin',
			'adminUrl'            => 'admin',
		);
		$pages = 0;
		if (!empty($_SESSION['setup'])) {
			foreach($_SESSION['setup']['config'] as $k => $v) {
				$values["config[$k]"] = $v;
			}
			if (!empty($_SESSION['setup']['route'])) {
				foreach($_SESSION['setup']['route'] as $n => $page) {
					foreach($page as $k => $v) {
						$values["route[$n][$k]"] = $v;
					}
					$pages++;
				}
			}
			foreach($_SESSION['setup']['admin'] as $k => $v) {
				$values["admin[$k]"] = $v;
			}
			unset($values['admin[password]'],$values['admin[password2]']);
			$values['adminUrl'] = $_SESSION['setup']['adminUrl'];
		}
		$this->data['values'] = $values;
		$this->data['pages'] = $pages;
	}

	function setupComplete() {
		$setup = $_SESSION['setup'];

		// Setup config settings for home page
		$hp = Load::Model($setup['root']);
		$hp->save();
		$hr = Load::Model('route_dynamic');
		$hr->assignVars(array(
			'controller'  => $setup['root'],
			'instance_id' => $hp->id(),
		));
		$hr->save();
		$setup['config']['root'] = array(
			'controller'  => $setup['root'],
			'id'          => $hr->id(),
			'instance_id' => $hp->id(),
		);

		$setup['config']['theme'] = array('bootstrap','strapped');
		$setup['config']['wwwroot'] = preg_replace('#/+$#','',
			$setup['config']['wwwroot']);

		// Save config settings
		$config = Load::Config();
		foreach($setup['config'] as $k => $v) {
			$config->save($k,$v);
		}

		// Setup admin user and permissions
		$auth = Load::Helper('userauth','local');
		$user = $auth->register(
			$setup['admin']['username'],
			$setup['admin']['password'],
			array(
				'email'        => $setup['admin']['email'],
				'display_name' => $setup['admin']['display_name'],
			)
		);
		$acl = Load::ACL();
		$acl->allow($user,'all','all',0,TRUE,TRUE);
		$acl->allow($user,'all','sysadmin',0,TRUE,TRUE);

		// Setup route to admin controller
		$route = Load::Model('route_dynamic');
		$route->assignVars(array(
			'title'      => 'Admin Center',
			'tag'        => $setup['adminUrl'],
			'controller' => 'admin',
			'parent_id'  => $hr->id(),
		));
		$route->save();

		// Setup routes
		$num = array(
			'page' => 1,
			'blog' => 1,
		);
		$num[$setup['root']]++;
		if (!empty($setup['route'])) {
			foreach($setup['route'] as $r) {
				$i = ++$num[$r['controller']];
				$route = Load::Model('route_dynamic');
				$r['parent_id'] = $hr->id();
				$r['instance_id'] = $i;
				$route->assignVars($r);
				$route->save();
			}
		}
		$this->display('complete');
	}
}