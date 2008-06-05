<?php
/*****************************************************************************
 *
 * NagiosServicegroup.php - Class of a Servicegroup in Nagios with all necessary 
 *                  informations
 *
 * Copyright (c) 2004-2008 NagVis Project (Contact: lars@vertical-visions.de)
 *
 * License:
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 *****************************************************************************/
 
/**
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
	var $in_downtime;
	
	var $summary_state;
	var $summary_output;
	var $summary_problem_has_been_acknowledged;
	var $summary_in_downtime;
	
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
		$this->MAINCFG = &$MAINCFG;
		$this->BACKEND = &$BACKEND;
		$this->LANG = &$LANG;
		$this->backend_id = $backend_id;
		$this->servicegroup_name = $servicegroupName;
		
		$this->members = Array();
		$this->state = '';
		
		parent::NagVisStatefulObject($this->MAINCFG, $this->BACKEND, $this->LANG);
	}
	
	/**
	 * PUBLIC fetchMembers()
	 *
	 * Gets all member objects
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchMembers() {
		// Get all member services
		$this->fetchMemberServiceObjects();
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
		// Get states of all members
		foreach($this->members AS &$OBJ) {
			$OBJ->fetchState();
		}
		
		// Also get summary state
		$this->fetchSummaryState();
		
		// At least summary output
		$this->fetchSummaryOutput();
		$this->state = $this->summary_state;
	}
	
	/**
	 * PUBLIC getNumMembers()
	 *
	 * Counts the number of members
	 *
	 * @return	Integer		Number of members
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getNumMembers() {
		return count($this->members);
	}
	
	/**
	 * PUBLIC getMembers()
	 *
	 * Returns the member objects
	 *
	 * @return	Array		Member objects
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getMembers() {
		return $this->members;
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
		// Get all services and states
		if($this->BACKEND->checkBackendInitialized($this->backend_id, TRUE)) {
			// Get additional informations like the alias (maybe bad place here)
			$this->setConfiguration($this->BACKEND->BACKENDS[$this->backend_id]->getServicegroupInformations($this->servicegroup_name));
			
			$arrServices = $this->BACKEND->BACKENDS[$this->backend_id]->getServicesByServicegroupName($this->servicegroup_name);
			foreach($arrServices AS &$service) {
				$OBJ = new NagVisService($this->MAINCFG, $this->BACKEND, $this->LANG, $this->backend_id, $service['host_name'], $service['service_description']);
				
				// The services have to know how they should handle hard/soft 
				// states. This is a little dirty but the simplest way to do this
				// until the hard/soft state handling has moved from backend to the
				// object classes.
				$OBJ->setConfiguration($this->getObjectConfiguration());
				
				// Add child object to the members array
				$this->members[] = $OBJ;
			}
		}
	}
	
	/**
	 * PRIVATE fetchSummaryState()
	 *
	 * Fetches the summary state of all members
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchSummaryState() {
		if($this->getNumMembers() > 0) {
			// Get summary state member objects
			foreach($this->members AS &$MEMBER) {
				$this->wrapChildState($MEMBER);
			}
		} else {
			$this->summary_state = 'ERROR';
		}
	}
	
	/**
	 * PRIVATE fetchSummaryOutput()
	 *
	 * Fetches the summary output from all members
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchSummaryOutput() {
		if($this->getNumMembers() > 0) {
			$arrStates = Array('UNREACHABLE' => 0, 'CRITICAL' => 0,'DOWN' => 0,'WARNING' => 0,'UNKNOWN' => 0,'UP' => 0,'OK' => 0,'ERROR' => 0,'ACK' => 0,'PENDING' => 0);
			
			// Get summary state of this and child objects
			foreach($this->members AS &$MEMBER) {
				$arrStates[$MEMBER->getSummaryState()]++;
			}
			
			$this->mergeSummaryOutput($arrStates, $this->LANG->getLabel('services'));
		} else {
			$this->summary_output = $this->LANG->getMessageText('serviceGroupNotFoundInDB','SERVICEGROUP~'.$this->servicegroup_name);
		}
	}
}
?>
