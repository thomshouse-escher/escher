<?php

class Controller_scheduler extends Controller {
	function action_index($args) {
		// List current queue and progress
		$j = Load::Model('scheduler_job');
		$this->data['processing'] = $j->find(array('status'=>'processing'),array('order'=>array('started_at'=>1)));
		$this->data['failed'] = $j->find(array('status'=>'failed','ended_at'=>array('>' => NOW-5*24*60*60)));
		$this->data['queued'] = $j->find(array('status'=>'queued'),array('order'=>array('process_at'=>1)));
	}

	function action_process($args) {
		// Process asynchronously via cli to prevent multiple simultaneous processes
		$scheduler = Load::Helper('scheduler');
		$scheduler->process();
		$this->headers->redirect();
	}

	function process($vargs) {
		// If we are not executing via command line, do nothing
		if (php_sapi_name()!='cli') { return false; }

		// Optionally, sleep for X number of seconds
		if (sizeof($vargs)>0 && is_numeric($vargs[0]) && $vargs[0]>0) {
			sleep(min($vargs[0],59));
		}

		// Get the current hostname of the server
		$node = trim(file_get_contents('/etc/hostname'));

		// Determine whether or not scheduler is already executing on this server
		exec('ps aux | grep "proc.php - scheduler process" | grep -v grep',$ps);
		if (sizeof($ps)>1) { return 'Already running.'; }

		// Load the database
		$db = Load::DB();

		// Attempt to reserve a job atomically
		$db->execute('UPDATE '.$db->t('scheduler_job')
			.' SET status="processing", node=?, started_at=FROM_UNIXTIME(?)
			WHERE status IN("queued","") && process_at<=FROM_UNIXTIME(?) LIMIT 1',array($node,NOW,NOW));
		if (!$db->affectedRows()) { return 'No jobs.'; }
		$job = Load::Model('scheduler_job',array('status'=>'processing','node'=>$node));

		$controller = !empty($job->plugin)
			? array($job->plugin,$job->controller)
			: $job->controller;
		if (!$con_obj = Load::Controller($controller)) {
			$job->assignVars(array(
				'status'   => 'failed',
				'message'  => 'Controller does not exist.',
				'ended_at' => NOW,
			));
		} elseif (!method_exists($con_obj,$job->method)) {
			$job->assignVars(array(
				'status'   => 'failed',
				'message'  => 'Method does not exist.',
				'ended_at' => NOW,
			));
		} elseif ($con_obj->{$job->method}($job->data,$job)) {
			$job->assignVars(array(
				'status'   => 'completed',
				'ended_at' => time(),
			));
		} else {
			$job->assignVars(array(
				'status'   => 'failed',
				'ended_at' => time(),
			));
		}
		$job->save();

		// Keep running the scheduler until there are no jobs
		$scheduler = Load::Helper('scheduler');
		$scheduler->process(1);
		return 'Done';
	}
}