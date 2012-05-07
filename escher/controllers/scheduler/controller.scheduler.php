<?php

class Controller_scheduler extends Controller {
	protected $job;

	function action_index($args) {
		// List current queue and progress
		$j = Load::Model('scheduler_job');
		$this->data['processing'] = $j->find(
			array('status' => 'processing'),
			array('order' => array('started_at'=>1))
		);
		$this->data['failed'] = $j->find(
			array(
				'status'   => 'failed',
				'ended_at' => array('>' => NOW-5*24*60*60),
			),
			array('order' => array('ended_at' => 1))
		);
		$this->data['queued'] = $j->find(
			array('status' => 'queued'),
			array('order' => array('process_at'=>1))
		);
		$this->data['completed'] = $j->find(
			array(
				'status'   => 'completed',
				'ended_at' => array('>' => NOW-10*24*60*60),
			),
			array('limit' => 10, 'order' => array('ended_at' => 1)));
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

		// Get the current hostname of the server
		$node = trim(file_get_contents('/etc/hostname'));

		// Load the database
		$db = Load::DB();

		// Attempt to reserve a job atomically
		$db->execute('UPDATE '.$db->t('scheduler_job')
			.' SET status="processing", node=?, started_at=FROM_UNIXTIME(?)
			WHERE status IN("queued","") && process_at<=FROM_UNIXTIME(?) LIMIT 1',array($node,NOW,NOW));
		if (!$db->affectedRows()) { return 'No jobs.'; }
		$this->job = $job = Load::Model('scheduler_job',array('status'=>'processing','node'=>$node));

		register_shutdown_function(array($this,'shutdown_function'));

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
		} else {
			set_error_handler(array($this,'error_handler'),E_ALL);
			if ($con_obj->{$job->method}($job->data,$job)) {
				$job->assignVars(array(
					'status'   => 'completed',
					'ended_at' => time(),
				));
				if (empty($job->progress) && empty($job->total)) {
					$job->assignVars(array(
						'progress' => 1,
						'total'    => 1,
					));
				}
			} else {
				$job->assignVars(array(
					'status'   => 'failed',
					'ended_at' => time(),
				));
			}
		}
		$job->save();
		$this->job = NULL;

		// Keep running the scheduler until there are no jobs
		$scheduler = Load::Helper('scheduler');
		$scheduler->process(1);
		return 'Done';
	}

	function error_handler($number,$message,$file,$line) {
		// If error occurs during a job, log it
		if (!is_null($this->job)) {
			if (!is_array($this->job->errors)) {
				$this->job->errors = array();
			}
			$this->job->errors[] = array(
				'type'    => $number,
				'message' => $message,
				'file'    => $file,
				'line'    => $line,
			);
			$this->job->save();
		}
	}

	function shutdown_function() {
		// If job shut down unexpectedly, attempt to save failure info
		if (!is_null($this->job)) {
			$this->job->assignVars(array(
				'status'   => 'failed',
				'message'  => 'Job shut down unexpectedly.',
				'ended_at' => time(),
			));
			$this->job->save();
			$this->job = NULL;

			// Keep running the scheduler until there are no jobs
			$scheduler = Load::Helper('scheduler');
			$scheduler->process(1);
		}
	}
}