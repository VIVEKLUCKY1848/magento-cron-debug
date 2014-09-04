<?php

require_once 'abstract.php';

class Mage_Shell_Cron extends Mage_Shell_Abstract {

	/**
	 * Run script
	 * @return void
	 */
	public function run(){

		$action = $this->getArg('action');

		if( empty($action) ){
			echo $this->usageHelp();

		}else{

			$actionMethodName = $action.'Action';

			if( method_exists($this, $actionMethodName) ){
				$this->$actionMethodName();

			}else{
				echo "Action $action not found!\n";
				echo $this->usageHelp();
				exit(1);
			}

		}

	}

	/**
	 * Retrieve Usage Help Message
	 * @return string
	 */
	public function usageHelp(){

		$help = 'Available actions: ' . "\n";
		$methods = get_class_methods($this);

		foreach( $methods as $method ){

			if( substr($method, -6) == 'Action' ){

				$help .= '    -action ' . substr($method, 0, -6);
				$helpMethod = $method.'Help';
				if (method_exists($this, $helpMethod)) {
					$help .= $this->$helpMethod();
				}

				$help .= "\n";
			}

		}

		return $help;
	}

	/**
	 * Display extra help
	 * @return string
	 */
	public function listAllJobsActionHelp() {
		return " - List all registered jobs from config.xml";
	}

	/**
	 * List all registered jobs
	 * @return void
	 */
	public function listAllJobsAction(){

		$jobs = Mage::getConfig()->getNode('crontab/jobs');

		echo "\n";
		echo '========== ALL JOBS ========';
		echo "\n";
		echo 'SINTAX: code - observer - cron_expr';
		echo "\n";

		foreach ($jobs[0] as $code => $job) {
			echo $code. ' - '. $job->run->model .' - '. $job->schedule->cron_expr;
			echo "\n";
		}

		echo "\n";
	}

	/**
	 * List an schedules interator
	 * @param object $schedules
	 * @return void
	 */
	protected function listSchedules($schedules){

		if( count( $schedules->getIterator() ) ){

			foreach($schedules->getIterator() as $schedule){
				echo $schedule->getData('job_code');

				if( $schedule->getData('scheduled_at') ){
					echo ' - '. $schedule->getData('scheduled_at');
				}

				if( $schedule->getData('executed_at') ){
					echo ' - '. $schedule->getData('executed_at');
				}

				if( $schedule->getData('finished_at') ){
					echo ' - '. $schedule->getData('finished_at');
				}

				echo "\n";
			}

		}else{
			echo '...';
		}

		echo "\n";
	}

	/**
	 * Display extra help
	 * @return string
	 */
	public function listPendingJobsActionHelp() {
		return " [-interval MINUTES] - List all pending jobs from a interval of X min";
	}

	/**
	 * List all pending jobs
	 * @return void
	 */
	function listPendingJobsAction(){

		$interval = $this->getArg('interval');

		if( !$interval ){
			$interval = Mage::getStoreConfig('system/cron/max_running_time');
		}

		$maxAge = time() - $interval * 60;

		echo "\n";
		echo '========== PENDING JOBS ========';
		echo "\n";
		echo 'SINTAX: code - scheduled_at';
		echo "\n";

		$schedules = Mage::getModel('cron/schedule')
			->getCollection()
			->addFieldToFilter('status', Mage_Cron_Model_Schedule::STATUS_PENDING)
			->addFieldToFilter('scheduled_at', array('lt' => strftime('%Y-%m-%d %H:%M:00', $maxAge)))
			->load();

		return $this->listSchedules($schedules);
	}

	/**
	 * Display extra help
	 * @return string
	 */
	public function listRunningJobsActionHelp() {
		return " [-interval MINUTES] - List all running jobs from a interval of X min";
	}

