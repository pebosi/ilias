<?php
/**
* Class RoleObjectOut
*
* @author Stefan Meyer <smeyer@databay.de> 
* $Id$Id$
* 
* @extends Object
* @package ilias-core
*/

class RoleObjectOut extends ObjectOut
{
	/**
	* Constructor
	* @access public
	*/
	function RoleObjectOut($a_data)
	{
		$this->ObjectOut($a_data);
	}

	function updateObject()
	{
		header("Location: adm_object.php?obj_id=".$_GET["parent"]."&parent=".
			   $_GET["parent_parent"]."&cmd=view");
		exit();
	}
	
	function permObject()
	{
		$this->tpl->addBlockFile("CONTENT", "content", "tpl.adm_content.html");
		$this->tpl->addBlockFile("LOCATOR", "locator", "tpl.locator.html");
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.adm_perm_role.html");


		// BEGIN BLOCK OBJECT TYPES
		$this->tpl->setCurrentBlock("OBJECT_TYPES");

		foreach ($this->data["obj_types"] as $type)
		{
			$this->tpl->setVariable("OBJ_TYPES",$type);
			$this->tpl->parseCurrentBlock();
		}
		// END BLOCK OBJECT TYPES

		// BEGIN TABLE DATA OUTER
		foreach($this->data["perm"] as $name => $operations)
		{
			// BEGIN CHECK PERMISSION
			$this->tpl->setCurrentBlock("CHECK_PERM");
			for($i = 0;$i < count($operations)-1;++$i)
			{
				$this->tpl->setVariable("CHECK_PERMISSION",$operations[$i]);
				$this->tpl->parseCurrentBlock();
			}
			// END CHECK PERMISSION
			$this->tpl->setCurrentBlock("TABLE_DATA_OUTER");
			$this->tpl->setVariable("CSS_ROW",$operations["color"]);
			$this->tpl->setVariable("PERMISSION",$name);
			$this->tpl->parseCurrentBlock();
		}
		// END TABLE DATA OUTER

		// BEGIN ADOPT PERMISSIONS

		foreach($this->data["adopt"] as $key => $value)
		{
			$this->tpl->setCurrentBlock("ADOPT_PERMISSIONS");			
			$this->tpl->setVariable("CSS_ROW_ADOPT",$value["css_row_adopt"]);
			$this->tpl->setVariable("CHECK_ADOPT",$value["check_adopt"]);
			$this->tpl->setVariable("TYPE",$value["type"]);
			$this->tpl->setVariable("ROLE_NAME",$value["role_name"]);
			$this->tpl->parseCurrentBlock();
		}
		// END ADOPT PERMISSIONS


		// BEGIN USER_ASSIGNMENT
		if(count($this->data["users"]))
		{
			foreach($this->data["users"] as $key => $value)
			{
				$this->tpl->setCurrentBLock("TABLE_USER");
				$this->tpl->setVariable("CSS_ROW_USER",$value["css_row_user"]);
				$this->tpl->setVariable("CHECK_USER",$value["check_user"]);
				$this->tpl->setVariable("USERNAME",$value["username"]);
				$this->tpl->parseCurrentBlock();
			}
			$this->tpl->setCurrentBlock("ASSIGN");
			$this->tpl->setVariable("MESSAGE_BOTTOM",$this->data["message_bottom"]);
			$this->tpl->setVariable("FORMACTION_ASSIGN",$this->data["formaction_assign"]);
			$this->tpl->parseCurrentBlock();
		}

		// END USER_ASSIGNMENT
		$this->tpl->setCurrentBlock("adm_content");

		$this->tpl->setVariable("COL_ANZ",$this->data["col_anz"]);
		$this->tpl->setVariable("CHECK_BOTTOM",$this->data["check_bottom"]);
		$this->tpl->setVariable("MESSAGE_TABLE",$this->data["message_table"]);		
		$this->tpl->setVariable("FORMACTION",$this->data["formaction"]);
		$this->tpl->setVariable("MESSAGE_MIDDLE",$this->data["message_middle"]);
		$this->tpl->setVariable("FORMACTION_ADOPT",$this->data["formaction_adopt"]);


		$this->tpl->parseCurrentBlock("adm_content");
	}
	function adoptPermSaveObject()
	{
		header("Location: adm_object.php?obj_id=".$_GET["obj_id"]."&parent=".
			   $_GET["parent"]."&parent_parent=".$_GET["parent_parent"]."&cmd=perm");
		exit();
	}

	function assignSaveObject()
	{
		header("Location: adm_object.php?obj_id=".$_GET["obj_id"]."&parent=".
			   $_GET["parent"]."&parent_parent=".$_GET["parent_parent"]."&cmd=perm");
		exit();
	}
} // END class.RoleObjectOut
?>