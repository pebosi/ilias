<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2001 ILIAS open source, University of Cologne            |
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
* Class ilObjectGUI
* Basic methods of all Output classes
*
* @author Stefan Meyer <smeyer@databay.de>
* @version $Id$
*
* @package ilias-core
*/
class ilObjectGUI
{
	/**
	* ilias object
	* @var		object ilias
	* @access	private
	*/
	var $ilias;

	/**
	* object Definition Object
	* @var		object ilias
	* @access	private
	*/
	var $objDefinition;

	/**
	* template object
	* @var		object ilias
	* @access	private
	*/
	var $tpl;

	/**
	* tree object
	* @var		object ilias
	* @access	private
	*/
	var $tree;

	/**
	* language object
	* @var		object ilias
	* @access	private
	*/
	var $lng;

	/**
	* output data
	* @var		data array
	* @access	private
	*/
	var $data;

	/**
	* object
	* @var          object
	* @access       private
	*/
	var $object;
	var $ref_id;
	var $obj_id;
	var $maxcount;			// contains number of child objects
	var $formaction;		// special formation (array "cmd" => "formaction")
	var $return_location;	// special return location (array "cmd" => "location")

	/**
	* Constructor
	* @access	public
	* @param	array	??
	* @param	integer	object id
	* @param	boolean	call be reference
	*/
	function ilObjectGUI($a_data, $a_id = 0, $a_call_by_reference = true, $a_prepare_output = true)
	{
		global $ilias, $objDefinition, $tpl, $tree, $lng;

		$this->ilias =& $ilias;
		$this->objDefinition =& $objDefinition;
		$this->tpl =& $tpl;
		$this->lng =& $lng;
		$this->tree =& $tree;
		$this->formaction = array();
		$this->return_location = array();

		$this->data = $a_data;
		$this->id = $a_id;
		$this->call_by_reference = $a_call_by_reference;

		$this->ref_id = $_GET["ref_id"];
		$this->obj_id = $_GET["obj_id"];

		// TODO: it seems that we always have to pass only the ref_id
		if ($a_id != 0)
		{
			if ($this->call_by_reference)
			{
				$this->link_params = "ref_id=".$this->ref_id;
				$this->object =& $this->ilias->obj_factory->getInstanceByRefId($a_id);

			}
			else
			{
				$this->link_params = "ref_id=".$this->ref_id;
				$this->object =& $this->ilias->obj_factory->getInstanceByObjId($a_id);
			}
		}

		//prepare output
		if ($a_prepare_output)
		{
			$this->tpl->addBlockFile("CONTENT", "content", "tpl.adm_content.html");
			$title = $this->object->getTitle();

			// catch feedback message
			sendInfo();

			if (!empty($title))
			{
				$this->tpl->setVariable("HEADER", $title);
			}

			$this->setAdminTabs();
			$this->setLocator();
		}

		// set offset & limit for
		// TODO: init better move to inc.header.php
		$_GET["offset"] = intval($_GET["offset"]);
		$_GET["limit"] = intval($_GET["limit"]);

		if ($_GET["limit"] == 0)
		{
			$_GET["limit"] = 10;	// TODO: move to user settings
		}

		// set default sort column
		if (empty($_GET["sort_by"]))
		{
			// TODO: init sort_by better in obj class?
			if ($this->object->getType() == "usrf"
				or $this->object->getType() == "rolf")
			{
				$_GET["sort_by"] = "name";
			}
			elseif ($this->object->getType() == "typ")
			{
				$_GET["sort_by"] = "operation";
			}
			elseif ($this->object->getType() == "lngf")
			{
				$_GET["sort_by"] = "language";
			}
			else
			{
				$_GET["sort_by"] = "title";
			}
		}
	}

	/**
	* set admin tabs
	* @access	public
	*/
	function setAdminTabs()
	{
		global $rbacsystem;

		$tabs = array();
		$this->tpl->addBlockFile("TABS", "tabs", "tpl.tabs.html");
		$d = $this->objDefinition->getProperties($this->type);

		foreach ($d as $key => $row)
		{
			$tabs[] = array($row["lng"], $row["name"]);
		}

		// check for call_by_reference too to avoid hacking
		if (isset($_GET["obj_id"]) and $this->call_by_reference === false)
		{
			$object_link = "&obj_id=".$_GET["obj_id"];
		}

		foreach ($tabs as $row)
		{
			$i++;

			if ($row[1] == $_GET["cmd"])
			{
				$tabtype = "tabactive";
				$tab = $tabtype;
			}
			else
			{
				$tabtype = "tabinactive";
				$tab = "tab";
			}

			$show = true;

			// only check permissions for tabs if object is a permission object
			if ($this->call_by_reference)
			{
				// only show tab when the corresponding permission is granted
				switch ($row[1])
				{
				  case 'view':
					  if (!$rbacsystem->checkAccess('visible',$this->ref_id))
					  {
						  $show = false;
					  }
					  break;
	
				  case 'edit':
					  if (!$rbacsystem->checkAccess('write',$this->ref_id))
					  {
						  $show = false;
					  }
					  break;
	
				  case 'perm':
					  if (!$rbacsystem->checkAccess('edit permission',$this->ref_id))
					  {
						  $show = false;
					  }
					  break;
				  case 'trash':
					  if (!$rbacsystem->checkAccess('edit permission',$this->ref_id))
					  {
						  $show = false;
					  }
					  break;
				} //switch
			}

			if (!$show)
			{
				continue;
			}

			$this->tpl->setCurrentBlock("tab");
			$this->tpl->setVariable("TAB_TYPE", $tabtype);
			$this->tpl->setVariable("TAB_TYPE2", $tab);
			$this->tpl->setVariable("IMG_LEFT", ilUtil::getImagePath("eck_l.gif"));
			$this->tpl->setVariable("IMG_RIGHT", ilUtil::getImagePath("eck_r.gif"));
			$this->tpl->setVariable("TAB_LINK", "adm_object.php?ref_id=".$_GET["ref_id"].$object_link."&cmd=".$row[1]);
			$this->tpl->setVariable("TAB_TEXT", $this->lng->txt($row[0]));
			$this->tpl->parseCurrentBlock();
		}
	}

	/**
	* set Locator
	*
	* @param	object	tree object
	* @param	integer	reference id
	* @access	public
 	*/
	function setLocator($a_tree = "", $a_id = "")
	{
		if (!is_object($a_tree))
		{
			$a_tree =& $this->tree;
		}

		if (!($a_id))
		{
			$a_id = $_GET["ref_id"];
		}

		$this->tpl->addBlockFile("LOCATOR", "locator", "tpl.locator.html");

		$path = $a_tree->getPathFull($a_id);

        //check if object isn't in tree, this is the case if parent_parent is set
		// TODO: parent_parent no longer exist. need another marker
		if ($a_parent_parent)
		{
			//$subObj = getObject($a_ref_id);
			$subObj =& $this->ilias->obj_factory->getInstanceByRefId($a_ref_id);

			$path[] = array(
				"id"	 => $a_ref_id,
				"title"  => $this->lng->txt($subObj->getTitle())
				);
		}

		// this is a stupid workaround for a bug in PEAR:IT
		$modifier = 1;

		if (isset($_GET["obj_id"]))
		{
			$modifier = 0;
		}

		foreach ($path as $key => $row)
		{
			if ($key < count($path)-$modifier)
			{
				$this->tpl->touchBlock("locator_separator");
			}

			$this->tpl->setCurrentBlock("locator_item");
			$this->tpl->setVariable("ITEM", $row["title"]);
			// TODO: SCRIPT NAME HAS TO BE VARIABLE!!!
			$this->tpl->setVariable("LINK_ITEM", "adm_object.php?ref_id=".$row["child"]);
			$this->tpl->parseCurrentBlock();
			
		}

		if (isset($_GET["obj_id"]))
		{
			$obj_data =& $this->ilias->obj_factory->getInstanceByObjId($_GET["obj_id"]);

			$this->tpl->setCurrentBlock("locator_item");
			$this->tpl->setVariable("ITEM", $obj_data->getTitle());
			// TODO: SCRIPT NAME HAS TO BE VARIABLE!!!
			$this->tpl->setVariable("LINK_ITEM", "adm_object.php?ref_id=".$_GET["ref_id"]."&obj_id=".$_GET["obj_id"]);
			$this->tpl->parseCurrentBlock();
		}

		$this->tpl->setCurrentBlock("locator");

		if (DEBUG)
		{
			$debug = "DEBUG: <font color=\"red\">".$this->type."::".$this->id."::".$_GET["cmd"]."</font><br/>";
		}

		$prop_name = $this->objDefinition->getPropertyName($_GET["cmd"],$this->type);

		if ($_GET["cmd"] == "confirmDeleteAdm")
		{
			$prop_name = "delete_object";
		}

		$this->tpl->setVariable("TXT_PATH",$debug.$this->lng->txt($prop_name)." ".strtolower($this->lng->txt("of")));
		$this->tpl->parseCurrentBlock();
	}