	/**
	 * List all running jobs
	 * @return void
	 */
	function listRunningJobsAction(){

		$interval = $this->getArg('interval');

		if( !$interval ){
			$interval = Mage::getStoreConfig('system/cron/max_running_time');
		}

		$maxAge = time() - $interval * 60;

		echo "\n";
		echo '========== RUNNING JOBS ========';
		echo "\n";
		echo 'SINTAX: code - scheduled_at - executed_at';
		echo "\n";

		$schedules = Mage::getModel('cron/schedule')->getCollection()
			->addFieldToFilter('status', Mage_Cron_Model_Schedule::STATUS_RUNNING)
			->addFieldToFilter('executed_at', array('lt' => strftime('%Y-%m-%d %H:%M:00', $maxAge)))
			->load();

		return $this->listSchedules($schedules);
	}

	/**
	 * Display extra help
	 * @return string
	 */
	public function listFinishedJobsActionHelp() {
		return " [-interval MINUTES] - List all finished jobs from a interval of X min";
	}

	/**
	 * List all finished jobs
	 * @return void
	 */
	function listFinishedJobsAction(){

		$interval = $this->getArg('interval');

		if( !$interval ){
			$interval = Mage::getStoreConfig('system/cron/max_running_time');
		}

		$maxAge = time() - $interval * 60;

		echo "\n";
		echo '========== FINISHED JOBS ========';
		echo "\n";
		echo 'SINTAX: code - scheduled_at - executed_at - finished_at';
		echo "\n";

		$schedules = Mage::getModel('cron/schedule')->getCollection()
			->addFieldToFilter('status', Mage_Cron_Model_Schedule::STATUS_SUCCESS)
			->addFieldToFilter('finished_at', array('lt' => strftime('%Y-%m-%d %H:%M:00', $maxAge)))
			->load();

		return $this->listSchedules($schedules);
	}

	/**
	 * Display extra help
	 * @return string
	 */
	public function listErrorJobsActionHelp() {
		return " [-interval MINUTES] - List all jobs errors from a interval of X min";
	}

	/**
	 * List all finished jobs
	 * @return void
	 */
	function listErrorJobsAction(){

		$interval = $this->getArg('interval');

		if( !$interval ){
			$interval = Mage::getStoreConfig('system/cron/max_running_time');
		}

		$maxAge = time() - $interval * 60;

		echo "\n";
		echo '========== ERROR JOBS ========';
		echo "\n";
		echo 'SINTAX: code - scheduled_at - executed_at - finished_at';
		echo "\n";

		$schedules = Mage::getModel('cron/schedule')->getCollection()
			->addFieldToFilter('status', Mage_Cron_Model_Schedule::STATUS_ERROR)
			->addFieldToFilter('finished_at', array('lt' => strftime('%Y-%m-%d %H:%M:00', $maxAge)))
			->load();

		return $this->listSchedules($schedules);
	}

	/**
	 * Display extra help
	 * @return string
	 */
	public function runJobActionHelp() {
		return " [-code JOB_CODE] - Schedule a job and force to run on next cron execution";
	}

	/**
	 * Force one job to run
	 * @return void
	 */
	function runJobAction(){

		$jobCode = $this->getArg('code');

		if( !$jobCode ){
			echo 'You must insert a a JOB_CODE to run this action';
			exit(1);
		}

		$jobsRoot = Mage::getConfig()->getNode('crontab/jobs');
		$jobConfig = $jobsRoot->{$jobCode};
		$timestamp = strftime('%Y-%m-%d %H:%M:00', time());

		if( !$jobConfig || !$jobConfig->run ){
			echo 'Job not found';
			exit(1);
		}

		$schedule = Mage::getModel('cron/schedule')
			->setJobCode($jobCode)
			->setStatus(Mage_Cron_Model_Schedule::STATUS_PENDING)
			->setCreatedAt($timestamp)
			->setScheduledAt($timestamp)
			->save();

	}

}

$shell = new Mage_Shell_Cron();
$shell->run();