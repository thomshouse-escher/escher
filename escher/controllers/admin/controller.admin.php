<?php

class Controller_admin extends Controller {
	protected $isACLRestricted = TRUE;
	function action_index($args) { return TRUE; }
}