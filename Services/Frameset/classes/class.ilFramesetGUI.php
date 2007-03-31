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
* Class ilRepositoryGUI
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
* @package core
*/
class ilFramesetGUI
{

	/**
	* Constructor
	* @access	public
	*/
	function ilFramsetGUI()
	{
	}
	
	/**
	* set title for frameset (is normally shown by browser)
	*/
	function setFramesetTitle($a_fs_title)
	{
		$this->frameset_title = $a_fs_title;
	}
	
	/**
	* source url for main frame
	*/
	function setMainFrameSource($a_main_source)
	{
		$this->main_frame_source = $a_main_source;
	}

	/**
	* title for main frame
	*/
	function setMainFrameTitle($a_main_title)
	{
		$this->main_frame_title = $a_main_title;
	}

	/**
	* name for main frame
	*/
	function setMainFrameName($a_main_name)
	{
		$this->main_frame_name = $a_main_name;
	}

	/**
	* source url for side frame
	*/
	function setSideFrameSource($a_side_source)
	{
		$this->side_frame_source = $a_side_source;
	}

	/**
	* title for side frame
	*/
	function setSideFrameTitle($a_side_title)
	{
		$this->side_frame_title = $a_side_title;
	}
	
	/**
	* name for main frame
	*/
	function setSideFrameName($a_side_name)
	{
		$this->side_frame_name = $a_side_name;
	}

	/**
	* show frameset
	*/
	function show()
	{
		global $ilSetting;
		
		if ($ilSetting->get("tree_frame") == "right")
		{
			$main = "LEFT";
			$side = "RIGHT";
		}
		else
		{
			$main = "RIGHT";
			$side = "LEFT";
		}
		
		$main_width = "*";
		$side_width = "25%";

		$tpl = new ilTemplate("tpl.frameset.html", true, false);
		$tpl->setVariable("LOCATION_STYLESHEET", ilUtil::getStyleSheetLocation());
		$tpl->setVariable("PAGETITLE", "- ".$this->frameset_title);
		$tpl->setVariable("SRC_".$main, $this->main_frame_source);
		$tpl->setVariable("SRC_".$side, $this->side_frame_source);
		$tpl->setVariable("TITLE_".$main, $this->main_frame_title);
		$tpl->setVariable("TITLE_".$side, $this->side_frame_title);
		$tpl->setVariable("NAME_".$main, $this->main_frame_name);
		$tpl->setVariable("NAME_".$side, $this->side_frame_name);
		$tpl->setVariable("WIDTH_".$main, $main_width);
		$tpl->setVariable("WIDTH_".$side, $side_width);
		if ($ilSetting->get('short_inst_name') != "")
		{
			$tpl->setVariable("WINDOW_TITLE",
				$ilSetting->get('short_inst_name'));
		}
		else
		{
			$tpl->setVariable("WINDOW_TITLE",
				"ILIAS");
		}

		$tpl->show("DEFAULT", false);
	}

}

