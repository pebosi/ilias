<?php

/* Copyright (c) 1998-2011 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
* Navigation History of Repository Items
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*/
class ilNavigationHistory
{

	private $items;

	/**
	* Constructor.
	*
	* @param	int	$a_id	
	*/
	public function __construct()
	{
		$this->items = array();
		$items = null;
		if (isset($_SESSION["il_nav_history"]))
		{
			$items = unserialize($_SESSION["il_nav_history"]);
		}
		if (is_array($items))
		{
			$this->items = $items;
		}
	}

	/**
	* Add an item to the stack. If ref_id is already used,
	* the item is moved to the top.
	*/
	public function addItem($a_ref_id, $a_link, $a_type, $a_title = "", $a_sub_obj_id = "",
		$a_goto_link = "")
	{
		global $ilUser, $ilDB;

		$a_sub_obj_id = $a_sub_obj_id."";
		
		if ($a_title == "" && $a_ref_id > 0)
		{
			$obj_id = ilObject::_lookupObjId($a_ref_id);
			if (ilObject::_exists($obj_id))
			{
				$a_title = ilObject::_lookupTitle($obj_id);
			}
		}

		$id = $a_ref_id.":".$a_sub_obj_id;

		$new_items[$id] = array("id" => $id,"ref_id" => $a_ref_id, "link" => $a_link, "title" => $a_title,
			"type" => $a_type, "sub_obj_id" => $a_sub_obj_id, "goto_link" => $a_goto_link);
		
		$cnt = 1;
		foreach($this->items as $key => $item)
		{
			if ($item["id"] != $id && $cnt <= 10)
			{
				$new_items[$item["id"]] = $item;
				$cnt++;
			}
		}
		
		// put items in session
		$this->items = $new_items;

		$items  = serialize($this->items);
		$_SESSION["il_nav_history"] = $items;
//var_dump($this->getItems());
		// update entries in db
		$ilDB->update("usr_data",
				array(
					"last_visited" => array("clob", serialize($this->getItems()))),
				array(
				"usr_id" => array("integer", $ilUser->getId()))
			);
	}
	
	/**
	* Get navigation item stack.
	*/
	public function getItems()
	{
		global $tree, $ilDB, $ilUser;
		
		$items = array();
		
		foreach ($this->items as $it)
		{
			if ($tree->isInTree($it["ref_id"]))
			{
				$items[$it["ref_id"].":".$it["sub_obj_id"]] = $it;
			}
		}
		// less than 10? -> get items from db
		if (count($items) < 10)
		{
			$set = $ilDB->query("SELECT last_visited FROM usr_data ".
				" WHERE usr_id = ".$ilDB->quote($ilUser->getId(), "integer")
				);
			$rec  = $ilDB->fetchAssoc($set);
			$db_entries = unserialize($rec["last_visited"]);
			$cnt = count($items);
			if (is_array($db_entries))
			{
				foreach ($db_entries as $rec)
				{
					include_once("./Services/Link/classes/class.ilLink.php");
					
					if ($cnt <= 10 && ! isset($items[$rec["ref_id"].":".$rec["sub_obj_id"]]))
					{
						if ($tree->isInTree($rec["ref_id"]))
						{
							$link = ($rec["goto_link"] != "")
								? $rec["goto_link"]
								: ilLink::_getLink($rec["ref_id"]);
							if ($rec["sub_obj_id"] != "")
							{
								$title = $rec["title"];
							}
							else
							{
								$title = ilObject::_lookupTitle(ilObject::_lookupObjId($rec["ref_id"]));
							}
							$items[$rec["ref_id"].":".$rec["sub_obj_id"]] = array("id" => $rec["ref_id"].":".$rec["sub_obj_id"],
								"ref_id" => $rec["ref_id"], "link" => $link, "title" => $title,
								"type" => $rec["type"], "sub_obj_id" => $rec["sub_obj_id"], "goto_link" => $rec["goto_link"]);
							$cnt++;
						}
					}
				}
			}
		}
//var_dump($items);
		return $items;
	}
}
?>
