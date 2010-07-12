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

include_once('./Services/Table/classes/class.ilTable2GUI.php');

/**
*
* @author Helmut Schottmüller <ilias@aurealis.de>
* @version $Id$
*
* @ingroup ModulesTestQuestionPool
*/

class ilQuestionBrowserTableGUI extends ilTable2GUI
{
	protected $editable = true;
	protected $writeAccess = false;
	protected $totalPoints = 0;
	
	/**
	 * Constructor
	 *
	 * @access public
	 * @param
	 * @return
	 */
	public function __construct($a_parent_obj, $a_parent_cmd, $a_write_access = false)
	{
		$this->setId("qpl");
		parent::__construct($a_parent_obj, $a_parent_cmd);

		global $lng, $ilCtrl;

		$this->lng = $lng;
		$this->ctrl = $ilCtrl;
	
		$this->setWriteAccess($a_write_access);

		$qplSetting = new ilSetting("qpl");
			
		$this->setFormName('questionbrowser');
		$this->setStyle('table', 'fullwidth');
		$this->addColumn('','f','1%');
		$this->addColumn($this->lng->txt("title"),'title', '');
		$this->addColumn('','edit', '');
		$this->addColumn('','preview', '');
		foreach ($this->getSelectedColumns() as $c)
		{
			if (strcmp($c, 'description') == 0) $this->addColumn($this->lng->txt("description"),'description', '');
			if (strcmp($c, 'type') == 0) $this->addColumn($this->lng->txt("question_type"),'ttype', '');
			if (strcmp($c, 'points') == 0) $this->addColumn($this->lng->txt("points"),'', '', false, 'right');
			if (strcmp($c, 'statistics') == 0) $this->addColumn('','statistics', '');
			if (strcmp($c, 'author') == 0) $this->addColumn($this->lng->txt("author"),'author', '');
			if (strcmp($c, 'created') == 0) $this->addColumn($this->lng->txt("create_date"),'created', '');
			if (strcmp($c, 'updated') == 0) $this->addColumn($this->lng->txt("last_update"),'updated', '');
		}

		$this->setPrefix('q_id');
		$this->setSelectAllCheckbox('q_id');
		
		if ($this->getWriteAccess())
		{
			$this->addMultiCommand('copy', $this->lng->txt('copy'));
			$this->addMultiCommand('move', $this->lng->txt('move'));
			$this->addMultiCommand('exportQuestion', $this->lng->txt('export'));
			$this->addMultiCommand('deleteQuestions', $this->lng->txt('delete'));

			$this->addCommandButton('importQuestions', $this->lng->txt('import'));
			if (array_key_exists("qpl_clipboard", $_SESSION))
			{
				$this->addCommandButton('paste', $this->lng->txt('paste'));
			}
		}


		$this->setRowTemplate("tpl.il_as_qpl_questionbrowser_row.html", "Modules/TestQuestionPool");

		$this->setFormAction($this->ctrl->getFormAction($a_parent_obj, $a_parent_cmd));
		$this->setDefaultOrderField("title");
		$this->setDefaultOrderDirection("asc");
		
		$this->enable('sort');
		$this->enable('header');
		$this->enable('select_all');
		$this->setFilterCommand('filterQuestionBrowser');
		$this->setResetCommand('resetQuestionBrowser');
		
		$this->initFilter();
	}

	function getSelectableColumns()
	{
		global $lng;
		$cols["description"] = array(
			"txt" => $lng->txt("description"),
			"default" => true
		);
		$cols["type"] = array(
			"txt" => $lng->txt("question_type"),
			"default" => true
		);
		$cols["points"] = array(
			"txt" => $lng->txt("points"),
			"default" => true
		);
		$cols["statistics"] = array(
			"txt" => $lng->txt("statistics"),
			"default" => true
		);
		$cols["author"] = array(
			"txt" => $lng->txt("author"),
			"default" => true
		);
		$cols["created"] = array(
			"txt" => $lng->txt("create_date"),
			"default" => true
		);
		$cols["updated"] = array(
			"txt" => $lng->txt("last_update"),
			"default" => true
		);
		return $cols;
	}

	/**
	* Init filter
	*/
	function initFilter()
	{
		global $lng, $rbacreview, $ilUser;
		
		// title
		include_once("./Services/Form/classes/class.ilTextInputGUI.php");
		$ti = new ilTextInputGUI($lng->txt("title"), "title");
		$ti->setMaxLength(64);
		$ti->setSize(20);
		$this->addFilterItem($ti);
		$ti->readFromSession();
		$this->filter["title"] = $ti->getValue();
		
		// description
		$ti = new ilTextInputGUI($lng->txt("description"), "description");
		$ti->setMaxLength(64);
		$ti->setSize(20);
		$this->addFilterItem($ti);
		$ti->readFromSession();
		$this->filter["description"] = $ti->getValue();
		
		// author
		$ti = new ilTextInputGUI($lng->txt("author"), "author");
		$ti->setMaxLength(64);
		$ti->setSize(20);
		$this->addFilterItem($ti);
		$ti->readFromSession();
		$this->filter["author"] = $ti->getValue();
		
		// questiontype
		include_once("./Services/Form/classes/class.ilSelectInputGUI.php");
		include_once("./Modules/TestQuestionPool/classes/class.ilObjQuestionPool.php");
		$types = ilObjQuestionPool::_getQuestionTypes();
		$options = array();
		$options[""] = $lng->txt('filter_all_question_types');
		foreach ($types as $translation => $row)
		{
			$options[$row['type_tag']] = $translation;
		}

		$si = new ilSelectInputGUI($this->lng->txt("question_type"), "type");
		$si->setOptions($options);
		$this->addFilterItem($si);
		$si->readFromSession();
		$this->filter["type"] = $si->getValue();
		
	}
	
