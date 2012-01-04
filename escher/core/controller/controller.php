<?php

/**
 * Controller.php
 * 
 * Contains the Controller class, which is the basis for all controllers in Escher.
 * @author Thom Stricklin <code@thomshouse.net>
 * @version 1.0
 * @package Escher
 */

/**
 * Controller base class
 * @package Escher
 */
class EscherController extends EscherObject {
	protected $defaultAction = 'index';
	protected $defaultAllowArgs = FALSE;
	protected $args = NULL;
	protected $argPrecedesActions = FALSE;
	protected $output_type = 'php';
	protected $calledAction = NULL;
	protected $isACLRestricted = FALSE;
	protected $ACLRestrictedActions = array();
	protected $input = array();
	public $data = array();
	
	/**
	 * Constructor object.  
	 * @param array $args An array of arguments to load.
	 */
	function __construct($args=NULL) {
		parent::__construct();
		if (!is_null($args)) {
			$this->args = (array)$args;
		}

		// Convenient access for commonly used objects
		$this->headers = Load::Headers();
		$this->input = Load::Input();
		$this->USER = Load::User();
	}
	
	/**
	 * Executes the controller's behavior based on the arguments provided.  
	 * @param array $args An array of arguments to execute on.
	 */
	function execute($args=NULL) {
		// If this controller requires access, do an ACL check.
		if ($this->isACLRestricted==TRUE) {
			$acl = Load::ACL();
			if (!$acl->check()) { Load::Error('401'); }
		}

		// Set $this->id to $router->instance_id if present
		if (!empty($this->router->instance_id)) {
			$this->id = $this->router->instance_id;
		}

		// Clean up args
		if (is_null($args)) { $args = $this->args; }
		$args = (array)$args;

		// Determine the intended action
		// Priority 1: Action specified by the router
		if (isset($this->router->action)) {
			$action = $this->router->action;

		// Priority 2: 2nd Argument in case of valid $argPrecedesActions
		} elseif (isset($args[1])
			&& ($this->argPrecedesActions===TRUE
				|| (is_array($this->argPrecedesActions)
					&& in_array($args[1],$this->argPrecedesActions)))
			&& method_exists($this,'action_'.$args[1])
			&& $args[1]!=$this->defaultAction
		) {
			$action = $args[1];
			array_splice($args,1,1);

		// Priority 3: 1st Argument in case of valid manage function
		} elseif (isset($args[0]) && method_exists($this,'manage_'.$args[0])
			&& $args[0]!=$this->defaultAction
		) {
			$action = array_shift($args);
			$functype = "manage";

		// Priority 4: 1st Argument in case of valid action
		} elseif (isset($args[0]) && method_exists($this,'action_'.$args[0])
			&& $args[0]!=$this->defaultAction
		) {
			$action = array_shift($args);
			$functype = "action";

		// Priority 5: Default argument if valid
		} else {
			$action = $this->defaultAction;
		}

		// Don't execute default action if $args are present & not allowed
		if ($action==$this->defaultAction
			&& !$this->defaultAllowArgs
			&& !empty($args)
		) {
			return false;
		}

		// Determine whether the intended action is valid 
		if (empty($functype)) {
			if (method_exists($this,"manage_$action")) {
				$functype = "manage";
			} elseif (method_exists($this,"action_$action")) {
				$functype = "action";
			} else {
				return false;
			}
		}

		// If our action requires access, do an ACL check
		if ($functype=="manage" || in_array($action,$this->ACLRestrictedActions)) {
			$acl = Load::ACL();
			if (!$acl->check(NULL,$action)) { Load::Error('401'); }
		}

		// Record which action we are calling, and call it
		$this->calledAction = $action;
		$result = call_user_func(array(&$this,"{$functype}_{$action}"),$args);

		// Return true if result is true or data is non-empty
		return (bool)($result || !empty($this->data));
	}

	final protected function dispatch($path,$args=array(),$options=array(),$display=FALSE) {
		if (is_string($path)) {
			$path = preg_split('#/#',$path,-1,PREG_SPLIT_NO_EMPTY);
		}
		if (is_string($args)) {
			$args = preg_split('#/#',$args,-1,PREG_SPLIT_NO_EMPTY);
		}
		$base = $path;
		$dispatch = array_pop($base);
		$base = implode('/',$base);
		$controller = Load::Controller($dispatch,$args);
		if (!empty($options)) { $controller->assignVars($options); }
		$controller->router = Load::Helper('router','dispatch',
			array(
				'router' => $this->router,
				'dispatch' => $dispatch,
				'base' => $base,
				'args' => $args
			)
		);
		$result = $controller->execute();
		if ($display) {
			if (!$result && empty($controller->data)) {
				Load::Error('404');
			} else {
				$controller->display($controller->getCalledAction(),$controller->data);
			}
		} else {
			return $controller->render($controller->getCalledAction(),$controller->data);
		}			
	}
	
	// Display the specified view for this controller with the data provided
	final function display($view,$data=array(),$themed=TRUE,$type=NULL) {
		if ($result = $this->render($view,$data,$themed,$type)) {
			$headers = Load::Headers();
			$headers->sendHTTP();
			exit($result);
		}
		Load::Error('404');
	}
	
	// Render the specified view for this controller with the data provided
	final function render($view,$data=array(),$themed=FALSE,$type=NULL) {
		if (is_null($type)) {
			$type = $this->output_type;
		}
		$out = Load::Output($type,$this);
		$out->assignVars($data);
		if (!$themed) {
			return $out->displayControllerView($this,$view);
		} else {
			if (!$content = $out->displayControllerView($this,$view)) {
				return false;
			}
			$ui = Load::UI();
			return $out->displayTheme($ui->theme(),$content);
		}
	}
	
	// Allow others to see what action was called
	final function getCalledAction() {
		return $this->calledAction;
	}
	
	// What controller is this?
	final function _c() {
		$class = get_class($this);
		return substr($class,strpos($class,'Controller_')+11);
	}
}