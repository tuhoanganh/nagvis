<?php
/**
 * Class of a Servicegroups in Nagios with all necessary informations
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
class NagiosServicegroup extends NagVisStatefulObject {
	var $MAINCFG;
	var $BACKEND;
	var $LANG;
	
	var $backend_id;
	
	var $servicegroup_name;
	var $alias;
	var $display_name;
	var $address;
	
	var $state;
	var $output;
	var $problem_has_been_acknowledged;
	
	var $summary_state;
	var $summary_output;
	var $summary_problem_has_been_acknowledged;
	
	var $members;
	
	/**
	 * Class constructor
	 *
	 * @param		Object 		Object of class GlobalMainCfg
	 * @param		Object 		Object of class GlobalBackendMgmt
	 * @param		Object 		Object of class GlobalLanguage
	 * @param		Integer 	ID of queried backend
	 * @param		String		Name of the servicegroup
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function NagiosServicegroup(&$MAINCFG, &$BACKEND, &$LANG, $backend_id, $servicegroupName) {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagiosServicegroup::NagiosServicegroup(MAINCFG,BACKEND,LANG,'.$backend_id.','.$servicegroupName.')');
		$this->MAINCFG = &$MAINCFG;
		$this->BACKEND = &$BACKEND;
		$this->LANG = &$LANG;
		$this->backend_id = $backend_id;
		$this->servicegroup_name = $servicegroupName;
		
		$this->members = Array();
		$this->state = '';
		
		parent::NagVisStatefulObject($this->MAINCFG, $this->BACKEND, $this->LANG);
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagiosServicegroup::NagiosServicegroup()');
	}
	
	/**
	 * PUBLIC fetchMembers()
	 *
	 * Gets all member objects
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchMembers() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagiosServicegroup::fetchMembers()');
		// Get all member services
		$this->fetchMemberServiceObjects();
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagiosServicegroup::fetchMembers()');
	}
	
	/**
	 * PUBLIC fetchState()
	 *
	 * Fetches the state of the servicegroup and all members. It also fetches the
	 * summary output
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchState() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagiosServicegroup::fetchState()');
		
		// Get states of all members
		foreach($this->members AS $OBJ) {
			$OBJ->fetchState();
		}
		
		// Also get summary state
		$this->fetchSummaryState();
		
		// At least summary output
		$this->fetchSummaryOutput();
		$this->state = $this->summary_state;
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagiosServicegroup::fetchState()');
	}
	
	# End public methods
	# #########################################################################
	
	/**
	 * PRIVATE fetchMemberServiceObjects()
	 *
	 * Gets all members of the given servicegroup and saves them to the members
	 * array
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchMemberServiceObjects() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagiosServicegroup::fetchMemberServiceObjects()');
		// Get all services and states
		if($this->BACKEND->checkBackendInitialized($this->backend_id, TRUE)) {
			$arrServices = $this->BACKEND->BACKENDS[$this->backend_id]->getServicesByServicegroupName($this->servicegroup_name);
			foreach($arrServices AS $service) {
				$OBJ = new NagVisService($this->MAINCFG, $this->BACKEND, $this->LANG, $this->backend_id, $service['host_name'], $service['service_description']);
				
				// FIXME: The services have to know how they should handle hard/soft 
				// states. This is a little dirty but the simplest way to do this
				// until the hard/soft state handling has moved from backend to the
				// object classes.
				$objConf = Array('only_hard_states' => $this->getOnlyHardStates());
				$OBJ->setConfiguration($objConf);
				
				// Add child object to the members array
				$this->members[] = $OBJ;
			}
		}
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagiosServicegroup::fetchMemberServiceObjects()');
	}
	
	/**
	 * PRIVATE getNumMembers()
	 *
	 * Counts the number of members
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getNumMembers() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagiosServicegroup::getNumMembers()');
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagiosServicegroup::getNumMembers()');
		return count($this->members);
	}
	
	/**
	 * PRIVATE fetchSummaryState()
	 *
	 * Fetches the summary state of all members
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchSummaryState() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagiosServicegroup::fetchSummaryState()');
		if($this->getNumMembers() > 0) {
			// Get summary state member objects
			foreach($this->members AS $MEMBER) {
				$this->wrapChildState($MEMBER);
			}
		} else {
			$this->summary_state = 'ERROR';
		}
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagiosServicegroup::fetchSummaryState()');
	}
	
	/**
	 * PRIVATE fetchSummaryOutput()
	 *
	 * Fetches the summary output from all members
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchSummaryOutput() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagiosServicegroup::fetchSummaryOutput()');
		if($this->getNumMembers() > 0) {
			$arrStates = Array('CRITICAL' => 0,'DOWN' => 0,'WARNING' => 0,'UNKNOWN' => 0,'UP' => 0,'OK' => 0,'ERROR' => 0,'ACK' => 0,'PENDING' => 0);
			
			// Get summary state of this and child objects
			foreach($this->members AS $MEMBER) {
				$arrStates[$MEMBER->getSummaryState()]++;
			}
			
			parent::fetchSummaryOutput($arrStates, $this->LANG->getLabel('services'));
		} else {
			$this->summary_output = $this->LANG->getMessageText('serviceGroupNotFoundInDB','SERVICEGROUP~'.$this->servicegroup_name);
		}
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagiosServicegroup::fetchSummaryOutput()');
	}
}
?>
