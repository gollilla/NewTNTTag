<?php 

namespace soradore\tnttag;

class TaskManager {

    public function __construct($scheduler){
    	$this->scheduler = $scheduler;
    }


	public function submitTask($task, $per){
		$this->scheduler->scheduleRepeatingTask($task, $per);
	}
}