	function fillHeader()
	{
		foreach ($this->column as $key => $column)
		{
			if (strcmp($column['text'], $this->lng->txt("points")) == 0)
			{
				$this->column[$key]['text'] = $this->lng->txt("points") . "&nbsp;(" . $this->totalPoints . ")";
			}
		}
		parent::fillHeader();
	}
	
	/**
	 * fill row 
	 *
	 * @access public
	 * @param
	 * @return
	 */
	public function fillRow($data)
	{
		global $ilUser,$ilAccess;
		include_once "./Modules/TestQuestionPool/classes/class.assQuestion.php";
		include_once "./Modules/TestQuestionPool/classes/class.assQuestionGUI.php";
		$class = strtolower(assQuestionGUI::_getGUIClassNameForId($data["question_id"]));
		$this->ctrl->setParameterByClass("ilpageobjectgui", "q_id", $data["question_id"]);
		$this->ctrl->setParameterByClass($class, "q_id", $data["question_id"]);
		$points = 0;
		if ($this->getEditable())
		{
			$this->tpl->setCurrentBlock("edit_link");
			$this->tpl->setVariable("TXT_EDIT", $this->lng->txt("edit"));
			$this->tpl->setVariable("LINK_EDIT", $this->ctrl->getLinkTargetByClass("ilpageobjectgui", "edit"));
			$this->tpl->parseCurrentBlock();
		}
		if ($data["complete"] == 0)
		{
			$this->tpl->setCurrentBlock("qpl_warning");
			$this->tpl->setVariable("IMAGE_WARNING", ilUtil::getImagePath("warning.gif"));
			$this->tpl->setVariable("ALT_WARNING", $this->lng->txt("warning_question_not_complete"));
			$this->tpl->setVariable("TITLE_WARNING", $this->lng->txt("warning_question_not_complete"));
			$this->tpl->parseCurrentBlock();
		}
		else
		{
			$points = $data["points"];
		}
		$this->totalPoints += $points;

		foreach ($this->getSelectedColumns() as $c)
		{
			if (strcmp($c, 'description') == 0)
			{
				$this->tpl->setCurrentBlock('description');
				$this->tpl->setVariable("QUESTION_COMMENT", (strlen($data["description"])) ? $data["description"] : "&nbsp;");
				$this->tpl->parseCurrentBlock();
			}
			if (strcmp($c, 'type') == 0)
			{
				$this->tpl->setCurrentBlock('type');
				$this->tpl->setVariable("QUESTION_TYPE", assQuestion::_getQuestionTypeName($data["type_tag"]));
				$this->tpl->parseCurrentBlock();
			}
			if (strcmp($c, 'points') == 0)
			{
				$this->tpl->setCurrentBlock('points');
				$this->tpl->setVariable("QUESTION_POINTS", $points);
				$this->tpl->parseCurrentBlock();
			}
			if (strcmp($c, 'statistics') == 0)
			{
				$this->tpl->setCurrentBlock('statistics');
				$this->tpl->setVariable("LINK_ASSESSMENT", $this->ctrl->getLinkTargetByClass($class, "assessment"));
				$this->tpl->setVariable("TXT_ASSESSMENT", $this->lng->txt("statistics"));
				include_once "./Services/Utilities/classes/class.ilUtil.php";
				$this->tpl->setVariable("IMG_ASSESSMENT", ilUtil::getImagePath("assessment.gif", "Modules/TestQuestionPool"));
				$this->tpl->parseCurrentBlock();
			}
			if (strcmp($c, 'author') == 0)
			{
				$this->tpl->setCurrentBlock('author');
				$this->tpl->setVariable("QUESTION_AUTHOR", $data["author"]);
				$this->tpl->parseCurrentBlock();
			}
			include_once "./classes/class.ilFormat.php";
			if (strcmp($c, 'created') == 0)
			{
				$this->tpl->setCurrentBlock('created');
				$this->tpl->setVariable("QUESTION_CREATED", ilDatePresentation::formatDate(new ilDate($data['created'],IL_CAL_UNIX)));
				$this->tpl->parseCurrentBlock();
			}
			if (strcmp($c, 'updated') == 0)
			{
				$this->tpl->setCurrentBlock('updated');
				$this->tpl->setVariable("QUESTION_UPDATED", ilDatePresentation::formatDate(new ilDate($data["tstamp"],IL_CAL_UNIX)));
				$this->tpl->parseCurrentBlock();
			}
		}
		$this->tpl->setVariable('QUESTION_ID', $data["question_id"]);
		$this->tpl->setVariable("QUESTION_TITLE", $data["title"]);

		$this->tpl->setVariable("TXT_PREVIEW", $this->lng->txt("preview"));
		$this->tpl->setVariable("LINK_PREVIEW", $this->ctrl->getLinkTargetByClass("ilpageobjectgui", "preview"));
	}
	
	public function setEditable($value)
	{
		$this->editable = $value;
	}
	
	public function getEditable()
	{
		return $this->editable;
	}

	public function setWriteAccess($value)
	{
		$this->writeAccess = $value;
	}
	
	public function getWriteAccess()
	{
		return $this->writeAccess;
	}
}
?>