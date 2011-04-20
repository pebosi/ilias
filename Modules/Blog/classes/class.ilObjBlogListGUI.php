<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once "Services/Object/classes/class.ilObjectListGUI.php";

/**
* Class ilObjBlogListGUI
*
* @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
* $Id: class.ilObjRootFolderListGUI.php 23764 2010-05-06 15:11:30Z smeyer $
*
* @extends ilObjectListGUI
*/
class ilObjBlogListGUI extends ilObjectListGUI
{
	/**
	* initialisation
	*/
	function init()
	{
		$this->copy_enabled = true;
		$this->delete_enabled = true;
		$this->cut_enabled = true;
		$this->subscribe_enabled = false;
		$this->link_enabled = false;
		$this->payment_enabled = false;
		$this->type = "blog";
		$this->gui_class_name = "ilobjbloggui";

		// general commands array
		include_once('./Modules/Blog/classes/class.ilObjBlogAccess.php');
		$this->commands = ilObjBlogAccess::_getCommands();
	}
}

?>