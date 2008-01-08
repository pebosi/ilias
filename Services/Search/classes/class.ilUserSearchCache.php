<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2006 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/

/** 
* Class for storing search result. Allows paging of result sets
* 
* 
* @author Stefan Meyer <smeyer@databay.de>
* @version $Id$
* 
* 
* @ilCtrl_Calls 
* @ingroup ServicesSearch 
*/
class ilUserSearchCache
{
	const DEFAULT_SEARCH = 0;
	const ADVANCED_SEARCH = 1;
	const ADVANCED_MD_SEARCH = 4;

	private static $instance = null;
	private $db;
	
	private $usr_id;
	private $search_type = self::DEFAULT_SEARCH;
	
	private $search_result = array();
	private $checked = array();
	private $failed = array();
	private $page_number = 1;
	
	
	/**
	 * Constructor
	 *
	 * @access private
	 * 
	 */
	private function __construct($a_usr_id)
	{
		global $ilDB;
		
		$this->db = $ilDB;
	 	$this->usr_id = $a_usr_id;
	 	$this->search_type = self::DEFAULT_SEARCH;
	 	$this->read();
	}
	
	/**
	 * Get singleton instance
	 *
	 * @access public
	 * @static
	 *
	 * @param int usr_id
	 */
	public static function _getInstance($a_usr_id)
	{
		if(is_object(self::$instance) and self::$instance)
		{
			return self::$instance;
		}
		return self::$instance = new ilUserSearchCache($a_usr_id);
	}
	
	/**
	 * switch to search type
	 * reads entries from database
	 * 
	 * @access public
	 * @param int search type
	 * 
	 */
	public function switchSearchType($a_type)
	{
	 	$this->search_type = $a_type;
	 	$this->read();
	 	return true;
	}
	
	/**
	 * Get results
	 *
	 * @access public
	 * 
	 */
	public function getResults()
	{
		return $this->search_result ? $this->search_result : array();	 	
	}
	
	/**
	 * Set results
	 *
	 * @access public
	 * @param array(int => array(int,int,string)) array(ref_id => array(ref_id,obj_id,type))
	 * 
	 */
	public function setResults($a_results)
	{
	 	$this->search_result = $a_results;
	}
	
	/**
	 * Append result
	 *
	 * @access public
	 * @param array(int,int,string) array(ref_id,obj_id,type)
	 * 
	 */
	public function addResult($a_result_item)
	{
	 	$this->search_result[$a_result_item['ref_id']]['ref_id'] = $a_result_item['ref_id'];
	 	$this->search_result[$a_result_item['ref_id']]['obj_id'] = $a_result_item['obj_id'];
	 	$this->search_result[$a_result_item['ref_id']]['type'] = $a_result_item['type'];
		return true;
	}

	/**
	 * Append failed id
	 *
	 * @access public
	 * @param int ref_id of failed access 
	 * 
	 */
	public function appendToFailed($a_ref_id)
	{
	 	$this->failed[$a_ref_id] = $a_ref_id;
	}
	
	/**
	 * check if reference has failed access
	 *
	 * @access public
	 * @param int ref_id
	 * 
	 */
	public function isFailed($a_ref_id)
	{
	 	return in_array($a_ref_id,$this->failed) ? true : false;
	}
	
	/**
	 * Append checked id
	 *
	 * @access public
	 * @param int checked reference id
	 * @param int checked obj_id
	 * 
	 */
	public function appendToChecked($a_ref_id,$a_obj_id)
	{
	 	$this->checked[$a_ref_id] = $a_obj_id;
	}
	
	/**
	 * Check if reference was already checked
	 *
	 * @access public
	 * @param int ref_id
	 * 
	 */
	public function isChecked($a_ref_id)
	{
	 	return array_key_exists($a_ref_id,$this->checked) and $this->checked[$a_ref_id];
	}
	
	/**
	 * Get all checked items
	 *
	 * @access public
	 * @return array array(ref_id => obj_id)
	 * 
	 */
	public function getCheckedItems()
	{
	 	return $this->checked ? $this->checked : array();
	}
	
	/**
	 * Set result page number
	 *
	 * @access public
	 * 
	 */
	public function setResultPageNumber($a_number)
	{
	 	if($a_number)
	 	{
	 		$this->page_number = $a_number;
	 	}
	}
	
	/**
	 * get result page number
	 *
	 * @access public
	 * 
	 */
	public function getResultPageNumber()
	{
	 	return $this->page_number ? $this->page_number : 1; 
	}
	
	/**
	 * Delete user entries
	 *
	 * @access public
	 * 
	 */
	public function delete()
	{
	 	$query = "DELETE FROM usr_search ".
	 		"WHERE usr_id = ".$this->db->quote($this->usr_id)." ".
	 		"AND search_type = ".$this->db->quote($this->search_type);
			
	 	$res = $this->db->query($query);
	 	$this->read();
		return true;
	}
	
	/**
	 * Save entries
	 *
	 * @access public
	 * 
	 */
	public function save()
	{
		if($this->usr_id == ANONYMOUS_USER_ID)
		{
			return false;
		}

	 	$query = "DELETE FROM usr_search ".
	 		"WHERE usr_id = ".$this->db->quote($this->usr_id)." ".
	 		"AND search_type = ".$this->db->quote($this->search_type);
		$res = $this->db->query($query);

	 	$query = "INSERT INTO usr_search ".
	 		"SET usr_id = ".$this->db->quote($this->usr_id).", ".
	 		"search_result = '".addslashes(serialize($this->search_result))."', ".
	 		"checked = '".addslashes(serialize($this->checked))."', ".
	 		"failed = '".addslashes(serialize($this->failed))."', ".
	 		"page = ".$this->db->quote($this->page_number).", ".
	 		"search_type = ".$this->db->quote($this->search_type);
	 	$res = $this->db->query($query);
	}
	
	
	/**
	 * Read user entries
	 *
	 * @access private
	 * 
	 */
	private function read()
	{
		$this->failed = array();
		$this->checked = array();
		$this->search_result = array();
		$this->page_number = 0;

		if($this->usr_id == ANONYMOUS_USER_ID)
		{
			return false;
		}

	 	$query = "SELECT * FROM usr_search ".
	 		"WHERE usr_id = ".$this->db->quote($this->usr_id)." ".
	 		"AND search_type = ".$this->db->quote($this->search_type);
		
	 	$res = $this->db->query($query);
	 	while($row = $res->fetchRow(MDB2_FETCHMODE_OBJECT))
	 	{
	 		$this->search_result = unserialize(stripslashes($row->search_result));
	 		if(strlen($row->checked))
	 		{
		 		$this->checked = unserialize(stripslashes($row->checked));
	 		}
	 		if(strlen($row->failed))
	 		{	
	 			$this->failed = unserialize(stripslashes($row->failed));
	 		}
	 		$this->page_number = $row->page;
	 	}
		return true;			
	}
}


?>