	/**
	* copy object to clipboard
	*
	* @access	public
	*/
	function copyObject()
	{
		global $rbacsystem;

		if (!isset($_POST["id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}

		// FOR ALL OBJECTS THAT SHOULD BE COPIED
		foreach ($_POST["id"] as $ref_id)
		{
			// GET COMPLETE NODE_DATA OF ALL SUBTREE NODES
			$node_data = $this->tree->getNodeData($ref_id);
			$subtree_nodes = $this->tree->getSubTree($node_data);
			
			$all_node_data[] = $node_data;
			$all_subtree_nodes[] = $subtree_nodes;

			// CHECK READ PERMISSION OF ALL OBJECTS IN ACTUAL SUBTREE
			foreach ($subtree_nodes as $node)
			{
				if (!$rbacsystem->checkAccess('read',$node["ref_id"]))
				{
					$no_copy[] = $node["ref_id"];
				}
			}
		}
		// IF THERE IS ANY OBJECT WITH NO PERMISSION TO 'read'
		if (count($no_copy))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_copy")." ".implode(',',$this->getTitlesByRefId($no_copy)),
									 $this->ilias->error_obj->MESSAGE);
		}

		$_SESSION["clipboard"]["parent"] = $_GET["ref_id"];
		$_SESSION["clipboard"]["cmd"] = key($_POST["cmd"]);
		$_SESSION["clipboard"]["ref_ids"] = $_POST["id"];
		
		sendinfo($this->lng->txt("msg_copy_clipboard"),true);

		header("location: adm_object.php?ref_id=".$_GET["ref_id"]);
		exit();
	}

	/**
	* paste object from clipboard to current place
	*
	* @access	public
 	*/
	function pasteObject()
	{
		global $rbacsystem, $rbacadmin;

		// CHECK SOME THINGS
		if ($_SESSION["clipboard"]["cmd"] == "copy")
		{
			// IF CMD WAS 'copy' CALL PRIVATE CLONE METHOD
			$this->cloneObject($_GET["ref_id"]);
			return true;
		}

		// PASTE IF CMD WAS 'cut' (TODO: Could be merged with 'link' routine below in some parts)
		if ($_SESSION["clipboard"]["cmd"] == "cut")
		{
			// TODO:i think this can be substituted by $this->object ????
			$object =& $this->ilias->obj_factory->getInstanceByRefId($_GET["ref_id"]);
	
			// this loop does all checks
			foreach ($_SESSION["clipboard"]["ref_ids"] as $ref_id)
			{
				$obj_data =& $this->ilias->obj_factory->getInstanceByRefId($ref_id);

				// CHECK ACCESS
				if (!$rbacsystem->checkAccess('create', $_GET["ref_id"], $obj_data->getType()))
				{
					$no_paste[] = $ref_id;
				}

				// CHECK IF REFERENCE ALREADY EXISTS
				if ($_GET["ref_id"] == $obj_data->getRefId())
				{
					$exists[] = $ref_id;
					break;
				}

				// CHECK IF PASTE OBJECT SHALL BE CHILD OF ITSELF
				// TODO: FUNCTION IST NOT LONGER NEEDED IN THIS WAY. WE ONLY NEED TO CHECK IF
				// THE COMBINATION child/parent ALREADY EXISTS

				//if ($tree->isGrandChild(1,0))
				//if ($tree->isGrandChild($id, $_GET["ref_id"]))
				//{
			//		$is_child[] = $ref_id;
				//}

				// CHECK IF OBJECT IS ALLOWED TO CONTAIN PASTED OBJECT AS SUBOBJECT
				$obj_type = $obj_data->getType();
			
				if (!in_array($obj_type, array_keys($this->objDefinition->getSubObjects($object->getType()))))
				{
					$not_allowed_subobject[] = $obj_data->getType();
				}
			}

//////////////////////////
// process checking results
		
			if (count($exists))
			{
				$this->ilias->raiseError($this->lng->txt("msg_obj_exists"),$this->ilias->error_obj->MESSAGE);
			}

			if (count($is_child))
			{
				$this->ilias->raiseError($this->lng->txt("msg_not_in_itself")." ".implode(',',$is_child),
										 $this->ilias->error_obj->MESSAGE);
			}

			if (count($not_allowed_subobject))
			{
				$this->ilias->raiseError($this->lng->txt("msg_may_not_contain")." ".implode(',',$not_allowed_subobject),
										 $this->ilias->error_obj->MESSAGE);
			}

			if (count($no_paste))
			{
				$this->ilias->raiseError($this->lng->txt("msg_no_perm_paste")." ".
										 implode(',',$no_paste),$this->ilias->error_obj->MESSAGE);
			}
/////////////////////////////////////////
// everything ok: now paste the objects to new location

			foreach($_SESSION["clipboard"]["ref_ids"] as $ref_id)
			{

				// get node data
				$top_node = $this->tree->getNodeData($ref_id);

				// get subnodes of top nodes
				$subnodes[$ref_id] = $this->tree->getSubtree($top_node);
			
				// delete old tree entries
				$this->tree->deleteTree($top_node);
			}

			// now move all subtrees to new location
			foreach($subnodes as $key => $subnode)
			{
				//first paste top_node....
				$rbacadmin->revokePermission($key);
				$obj_data =& $this->ilias->obj_factory->getInstanceByRefId($key);
				$obj_data->putInTree($_GET["ref_id"]);
				$obj_data->setPermissions($_GET["ref_id"]);
			
				// ... remove top_node from list....
				array_shift($subnode);
				
				// ... insert subtree of top_node if any subnodes exist
				if (count($subnode) > 0)
				{
					foreach ($subnode as $node)
					{
						$rbacadmin->revokePermission($node["child"]);
						$obj_data =& $this->ilias->obj_factory->getInstanceByRefId($node["child"]);
						$obj_data->putInTree($node["parent"]);
						$obj_data->setPermissions($node["parent"]);
					}
				}
			}
		} // END IF 'cut & paste'

		// PASTE IF CMD WAS 'linkt' (TODO: Could be merged with 'cut' routine above)
		if ($_SESSION["clipboard"]["cmd"] == "link")
		{
			// TODO:i think this can be substituted by $this->object ????
			$object =& $this->ilias->obj_factory->getInstanceByRefId($_GET["ref_id"]);
	
			// this loop does all checks
			foreach ($_SESSION["clipboard"]["ref_ids"] as $ref_id)
			{
				$obj_data =& $this->ilias->obj_factory->getInstanceByRefId($ref_id);

				// CHECK ACCESS
				if (!$rbacsystem->checkAccess('create', $_GET["ref_id"], $obj_data->getType()))
				{
					$no_paste[] = $ref_id;
				}

				// CHECK IF REFERENCE ALREADY EXISTS
				if ($_GET["ref_id"] == $obj_data->getRefId())
				{
					$exists[] = $ref_id;
					break;
				}

				// CHECK IF PASTE OBJECT SHALL BE CHILD OF ITSELF
				// TODO: FUNCTION IST NOT LONGER NEEDED IN THIS WAY. WE ONLY NEED TO CHECK IF
				// THE COMBINATION child/parent ALREADY EXISTS

				//if ($tree->isGrandChild(1,0))
				//if ($tree->isGrandChild($id, $_GET["ref_id"]))
				//{
			//		$is_child[] = $ref_id;
				//}

				// CHECK IF OBJECT IS ALLOWED TO CONTAIN PASTED OBJECT AS SUBOBJECT
				$obj_type = $obj_data->getType();
			
				if (!in_array($obj_type, array_keys($this->objDefinition->getSubObjects($object->getType()))))
				{
					$not_allowed_subobject[] = $obj_data->getType();
				}
			}

//////////////////////////
// process checking results
		
			if (count($exists))
			{
				$this->ilias->raiseError($this->lng->txt("msg_obj_exists"),$this->ilias->error_obj->MESSAGE);
			}

			if (count($is_child))
			{
				$this->ilias->raiseError($this->lng->txt("msg_not_in_itself")." ".implode(',',$is_child),
										 $this->ilias->error_obj->MESSAGE);
			}

			if (count($not_allowed_subobject))
			{
				$this->ilias->raiseError($this->lng->txt("msg_may_not_contain")." ".implode(',',$not_allowed_subobject),
										 $this->ilias->error_obj->MESSAGE);
			}

			if (count($no_paste))
			{
				$this->ilias->raiseError($this->lng->txt("msg_no_perm_paste")." ".
										 implode(',',$no_paste),$this->ilias->error_obj->MESSAGE);
			}
/////////////////////////////////////////
// everything ok: now paste the objects to new location

			foreach ($_SESSION["clipboard"]["ref_ids"] as $ref_id)
			{

				// get node data
				$top_node = $this->tree->getNodeData($ref_id);
			
				// get subnodes of top nodes
				$subnodes[$ref_id] = $this->tree->getSubtree($top_node);
			}

			// now move all subtrees to new location
			foreach ($subnodes as $key => $subnode)
			{
				//first paste top_node....
				$obj_data =& $this->ilias->obj_factory->getInstanceByRefId($key);
				$obj_data->createReference();
				$obj_data->putInTree($_GET["ref_id"]);
				$obj_data->setPermissions($_GET["ref_id"]);

				// ... remove top_node from list....
				array_shift($subnode);

				// ... insert subtree of top_node if any subnodes exist
				if (count($subnode) > 0)
				{
					foreach ($subnode as $node)
					{
						$obj_data =& $this->ilias->obj_factory->getInstanceByRefId($node["child"]);
						$obj_data->createReference();
						// TODO: $node["parent"] is wrong in case of new reference!!!!
						$obj_data->putInTree($node["parent"]);
						$obj_data->setPermissions($node["parent"]);
					}
				}
			}
		} // END IF 'link & paste'
				
		// save cmd for correct message output after clearing the clipboard
		$last_cmd = $_SESSION["clipboard"]["cmd"];
		
		// clear clipboard
		$this->clearObject();
		
		if ($last_cmd == "cut")
		{
			sendInfo($this->lng->txt("msg_cut_copied"),true);
		}
		else
		{
			sendInfo($this->lng->txt("msg_linked"),true);		
		}

		header("location: adm_object.php?ref_id=".$_GET["ref_id"]);
		exit();
	}

	/**
	* clear clipboard and go back to last object
	*
	* @access	public
	*/
	function clearObject()
	{
		session_unregister("clipboard");

		//var_dump("<pre>",$_POST,"</pre>");exit;
		if (isset($_POST["cmd"]["clear"]))
		{
			sendinfo($this->lng->txt("msg_clear_clipboard"),true);
				
			header("location: adm_object.php?ref_id=".$_GET["ref_id"]);
			exit();
		}
	}

	/**
	* cut object(s) out from a container and write the information to clipboard
	*
	* @access	public
	*/
	function cutObject()
	{
		global $rbacsystem;

		if (!isset($_POST["id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}

		// FOR ALL OBJECTS THAT SHOULD BE COPIED
		foreach ($_POST["id"] as $ref_id)
		{
			// GET COMPLETE NODE_DATA OF ALL SUBTREE NODES
			$node_data = $this->tree->getNodeData($ref_id);
			$subtree_nodes = $this->tree->getSubTree($node_data);
			
			$all_node_data[] = $node_data;
			$all_subtree_nodes[] = $subtree_nodes;

			// CHECK DELETE PERMISSION OF ALL OBJECTS IN ACTUAL SUBTREE
			foreach ($subtree_nodes as $node)
			{
				if (!$rbacsystem->checkAccess('delete',$node["ref_id"]))
				{
					$no_cut[] = $node["ref_id"];
				}
			}
		}
		// IF THERE IS ANY OBJECT WITH NO PERMISSION TO 'delete'
		if (count($no_cut))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_cut")." ".implode(',',$this->getTitlesByRefId($no_cut)),
									 $this->ilias->error_obj->MESSAGE);
		}
		$_SESSION["clipboard"]["parent"] = $_GET["ref_id"];
		$_SESSION["clipboard"]["cmd"] = key($_POST["cmd"]);
		$_SESSION["clipboard"]["ref_ids"] = $_POST["id"];
		
		sendinfo($this->lng->txt("msg_cut_clipboard"),true);

		header("location: adm_object.php?ref_id=".$_GET["ref_id"]);
		exit();
	}
	/**
	* create an new reference of an object in tree
	* it's like a hard link of unix
	*
	* @access	public
	*/
	function linkObject()
	{
		global $clipboard, $rbacsystem, $rbacadmin;

		if (!isset($_POST["id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}

		// CHECK ACCESS
		foreach ($_POST["id"] as $ref_id)
		{
			if (!$rbacsystem->checkAccess('delete',$ref_id))
			{
				$no_cut[] = $ref_id;
			}

			$object =& $this->ilias->obj_factory->getInstanceByRefId($ref_id);
			$actions = $this->objDefinition->getActions($object->getType());
			
			if ($actions["link"]["exec"] == 'false')
			{
				$no_link[] = $object->getType();
			}
		}

		// NO ACCESS
		if (count($no_cut))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_link")." ".
									 implode(',',$no_cut),$this->ilias->error_obj->MESSAGE);
		}

		if (count($no_link))
		{
			$this->ilias->raiseError($this->lng->txt("msg_not_possible_link")." ".
									 implode(',',$no_link),$this->ilias->error_obj->MESSAGE);
		}

		// WRITE TO CLIPBOARD
		$clipboard["parent"] = $_GET["ref_id"];
		$clipboard["cmd"] = key($_POST["cmd"]);
		
		foreach ($_POST["id"] as $ref_id)
		{
			$clipboard["ref_ids"][] = $ref_id;
		}

		$_SESSION["clipboard"] = $clipboard;
	
		sendinfo($this->lng->txt("msg_link_clipboard"),true);

		header("location: adm_object.php?ref_id=".$_GET["ref_id"]);
		exit();

	} // END COPY

	/**
	* clone Object subtree
	*
	* @access	private
	* @param	integer	reference id
	*/
	function cloneObject($a_ref_id)
	{
		global $rbacsystem;

		if(!is_array($_SESSION["clipboard"]["ref_ids"]))
		{
			$this->ilias->raiseError($this->lng->txt("msg_error_copy"),$this->ilias->error_obj->MESSAGE);
		}
		
		foreach ($_SESSION["clipboard"]["ref_ids"] as $ref_id)
		{
			// CHECK SOME THINGS
			$obj_data =& $this->ilias->obj_factory->getInstanceByRefId($ref_id);
			$data = $this->tree->getNodeData($ref_id);

			// CHECK ACCESS
			if (!$rbacsystem->checkAccess('create',$a_ref_id,$obj_data->getType()))
			{
				$no_paste[] = $ref_id;
			}

			// CHECK IF PASTE OBJECT SHALL BE CHILD OF ITSELF
			if ($this->tree->isGrandChild($ref_id,$a_ref_id))
			{
				$is_child[] = $ref_id;
			}

			// CHECK IF OBJECT IS ALLOWED TO CONTAIN PASTED OBJECT AS SUBOBJECT
			$object =& $this->ilias->obj_factory->getInstanceByRefId($a_ref_id);

			if (!in_array($obj_data->getType(),array_keys($this->objDefinition->getSubObjects($object->getType()))))
			{
				$not_allowed_subobject[] = $obj_data->getType();
			}
		}
		if (count($no_paste))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_create")." ".implode(',',$this->getTitlesByRefId($no_paste)),
									 $this->ilias->error_obj->MESSAGE);
		}

		if (count($is_child))
		{
			$this->ilias->raiseError($this->lng->txt("msg_not_in_itself")." ".implode(',',$this->getTitlesByRefId($is_child)),
									 $this->ilias->error_obj->MESSAGE);
		}

		if (count($not_allowed_subobject))
		{
			$this->ilias->raiseError($this->lng->txt("msg_may_not_contain")." ".
									 implode(',',$not_allowed_subobject),
									 $this->ilias->error_obj->MESSAGE);
		}

		// NOW CLONE ALL OBJECTS
		// THEREFORE THE CLONE METHOD OF ALL OBJECTS IS CALLED
		foreach ($_SESSION["clipboard"]["ref_ids"] as $id)
		{
			$this->cloneNodes($id,$this->ref_id);
		}

		$this->clearObject();

		sendinfo($this->lng->txt("msg_cloned"),true);

		header("location: adm_object.php?ref_id=".$_GET["ref_id"]);
		exit();
	}

	/**
	* clone all nodes
	*
	* @access	public
	* @param	integer ref_id of source object
	* @param	integer ref_id of destination object
	* @param    boolean 
	*/
	function cloneNodes($a_source_id,$a_dest_id)
	{
		// FIRST CLONE THE OBJECT (THEREFORE THE CLONE METHOD OF EACH OBJECT IS CALLED
		$source_obj =& $this->ilias->obj_factory->getInstanceByRefId($a_source_id);
		$new_ref_id = $source_obj->clone($a_dest_id);
		unset($source_obj);

		// GET ALL CHILDS OF SOURCE OBJECT AND CALL THIS METHOD FOR OF THEM
		foreach ($this->tree->getChilds($a_source_id) as $child)
		{
			// STOP IF CHILD OBJECT IS ROLE FOLDER SINCE IT DOESN'T MAKE SENSE TO CLONE LOCAL ROLES
			if ($child["type"] != 'rolf')
			{
				$this->cloneNodes($child["ref_id"],$new_ref_id);
			}
		}

		return true;
	}

	/**
	* get object back from trash
	*
	* @access	public
	*/
	function undeleteObject()
	{
		global $rbacsystem;

		// AT LEAST ONE OBJECT HAS TO BE CHOSEN.
		if (!isset($_POST["trash_id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}

		foreach ($_POST["trash_id"] as $id)
		{
			$obj_data =& $this->ilias->obj_factory->getInstanceByRefId($id);

			if (!$rbacsystem->checkAccess('create',$_GET["ref_id"],$obj_data->getType()))
			{
				$no_create[] = $id;
			}
		}

		if (count($no_create))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_paste")." ".
									 implode(',',$no_paste),$this->ilias->error_obj->MESSAGE);
		}

		foreach ($_POST["trash_id"] as $id)
		{
			// INSERT AND SET PERMISSIONS
			$this->insertSavedNodes($id,$_GET["ref_id"],-(int) $id);
			// DELETE SAVED TREE
			$saved_tree = new ilTree(-(int)$id);
			$saved_tree->deleteTree($saved_tree->getNodeData($id));
		}
		
		sendInfo($this->lng->txt("msg_undeleted"),true);

		header("location: adm_object.php?ref_id=".$_GET["ref_id"]);
		exit();
	}

	/**
	* recursive method to insert all saved nodes of the clipboard
	* (maybe this function could be moved to a rbac class ?)
	*
	* @access	private
	* @param	integer
	* @param	integer
	* @param	integer
	*/
	function insertSavedNodes($a_source_id,$a_dest_id,$a_tree_id)
	{
		global $rbacadmin, $rbacreview;

		$this->tree->insertNode($a_source_id,$a_dest_id);

		// SET PERMISSIONS
		$parentRoles = $rbacreview->getParentRoleIds($a_dest_id);
		$obj =& $this->ilias->obj_factory->getInstanceByRefId($a_source_id);

		foreach ($parentRoles as $parRol)
		{
			$ops = $rbacreview->getOperationsOfRole($parRol["obj_id"], $obj->getType(), $parRol["parent"]);
			$rbacadmin->grantPermission($parRol["obj_id"],$ops,$a_source_id);
		}

		$saved_tree = new ilTree($a_tree_id);
		$childs = $saved_tree->getChilds($a_source_id);

		foreach ($childs as $child)
		{
			$this->insertSavedNodes($child["child"],$a_source_id,$a_tree_id);
		}
	}

	/**
	* confirmed deletion if object -> objects are moved to trash
	*
	* However objects are only removed from tree!! That means that the objects
	* itself stay in the database but are not linked in any context within the system.
	* Trash Bin Feature: Objects can be refreshed in trash
	*
	* @access	public
	*/
	function confirmedDeleteObject()
	{
		global $rbacsystem, $rbacadmin;
	
		// TODO: move checkings to deleteObject
		// TODO: cannot distinguish between obj_id from ref_id with the posted IDs.
		// change the form field and use instead of 'id' 'ref_id' and 'obj_id'. Then switch with varname
		
		// AT LEAST ONE OBJECT HAS TO BE CHOSEN.
		if (!isset($_SESSION["saved_post"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}

		// FOR ALL SELECTED OBJECTS
		foreach ($_SESSION["saved_post"] as $id)
		{
			// GET COMPLETE NODE_DATA OF ALL SUBTREE NODES
			$node_data = $this->tree->getNodeData($id);
			$subtree_nodes = $this->tree->getSubTree($node_data);

			$all_node_data[] = $node_data;
			$all_subtree_nodes[] = $subtree_nodes;

			// CHECK DELETE PERMISSION OF ALL OBJECTS
			foreach ($subtree_nodes as $node)
			{
				if (!$rbacsystem->checkAccess('delete',$node["child"]))
				{
					$not_deletable[] = $node["child"];
					$perform_delete = false;
				}
			}
		}

		// IF THERE IS ANY OBJECT WITH NO PERMISSION TO DELETE
		if (count($not_deletable))
		{
			$not_deletable = implode(',',$not_deletable);
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_delete")." ".
									 $not_deletable,$this->ilias->error_obj->MESSAGE);
		}

		// DELETE THEM
		if (!$all_node_data[0]["type"])
		{
			// OBJECTS ARE NO 'TREE OBJECTS'
			if ($rbacsystem->checkAccess('delete',$_GET["ref_id"]))
			{
				foreach($_SESSION["saved_post"] as $id)
				{
					$obj =& $this->ilias->obj_factory->getInstanceByObjId($id);
					$obj->delete();
				}
			}
			else
			{
				$this->ilias->raiseError($this->lng->txt("no_perm_delete"),$this->ilias->error_obj->MESSAGE);
			}
		}
		else
		{
			// SAVE SUBTREE AND DELETE SUBTREE FROM TREE
			foreach ($_SESSION["saved_post"] as $id)
			{
				// DELETE OLD PERMISSION ENTRIES
				$subnodes = $this->tree->getSubtree($this->tree->getNodeData($id));

				foreach ($subnodes as $subnode)
				{
					$rbacadmin->revokePermission($subnode["child"]);
				}

				$this->tree->saveSubTree($id);
				$this->tree->deleteTree($this->tree->getNodeData($id));
			}
		}

		// Feedback
		sendInfo($this->lng->txt("info_deleted"),true);

		header("location: adm_object.php?ref_id=".$_GET["ref_id"]);
		exit();
	}

	/**
	* cancel deletion of object
	*
	* @access	public
	*/
	function cancelDeleteObject()
	{
		session_unregister("saved_post");
		
		sendInfo($this->lng->txt("msg_cancel_delete"),true);
		
		header("location: adm_object.php?ref_id=".$_GET["ref_id"]);
		exit();

	}

	/**
	* remove objects from trash bin and all entries therefore every object needs a specific deleteObject() method
	*
	* @access	public
	*/
	function removeFromSystemObject()
	{
		global $rbacsystem;

		// AT LEAST ONE OBJECT HAS TO BE CHOSEN.
		if (!isset($_POST["trash_id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}

		foreach ($_POST["trash_id"] as $id)
		{
			//$obj_data = getObject($id);
			//$obj_data =& $this->ilias->obj_factory->getInstanceByObjId($id);

			if (!$rbacsystem->checkAccess('delete',$_GET["ref_id"]))
			{
				$no_delete[] = $id;
			}
		}

		if (count($no_delete))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_delete")." ".
									 implode(',',$no_delete),$this->ilias->error_obj->MESSAGE);
		}

		// DELETE THEM
		foreach ($_POST["trash_id"] as $id)
		{

			// GET COMPLETE NODE_DATA OF ALL SUBTREE NODES
			$saved_tree = new ilTree(-(int)$id);
			$node_data = $saved_tree->getNodeData($id);
			$subtree_nodes = $saved_tree->getSubTree($node_data);

			// FIRST DELETE AL ENTRIES IN TREE
			$this->tree->deleteTree($node_data);

			foreach ($subtree_nodes as $node)
			{
				// Todo: I think it must be distinguished between obj and ref ids here somehow
				$node_obj =& $this->ilias->obj_factory->getInstanceByRefId($node["ref_id"]);
				$node_obj->delete();
				//$this->object->delete($node["obj_id"],$node["parent"]);
			}
		}
		
		sendInfo($this->lng->txt("msg_removed"),true);

		header("location: adm_object.php?ref_id=".$_GET["ref_id"]."&cmd=trash");
		exit();
	}

	/**
	* create new object form
	*
	* @access	public
	*/
	function createObject()
	{
		global $rbacsystem;

		// TODO: get rid of $_GET variable
		if (!$rbacsystem->checkAccess("create", $_GET["ref_id"], $_POST["new_type"]))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}
		else
		{
			// fill in saved values in case of error
			$data = array();
			$data["fields"] = array();
			$data["fields"]["title"] = $_SESSION["error_post_vars"]["Fobject"]["title"];
			$data["fields"]["desc"] = $_SESSION["error_post_vars"]["Fobject"]["desc"];

			$this->getTemplateFile("edit");

			foreach ($data["fields"] as $key => $val)
			{
				$this->tpl->setVariable("TXT_".strtoupper($key), $this->lng->txt($key));
				$this->tpl->setVariable(strtoupper($key), $val);
				$this->tpl->parseCurrentBlock();
			}

			$this->tpl->setVariable("FORMACTION", $this->getFormAction("save","adm_object.php?cmd=save&ref_id=".$_GET["ref_id"]."&new_type=".$_POST["new_type"]));
			$this->tpl->setVariable("TXT_SAVE", $this->lng->txt("save"));
			$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));
		}
	}

	/**
	* save object
	*
	* @access	public
	*/
	function saveObject()
	{
		global $rbacsystem, $rbacreview, $rbacadmin;

		if (!$rbacsystem->checkAccess("create", $_GET["ref_id"], $_GET["new_type"]))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"), $this->ilias->error_obj->MESSAGE);
		}
		else
		{
			// create and insert object in objecttree
			$class_name = "ilObj".$this->objDefinition->getClassName($_GET["new_type"]);
			include_once("classes/class.".$class_name.".php");
			$newObj = new $class_name();
			$newObj->setType($_GET["new_type"]);
			$newObj->setTitle($_POST["Fobject"]["title"]);
			$newObj->setDescription($_POST["Fobject"]["desc"]);
			$newObj->create();
			$newObj->createReference();
			$newObj->putInTree($_GET["ref_id"]);
			$newObj->setPermissions($_GET["ref_id"]);
			unset($newObj);
		}

		sendInfo($this->lng->txt("msg_obj_created"),true);

		header("Location:".$this->getReturnLocation("save","adm_object.php?".$this->link_params));
		exit();
	}

