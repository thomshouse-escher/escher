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

		// Set $this->id to $router->instance_id if present
		if (!empty($this->router->instance_id)) {
			$this->id = $this->router->instance_id;
		}

		// Clean up args
		if (is_null($args)) { $args = $this->args; }
		$args = (array)$args;

		// Determine the intended action
		if (isset($this->router->action)) {
			// Priority 1: Action specified by the router
			$action = $this->router->action;
		} elseif (isset($args[1])
			&& ($this->argPrecedesActions===TRUE
				|| (is_array($this->argPrecedesActions)
					&& in_array($args[1],$this->argPrecedesActions)))
			&& method_exists($this,'action_'.$args[1])
			&& $args[1]!=$this->defaultAction
		) {
			// Priority 2: 2nd Argument in case of valid $argPrecedesActions
			$action = $args[1];
			array_splice($args,1,1);
		} elseif (isset($args[0])
			&& method_exists($this,'action_'.$args[0])
			&& $args[0]!=$this->defaultAction
		) {
			// Priority 3: 1st Argument in case of valid method
			$action = array_shift($args);
		} else {
			// Priority 4: Default argument if valid
			$action = $this->defaultAction;
			// If $args are present and not allowed, can't execute
			if (!empty($args) && !$this->defaultAllowArgs) { return false; }
		}

		// Determine whether the intended action is valid 
		if (method_exists($this,"manage_$action")) {
			$functype = "manage";
		} elseif (method_exists($this,"action_$action")) {
			$functype = "action";
		} else {
			return false;
		}

		// If our action requires access, do an ACL check
		if ($functype=="manage" || in_array($action,$this->ACLRestrictedActions)) {
			$acl = Load::ACL();
			// If ACL check fails, request is unauthorized
			if (!$acl->check(NULL,$action)) { Load::Error('401'); }
		// If our context requires access, do all the same checks as above.
		} elseif ($this->isACLRestricted==TRUE) {
			$acl = Load::ACL();
			if (!$acl->check()) { Load::Error('401'); }
		}

		// Record which action we are calling, and call it
		$this->calledAction = $action;
		$result = call_user_func(array(&$this,"{$functype}_{$action}"),$args);

		// Return true if result is true or data is non-empty
		return (bool)($result || !empty($this->data));
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