	/**
	* import new object form
	*
	* @access	public
	*/
	function importObject()
	{
		global $rbacsystem;

		// CHECK ACCESS 'write' of role folder
		// TODO: new_type will never be checked, if queried operation is not 'create'
		if (!$rbacsystem->checkAccess('write', $_GET["ref_id"], $_POST["new_type"]))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->WARNING);
		}

		$imp_obj =$this->objDefinition->getImportObjects($this->object->getType());

		if (!in_array($_POST["new_type"], $imp_obj))
		{
			$this->ilias->raiseError($this->lng->txt("no_import_available").
				" ".$this->lng->txt("obj_".$_POST["new_type"]),
				$this->ilias->error_obj->MESSAGE);
		}
		// no general implementation of this feature, the specialized classes
		// must provide further processing
	}


	/**
	* edit object
	*
	* @access	public
	*/
	function editObject()
	{
		global $rbacsystem;

		if (!$rbacsystem->checkAccess("write", $this->ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilias->error_obj->MESSAGE);
		}
		else
		{
			$fields = array();
			
			if ($_SESSION["error_post_vars"])
			{
				// fill in saved values in case of error
				$fields["title"] = $_SESSION["error_post_vars"]["Fobject"]["title"];
				$fields["desc"] = $_SESSION["error_post_vars"]["Fobject"]["desc"];			
			}
			else
			{
				$fields["title"] = $this->object->getTitle();
				$fields["desc"] = $this->object->getDescription();			
			}

			$this->displayEditForm($fields);
		}
	}

	/**
	* display edit form (usually called by editObject)
	*
	* @access	private
	* @param	array	$fields		key/value pairs of input fields
	*/
	function displayEditForm($fields)
	{
		$this->getTemplateFile("edit");

		foreach ($fields as $key => $val)
		{
			$this->tpl->setVariable("TXT_".strtoupper($key), $this->lng->txt($key));
			$this->tpl->setVariable(strtoupper($key), $val);
			$this->tpl->parseCurrentBlock();
		}

		$obj_str = ($this->call_by_reference) ? "" : "&obj_id=".$this->obj_id;

		$this->tpl->setVariable("FORMACTION", "adm_object.php?ref_id=".$this->ref_id.$obj_str."&cmd=update");
		$this->tpl->setVariable("TXT_SAVE", $this->lng->txt("save"));
		$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));
	}


	/**
	* updates object entry in object_data
	*
	* @access	public
	*/
	function updateObject()
	{
		global $rbacsystem;

		if (!$rbacsystem->checkAccess("write", $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilias->error_obj->MESSAGE);
		}
		else
		{
			$this->object->setTitle($_POST["Fobject"]["title"]);
			$this->object->setDescription($_POST["Fobject"]["desc"]);
			$this->update = $this->object->update();
		}

		sendInfo($this->lng->txt("msg_obj_modified"),true);
		
		header("Location: adm_object.php?ref_id=".$this->ref_id);
		exit();
	}

	/**
	* show permissions of current node
	*
	* @access	public
	*/
	function permObject()
	{
		global $log, $rbacsystem, $rbacreview, $rbacadmin;

#		static $num = 0;

		if (!$rbacsystem->checkAccess("edit permission", $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_perm"),$this->ilias->error_obj->WARNING);
		}
		else
		{
			// only display superordinate roles; local roles with other scope are not displayed
			$parentRoles = $rbacreview->getParentRoleIds($this->object->getRefId());

			$data = array();

			// GET ALL LOCAL ROLE IDS
			$role_folder = $rbacreview->getRoleFolderOfObject($this->object->getRefId());

			$local_roles = array();

			if ($role_folder)
			{
				$local_roles = $rbacreview->getRolesOfRoleFolder($role_folder["ref_id"]);
			}

			foreach ($parentRoles as $r)
			{
				$data["rolenames"][] = $r["title"];

				if (!in_array($r["obj_id"],$local_roles))
				{
					$data["check_inherit"][] = ilUtil::formCheckBox(0,"stop_inherit[]",$r["obj_id"]);
				}
				else
				{
					// don't display a checkbox for local roles
					if ($rbacreview->isAssignable($r["obj_id"],$role_folder["ref_id"]))
					{
						$data["check_inherit"][] = "&nbsp;";
					}
					else
					{
						// linked local roles with stopped inheritance
						$data["check_inherit"][] = ilUtil::formCheckBox(1,"stop_inherit[]",$r["obj_id"]);
					}
				}
			}

			$ope_list = getOperationList($this->object->getType());

			// BEGIN TABLE_DATA_OUTER
			foreach ($ope_list as $key => $operation)
			{
				$opdata = array();

				// skip 'create' permission because an object permission 'create' makes no sense
				if ($operation["operation"] != "create")
				{
					$opdata["name"] = $operation["operation"];

					foreach ($parentRoles as $role)
					{
						$checked = $rbacsystem->checkPermission($this->object->getRefId(), $role["obj_id"],$operation["operation"],$_GET["parent"]);
						// Es wird eine 2-dim Post Variable �bergeben: perm[rol_id][ops_id]
						$box = ilUtil::formCheckBox($checked,"perm[".$role["obj_id"]."][]",$operation["ops_id"]);
						$opdata["values"][] = $box;
					}

					$data["permission"][] = $opdata;
				}
			}
		}

		// TODO: needs review. $_GET[parent] doesn't exists!!!!
		$rolf_data = $rbacreview->getRoleFolderOfObject($this->object->getRefId());
		$permission = $rolf_data ? 'write' : 'create';
		$rolf_id = $rolf_data["obj_id"] ? $rolf_data["obj_id"] : $this->object->getRefId();
		$rolf_parent = $role_data["parent"] ? $rolf_data["parent"] : $_GET["parent"];

		if ($rbacsystem->checkAccess("edit permission", $this->object->getRefId()) &&
		   $rbacsystem->checkAccess($permission, $rolf_id, "rolf"))
		{
			// Check if object is able to contain role folder
			$child_objects = $rbacreview->getModules($this->object->getType(), $this->object->getRefId());

			if ($child_objects["rolf"])
			{
				$data["local_role"]["child"] = $this->object->getRefId();
				$data["local_role"]["parent"] = $_GET["parent"];
			}
		}

		/////////////////////
		// START DATA OUTPUT
		/////////////////////

		$this->getTemplateFile("perm");
		$this->tpl->setCurrentBlock("tableheader");
		$this->tpl->setVariable("TXT_PERMISSION", $this->lng->txt("permission"));
		$this->tpl->setVariable("TXT_ROLES", $this->lng->txt("roles"));
		$this->tpl->parseCurrentBlock();

		$num = 0;

		foreach($data["rolenames"] as $name)
		{
			// BLOCK ROLENAMES
			$this->tpl->setCurrentBlock("ROLENAMES");
			$this->tpl->setVariable("ROLE_NAME",$name);
			$this->tpl->parseCurrentBlock();

			// BLOCK CHECK INHERIT
			if ($this->objDefinition->stopInheritance($this->type))
			{
				$this->tpl->setCurrentBLock("CHECK_INHERIT");
				$this->tpl->setVariable("CHECK_INHERITANCE",$data["check_inherit"][$num]);
				$this->tpl->parseCurrentBlock();
			}

			$num++;
		}

		// save num for required column span and the end of parsing
		$colspan = $num + 1;
		$num = 0;

		// offer option 'stop inheritance' only to those objects where this option is permitted
		if ($this->objDefinition->stopInheritance($this->type))
		{
			$this->tpl->setCurrentBLock("STOP_INHERIT");
			$this->tpl->setVariable("TXT_STOP_INHERITANCE", $this->lng->txt("stop_inheritance"));
			$this->tpl->parseCurrentBlock();
		}

		foreach ($data["permission"] as $ar_perm)
		{
			foreach ($ar_perm["values"] as $box)
			{
				// BEGIN TABLE CHECK PERM
				$this->tpl->setCurrentBlock("CHECK_PERM");
				$this->tpl->setVariable("CHECK_PERMISSION",$box);
				$this->tpl->parseCurrentBlock();
				// END CHECK PERM
			}

			// BEGIN TABLE DATA OUTER
			$this->tpl->setCurrentBlock("TABLE_DATA_OUTER");
			$css_row = ilUtil::switchColor($num++, "tblrow1", "tblrow2");
			$this->tpl->setVariable("CSS_ROW",$css_row);
			$this->tpl->setVariable("PERMISSION", $ar_perm["name"]);
			$this->tpl->parseCurrentBlock();
			// END TABLE DATA OUTER
		}

		// ADD LOCAL ROLE
		if ($this->object->getRefId() != ROLE_FOLDER_ID)
		{
			$this->tpl->setCurrentBlock("LOCAL_ROLE");
			$this->tpl->setVariable("TXT_ADD", $this->lng->txt("add"));
			$this->tpl->setVariable("MESSAGE_BOTTOM", $this->lng->txt("you_may_add_local_roles"));
			$this->tpl->setVariable("FORMACTION_LR",$this->getFormAction("addRole", "adm_object.php?ref_id=".$_GET["ref_id"]."&cmd=addRole"));
			$this->tpl->parseCurrentBlock();
		}

		// PARSE BLOCKFILE
		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("FORMACTION",
			$this->getFormAction("permSave","adm_object.php?".$this->link_params."&cmd=permSave"));
		$this->tpl->setVariable("TXT_SAVE", $this->lng->txt("save"));
		$this->tpl->setVariable("COL_ANZ",$colspan);
		$this->tpl->parseCurrentBlock();
	}

	/**
	* get form action for command (command is method name without "Object", e.g. "perm")
	* @param	string		$a_cmd		command
	* @param	string		$a_cmd		default formaction (is returned, if no special
	*									formaction was set)
	* @access	public
	* @return	string
	*/
	function getFormAction($a_cmd, $a_formaction ="")
	{
		if ($this->formaction[$a_cmd] != "")
		{
			return $this->formaction[$a_cmd];
		}
		else
		{
			return $a_formaction;
		}
	}

	/**
	* set specific form action for command
	*
	* @param	string		$a_cmd		command
	* @param	string		$a_cmd		default formaction (is returned, if no special
	*									formaction was set)
	* @access	public 
	*/
	function setFormAction($a_cmd, $a_formaction)
	{
		$this->formaction[$a_cmd] = $a_formaction;
	}

	/**
	* get return location for command (command is method name without "Object", e.g. "perm")
	* @param	string		$a_cmd		command
	* @param	string		$a_cmd		default return location (is returned, if no special
	*									return location was set)
	* @access	public 
	*/
	function getReturnLocation($a_cmd, $a_location ="")
	{
		if ($this->return_location[$a_cmd] != "")
		{
			return $this->return_location[$a_cmd];
		}
		else
		{
			return $a_location;
		}
	}

	/**
	* set specific return location for command
	* @param	string		$a_cmd		command
	* @param	string		$a_cmd		default return location (is returned, if no special
	*									return location was set)
	* @access	public 
	*/
	function setReturnLocation($a_cmd, $a_location)
	{
		$this->return_location[$a_cmd] = $a_location;
	}

	/**
	* save permissions
	*
	* @access	public
	*/
	function permSaveObject()
	{
		global $rbacsystem, $rbacreview, $rbacadmin;

		// first save the new permission settings for all roles
		$rbacadmin->revokePermission($_GET["ref_id"]);

		foreach ($_POST["perm"] as $key => $new_role_perms)
		{
			// $key enthaelt die aktuelle Role_Id
			$rbacadmin->grantPermission($key,$new_role_perms,$_GET["ref_id"]);
		}

		// Wenn die Vererbung der Rollen Templates unterbrochen werden soll,
		// muss folgendes geschehen:
		// - existiert kein RoleFolder, wird er angelegt und die Rechte aus den Permission Templates ausgelesen
		// - existiert die Rolle im aktuellen RoleFolder werden die Permission Templates dieser Rolle angezeigt
		// - existiert die Rolle nicht im aktuellen RoleFolder wird sie dort angelegt
		//   und das Permission Template an den Wert des n�chst h�her gelegenen Permission Templates angepasst

		// get rolefolder data if a rolefolder already exists
		$rolf_data = $rbacreview->getRoleFolderOfObject($_GET["ref_id"]);

		if ($_POST["stop_inherit"])
		{
			// rolefolder doesn't exists, so create one
			if (empty($rolf_data["child"]))
			{
				// CHECK ACCESS 'create' rolefolder
				if (!$rbacsystem->checkAccess('create',$_GET["ref_id"],'rolf'))
				{
					$this->ilias->raiseError($this->lng->txt("msg_no_perm_create_rolf"),$this->ilias->error_obj->WARNING);
				}
				else
				{
					include_once ("classes/class.ilObjRoleFolder.php");
					$rolfObj = new ilObjRoleFolder();
					$rolfObj->setTitle("Local roles");
					$rolfObj->setDescription("Role Folder of object no. ".$_GET["ref_id"]);
					$rolfObj->create();
					$rolfObj->createReference();
					$rolfObj->putInTree($_GET["ref_id"]);
					$rolfObj->setPermissions($_GET["ref_id"]);
					unset($rolfObj);
			
					// now load rolefolder data again
					$rolf_data = $rbacreview->getRoleFolderOfObject($_GET["ref_id"]);
				}
			}

			// CHECK ACCESS 'write' of role folder
			if (!$rbacsystem->checkAccess('write',$rolf_data["child"]))
			{
				$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilias->error_obj->WARNING);
			}
			else
			{
				foreach ($_POST["stop_inherit"] as $stop_inherit)
				{
					$roles_of_folder = $rbacreview->getRolesOfRoleFolder($rolf_data["ref_id"]);

					// create role entries for roles with stopped inheritance
					if (!in_array($stop_inherit,$roles_of_folder))
					{
						$parentRoles = $rbacreview->getParentRoleIds($rolf_data["child"]);
						$rbacadmin->copyRolePermission($stop_inherit,$parentRoles[$stop_inherit]["parent"],
													   $rolf_data["child"],$stop_inherit);
						$rbacadmin->assignRoleToFolder($stop_inherit,$rolf_data["child"],$_GET["ref_id"],'n');
					}
				}
			}// END FOREACH
		}// END STOP INHERIT
		elseif 	(!empty($rolf_data["child"]))
		{
			// ok. if the rolefolder is not empty, delete the local roles
			//if (!empty($roles_of_folder = $rbacreview->getRolesOfRoleFolder($rolf_data["ref_id"])));
			//{
				//foreach ($roles_of_folder as $obj_id)
				//{
					//$rolfObj =& $this->ilias->obj_factory->getInstanceByRefId($rolf_data["child"]);
					//$rolfObj->delete();
					//unset($rolfObj);
				//}
			//}
		}
	
		sendinfo($this->lng->txt("saved_successfully"),true);

		header("Location: ".$this->getReturnLocation("permSave","adm_object.php?ref_id=".$_GET["ref_id"]."&cmd=perm"));
		exit();
	}

	/**
	* display object owner
	*
	* @access	public
	*/
	function ownerObject()
	{
		$this->getTemplateFile("owner");
		$this->tpl->setVariable("OWNER_NAME", $this->object->getOwnerName());
		$this->tpl->setVariable("TXT_OBJ_OWNER", $this->lng->txt("obj_owner"));
		$this->tpl->setVariable("CMD","update");
		$this->tpl->parseCurrentBlock();
	}

	/**
	* display object list
	*
	* @access	public
 	*/
	function displayList()
	{
		include_once "./classes/class.ilTableGUI.php";

		// load template for table
		$this->tpl->addBlockfile("ADM_CONTENT", "adm_content", "tpl.table.html");
		// load template for table content data
		$this->tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.obj_tbl_rows.html");

		$num = 0;

		$obj_str = ($this->call_by_reference) ? "" : "&obj_id=".$this->obj_id;
		$this->tpl->setVariable("FORMACTION", "adm_object.php?ref_id=".$this->ref_id."$obj_str&cmd=gateway");

		// create table
		$tbl = new ilTableGUI();

		// title & header columns
		$tbl->setTitle($this->object->getTitle(),"icon_".$this->object->getType()."_b.gif",$this->lng->txt("obj_".$this->object->getType()));
		$tbl->setHelp("tbl_help.php","icon_help.gif",$this->lng->txt("help"));
		
		foreach ($this->data["cols"] as $val)
		{
			$header_names[] = $this->lng->txt($val);
		}
		
		$tbl->setHeaderNames($header_names);
		
		$header_params = array("ref_id" => $this->ref_id);
		$tbl->setHeaderVars($this->data["cols"],$header_params);
		//$tbl->setColumnWidth(array("7%","7%","15%","31%","6%","17%"));
		
		// control
		$tbl->setOrderColumn($_GET["sort_by"]);
		$tbl->setOrderDirection($_GET["sort_order"]);
		$tbl->setLimit($_GET["limit"]);
		$tbl->setOffset($_GET["offset"]);
		$tbl->setMaxCount($this->maxcount);

		// display action buttons only if at least 1 object in the list can be manipulated
		// temp. deactivated
		/*if (is_array($this->data["data"][0]))
		{
			foreach ($this->data["ctrl"] as $val)
			{
				if ($this->objDefinition->hasCheckbox($val["type"]))
				{
					// SHOW VALID ACTIONS
					$this->tpl->setVariable("COLUMN_COUNTS",count($this->data["cols"]));
					$this->showActions(true);
					break;
				}				
			}
		}*/
		$this->tpl->setVariable("COLUMN_COUNTS",count($this->data["cols"]));		
		$this->showActions(true);
		
		// footer
		$tbl->setFooter("tblfooter",$this->lng->txt("previous"),$this->lng->txt("next"));
		//$tbl->disable("footer");

		// render table
		$tbl->render();

		if (is_array($this->data["data"][0]))
		{
			//table cell
			for ($i=0; $i < count($this->data["data"]); $i++)
			{
				$data = $this->data["data"][$i];
				$ctrl = $this->data["ctrl"][$i];

				// color changing
				$css_row = ilUtil::switchColor($i+1,"tblrow1","tblrow2");

				// surpress checkbox for particular object types
				if (!$this->objDefinition->hasCheckbox($ctrl["type"]))
				{
					$this->tpl->touchBlock("empty_cell");
				}
				else
				{
					// TODO: this type depending 'if' could become really a problem!!
					if ($ctrl["type"] == "usr" or $ctrl["type"] == "role" or $ctrl["type"] == "rolt")
					{
						$link_id = $ctrl["obj_id"];
					}
					else
					{
						$link_id = $ctrl["ref_id"];
					}

					$this->tpl->setCurrentBlock("checkbox");
					$this->tpl->setVariable("CHECKBOX_ID", $link_id);
					$this->tpl->setVariable("CSS_ROW", $css_row);
					$this->tpl->parseCurrentBlock();
				}

				$this->tpl->setCurrentBlock("table_cell");
				$this->tpl->setVariable("CELLSTYLE", "tblrow1");
				$this->tpl->parseCurrentBlock();

				foreach ($data as $key => $val)
				{
					//build link
					$link = "adm_object.php?";

					if ($_GET["type"] == "lo" && $key == "type")
					{
						$link = "lo_view.php?";
					}

					$n = 0;

					foreach ($ctrl as $key2 => $val2)
					{
						$link .= $key2."=".$val2;

						if ($n < count($ctrl)-1)
						{
					    	$link .= "&";
							$n++;
						}
					}

					if ($key == "title" || $key == "type")
					{
						$this->tpl->setCurrentBlock("begin_link");
						$this->tpl->setVariable("LINK_TARGET", $link);

						if ($_GET["type"] == "lo" && $key == "type")
						{
							$this->tpl->setVariable("NEW_TARGET", "\" target=\"lo_view\"");
						}

						$this->tpl->parseCurrentBlock();
						$this->tpl->touchBlock("end_link");
					}

					// process clipboard information"
					if (isset($_SESSION["clipboard"]))
					{
						$cmd = $_SESSION["clipboard"]["cmd"];
						$parent = $_SESSION["clipboard"]["parent"];

						foreach ($_SESSION["clipboard"]["ref_ids"] as $clip_id)
						{
							if ($ctrl["ref_id"] == $clip_id)
							{
								if ($cmd == "cut" and $key == "title")
								{
									$val = "<del>".$val."</del>";
								}
								
								if ($cmd == "copy" and $key == "title")
								{
									$val = "<font color=\"green\">+</font>  ".$val;
								}

								if ($cmd == "link" and $key == "title")
								{
									$val = "<font color=\"black\"><</font> ".$val;
								}
							}
						}
					}

					$this->tpl->setCurrentBlock("text");

					if ($key == "type")
					{
						$val = ilUtil::getImageTagByType($val,$this->tpl->tplPath);						
					}

					$this->tpl->setVariable("TEXT_CONTENT", $val);					
					$this->tpl->parseCurrentBlock();

					$this->tpl->setCurrentBlock("table_cell");
					$this->tpl->parseCurrentBlock();

				} //foreach

				$this->tpl->setCurrentBlock("tbl_content");
				$this->tpl->setVariable("CSS_ROW", $css_row);
				$this->tpl->parseCurrentBlock();
			} //for

		} //if is_array
		else
		{
			$this->tpl->setCurrentBlock("notfound");
			$this->tpl->setVariable("TXT_OBJECT_NOT_FOUND", $this->lng->txt("obj_not_found"));
			$this->tpl->setVariable("NUM_COLS", $num);
			$this->tpl->parseCurrentBlock();
		}
	}

	/**
	* list childs of current object
	*
	* @access	public
	*/
	function viewObject()
	{
		global $rbacsystem;

		if (!$rbacsystem->checkAccess("visible,read",$this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}

		//prepare objectlist
		$this->objectList = array();
		$this->data["data"] = array();
		$this->data["ctrl"] = array();
		$this->data["cols"] = array("", "type", "title", "description", "last_change");

		$childs = $this->tree->getChilds($_GET["ref_id"], $_GET["order"], $_GET["direction"]);

		foreach ($childs as $key => $val)
	    {
			// visible
			if (!$rbacsystem->checkAccess("visible",$val["ref_id"]))
			{
				continue;
			}

			//visible data part
			$this->data["data"][] = array(
										"type" => $val["type"],
										"title" => $val["title"],
										"description" => $val["desc"],
										"last_change" => $val["last_update"],
										"ref_id" => $val["ref_id"]
										);

			//control information is set below

	    } //foreach

		$this->maxcount = count($this->data["data"]);
		// sorting array
		include_once "./include/inc.sort.php";
		$this->data["data"] = sortArray($this->data["data"],$_GET["sort_by"],$_GET["sort_order"]);
		$this->data["data"] = array_slice($this->data["data"],$_GET["offset"],$_GET["limit"]);

		// now compute control information
		foreach ($this->data["data"] as $key => $val)
		{
			$this->data["ctrl"][$key] = array(
											"type" => $val["type"],
											"ref_id" => $val["ref_id"]
											);

			unset($this->data["data"][$key]["ref_id"]);
						$this->data["data"][$key]["last_change"] = ilFormat::formatDate($this->data["data"][$key]["last_change"]);
		}

		$this->displayList();
	}

	/**
	* display deletion confirmation screen
	* only for referenced objects. For user,role & rolt overwrite this function in the appropriate
	* Object folders classes (ilObjUserFolderGUI,ilObjRoleFolderGUI)
	*
	* @access	public
 	*/
	function deleteObject()
	{
		if (!isset($_POST["id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}
		
		// SAVE POST VALUES
		$_SESSION["saved_post"] = $_POST["id"];

		unset($this->data);
		$this->data["cols"] = array("type", "title", "description", "last_change");

		foreach($_POST["id"] as $id)
		{
			// TODO: cannot distinguish between obj_id from ref_id with the posted IDs.
			// change the form field and use instead of 'id' 'ref_id' and 'obj_id'. Then switch with varname
			//if ($this->call_by_reference)
			//{
				$obj_data =& $this->ilias->obj_factory->getInstanceByRefId($id);
			//}
			//else
			//{
			//	$obj_data =& $this->ilias->obj_factory->getInstanceByObjId($id);
			//}

			$this->data["data"]["$id"] = array(
				"type"        => $obj_data->getType(),
				"title"       => $obj_data->getTitle(),
				"desc"        => $obj_data->getDescription(),
				"last_update" => $obj_data->getLastUpdateDate());
		}

		$this->data["buttons"] = array( "confirmedDelete"  => $this->lng->txt("confirm"),
								  "cancelDelete"  => $this->lng->txt("cancel"));

		$this->getTemplateFile("confirm");

		sendInfo($this->lng->txt("info_delete_sure"));

		$this->tpl->setVariable("FORMACTION", "adm_object.php?ref_id=".$_GET["ref_id"]."&cmd=gateway");
		// BEGIN TABLE HEADER
		foreach ($this->data["cols"] as $key)
		{
			$this->tpl->setCurrentBlock("table_header");
			$this->tpl->setVariable("TEXT",$this->lng->txt($key));
			$this->tpl->parseCurrentBlock();
		}
		// END TABLE HEADER

		// BEGIN TABLE DATA
		$counter = 0;

		foreach ($this->data["data"] as $key => $value)
		{
			// BEGIN TABLE CELL
			foreach($value as $key => $cell_data)
			{
				$this->tpl->setCurrentBlock("table_cell");

				// CREATE TEXT STRING
				if($key == "type")
				{
					$this->tpl->setVariable("TEXT_CONTENT",ilUtil::getImageTagByType($cell_data,$this->tpl->tplPath));
				}
				else
				{
					$this->tpl->setVariable("TEXT_CONTENT",$cell_data);
				}
				$this->tpl->parseCurrentBlock();
			}

			$this->tpl->setCurrentBlock("table_row");
			$this->tpl->setVariable("CSS_ROW",ilUtil::switchColor(++$counter,"tblrow1","tblrow2"));
			$this->tpl->parseCurrentBlock();
			// END TABLE CELL
		}
		// END TABLE DATA

		// BEGIN OPERATION_BTN
		foreach ($this->data["buttons"] as $name => $value)
		{
			$this->tpl->setCurrentBlock("operation_btn");
			$this->tpl->setVariable("BTN_NAME",$name);
			$this->tpl->setVariable("BTN_VALUE",$value);
			$this->tpl->parseCurrentBlock();
		}
	}

	/**
	* show trash content of object
	*
	* @access	public
 	*/
	function trashObject()
	{
		$objects = $this->tree->getSavedNodeData($_GET["ref_id"]);

		if (count($objects) == 0)
		{
			sendInfo($this->lng->txt("msg_trash_empty"));
			$this->data["empty"] = true;
		}
		else
		{
			$this->data["empty"] = false;
			$this->data["cols"] = array("","type", "title", "description", "last_change");

			foreach ($objects as $obj_data)
			{
				$this->data["data"]["$obj_data[child]"] = array(
					"checkbox"    => "",
					"type"        => $obj_data["type"],
					"title"       => $obj_data["title"],
					"desc"        => $obj_data["desc"],
					"last_update" => $obj_data["last_update"]);
			}

			$this->data["buttons"] = array( "undelete"  => $this->lng->txt("btn_undelete"),
									  "removeFromSystem"  => $this->lng->txt("btn_remove_system"));
		}

		$this->getTemplateFile("confirm");

		if ($this->data["empty"] == true)
		{
			return;
		}

		sendInfo($this->lng->txt("info_trash"));

		$this->tpl->setVariable("FORMACTION", "adm_object.php?ref_id=".$_GET["ref_id"]."&cmd=gateway");

		// BEGIN TABLE HEADER
		foreach ($this->data["cols"] as $key)
		{
			$this->tpl->setCurrentBlock("table_header");
			$this->tpl->setVariable("TEXT",$this->lng->txt($key));
			$this->tpl->parseCurrentBlock();
		}
		// END TABLE HEADER

		// BEGIN TABLE DATA
		$counter = 0;

		foreach ($this->data["data"] as $key1 => $value)
		{
			// BEGIN TABLE CELL
			foreach ($value as $key2 => $cell_data)
			{
				$this->tpl->setCurrentBlock("table_cell");
				// CREATE CHECKBOX
				if ($key2 == "checkbox")
				{
					$this->tpl->setVariable("TEXT_CONTENT",ilUtil::formCheckBox(0,"trash_id[]",$key1));
				}

				// CREATE TEXT STRING
				elseif ($key2 == "type")
				{
					$this->tpl->setVariable("TEXT_CONTENT",ilUtil::getImageTagByType($cell_data,$this->tpl->tplPath));
				}
				else
				{
					$this->tpl->setVariable("TEXT_CONTENT",$cell_data);
				}

				$this->tpl->parseCurrentBlock();
			}

			$this->tpl->setCurrentBlock("table_row");
			$this->tpl->setVariable("CSS_ROW",ilUtil::switchColor(++$counter,"tblrow1","tblrow2"));
			$this->tpl->parseCurrentBlock();
			// END TABLE CELL
		}
		// END TABLE DATA

		// BEGIN OPERATION_BTN
		foreach ($this->data["buttons"] as $name => $value)
		{
			$this->tpl->setCurrentBlock("operation_btn");
			$this->tpl->setVariable("BTN_NAME",$name);
			$this->tpl->setVariable("BTN_VALUE",$value);
			$this->tpl->parseCurrentBlock();
		}
	}

	/**
	* adds a local role
	* This method is only called when choose the option 'you may add local roles'. This option
	* is displayed in the permission settings dialogue for an object and ONLY if no local role folder exists
	* TODO: this will be changed
	* @access	public
	*/
	function addRoleObject()
	{
		global $rbacadmin, $rbacreview, $rbacsystem;

		// first check if role title is unique
		if ($rbacreview->roleExists($_POST["Flocal_role"]))
		{
			$this->ilias->raiseError($this->lng->txt("msg_role_exists1")." '".$_POST["Flocal_role"]."' ".
									 $this->lng->txt("msg_role_exists2"),$this->ilias->error_obj->MESSAGE);
		}

		// if the current object is no role folder, create one
		if ($this->object->getType() != "rolf")
		{
			$rolf_data = $rbacreview->getRoleFolderOfObject($_GET["ref_id"]);
	
			// is there already a rolefolder?
			if (!($rolf_id = $rolf_data["child"]))
			{
				// can the current object contain a rolefolder?
				$mods = $rbacreview->getModules($this->object->getType(),$_GET["ref_id"]);
	
				if (!isset($mods["rolf"]))
				{
					$this->ilias->raiseError($this->lng->txt("msg_no_rolf_allowed1")." '".$this->object->getTitle()."' ".
											$this->lng->txt("msg_no_rolf_allowed2"),$this->ilias->error_obj->WARNING);
				}
	
				// CHECK ACCESS 'create' rolefolder
				if (!$rbacsystem->checkAccess('create',$_GET["ref_id"],'rolf'))
				{
					$this->ilias->raiseError($this->lng->txt("msg_no_perm_create_rolf"),$this->ilias->error_obj->WARNING);
				}
				else
				{
					// create a rolefolder
					include_once ("./classes/class.ilObjRoleFolder.php");
					$rolfObj = new ilObjRoleFolder();
					$rolfObj->setTitle("Role Folder");
					$rolfObj->setDescription("Automatically generated Role Folder for ref no. ".$this->object->getRefId());
					$rolfObj->create();
					$rolfObj->createReference();
					$rolfObj->putInTree($this->object->getRefId());
					$rolfObj->setPermissions($_GET["ref_id"]);
	
					$rolf_id = $rolfObj->getRefId();
	
					// Suche aller Parent Rollen im Baum
					$parentRoles = $rbacreview->getParentRoleIds($this->object->getRefId());
	
					foreach ($parentRoles as $parRol)
					{
						// Es werden die im Baum am 'n�chsten liegenden' Templates ausgelesen
						$ops = $rbacreview->getOperationsOfRole($parRol["obj_id"],'rolf',$parRol["parent"]);
						// TODO: make this work:
						//$rbacadmin->grantPermission($parRol["obj_id"],$ops,$rolf_id);
					}
				}
			}
		}
		else
		{
			// Current object is already a rolefolder. To create the role we take its reference id
			$rolf_id = $this->object->getRefId();
		}

		// CHECK ACCESS 'write' of role folder
		if (!$rbacsystem->checkAccess('write',$rolf_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilias->error_obj->WARNING);
		}
		else
		{
			include_once ("./classes/class.ilObjRole.php");
			$roleObj = new ilObjRole();
			$roleObj->setTitle($_POST["Flocal_role"]);
			$roleObj->setDescription("No description");
			$roleObj->create();
			$new_obj_id = $roleObj->getId();
			$rbacadmin->assignRoleToFolder($new_obj_id,$rolf_id,$_GET["ref_id"],'y');
		}

		sendInfo($this->lng->txt("role_added"),true);
		
		header("Location: ".$this->getReturnLocation("addRole","adm_object.php?ref_id=".$_GET["ref_id"]."&cmd=perm"));
		exit();
	}

	/**
	* show possible action (form buttons)
	* 
	* @param	boolean
	* @access	public
 	*/
	function showActions($with_subobjects = false)
	{
		$notoperations = array();
		// NO PASTE AND CLEAR IF CLIPBOARD IS EMPTY
		if (empty($_SESSION["clipboard"]))
		{
			$notoperations[] = "paste";
			$notoperations[] = "clear";
		}
		// CUT COPY PASTE LINK DELETE IS NOT POSSIBLE IF CLIPBOARD IS FILLED
		if ($_SESSION["clipboard"])
		{
			$notoperations[] = "cut";
			$notoperations[] = "copy";
			$notoperations[] = "link";
		}

		$operations = array();

		$d = $this->objDefinition->getActions($_GET["type"]);

		foreach ($d as $row)
		{
			if (!in_array($row["name"], $notoperations))
			{
				$operations[] = $row;
			}
		}

		if (count($operations) > 0)
		{
			foreach ($operations as $val)
			{
				$this->tpl->setCurrentBlock("tbl_action_btn");
				$this->tpl->setVariable("BTN_NAME", $val["lng"]);
				$this->tpl->setVariable("BTN_VALUE", $this->lng->txt($val["lng"]));
				$this->tpl->parseCurrentBlock();
			}
		}

		if ($with_subobjects === true)
		{
			$this->showPossibleSubObjects();
		}

		$this->tpl->setCurrentBlock("tbl_action_row");
		$this->tpl->parseCurrentBlock();
	}

	/**
	* show possible subobjects (pulldown menu)
	*
	* @access	public
 	*/
	function showPossibleSubObjects()
	{
		$d = $this->objDefinition->getSubObjects($_GET["type"]);

		$import = false;

		if (count($d) > 0)
		{
			foreach ($d as $row)
			{
			    $count = 0;
				if ($row["max"] > 0)
				{
					//how many elements are present?
					for ($i=0; $i<count($this->data["ctrl"]); $i++)
					{
						if ($this->data["ctrl"][$i]["type"] == $row["name"])
						{
						    $count++;
						}
					}
				}
				if ($row["max"] == "" || $count < $row["max"])
				{
					$subobj[] = $row["name"];
					if($row["import"] == "1")	// import allowed?
					{
						$import = true;
					}
				}
			}
		}

		if (is_array($subobj))
		{
			// show import button if at least one
			// object type can be imported
			if ($import)
			{
				$this->tpl->setCurrentBlock("import_object");
				$this->tpl->setVariable("BTN_IMP", "import");
				$this->tpl->setVariable("TXT_IMP", $this->lng->txt("import"));
				$this->tpl->parseCurrentBlock();
			}

			//build form
			$opts = ilUtil::formSelect(12,"new_type",$subobj);
			$this->tpl->setCurrentBlock("add_object");
			$this->tpl->setVariable("SELECT_OBJTYPE", $opts);
			$this->tpl->setVariable("BTN_NAME", "create");
			$this->tpl->setVariable("TXT_ADD", $this->lng->txt("add"));
			$this->tpl->parseCurrentBlock();
		}
	}

	/**
	* get a template blockfile
	* format: tpl.<objtype>_<command>.html
	*
	* @param	string	command
	* @param	string	object type definition
	* @access	public
 	*/
	function getTemplateFile($a_cmd,$a_type = "")
	{
		if (!$a_type)
		{
			$a_type = $_GET["type"];
		}

		$template = "tpl.".$a_type."_".$a_cmd.".html";

		if (!$this->tpl->fileExists($template))
		{
			$template = "tpl.obj_".$a_cmd.".html";
		}

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", $template);
	}
	
	/**
	* get Titles of objects
	* this method is used for error messages in methods cut/copy/paste
	*
	* @param	array	Array of ref_ids (integer)
	* @return   array	Array of titles (string)
	* @access	private
 	*/
	function getTitlesByRefId($a_ref_ids)
	{
		foreach ($a_ref_ids as $id)
		{
			// GET OBJECT TITLE
			$tmp_obj =& $this->ilias->obj_factory->getInstanceByRefId($id);
			$title[] = $tmp_obj->getTitle();
			unset($tmp_obj);
		}

		return $title ? $title : array();
	}
} // END class.ilObjectGUI
?>
