<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("class.ilCloudPluginListGUI.php");

/**
 * Class ilCloudPluginActionListGUI
 *
 * Basic functionality of the action list. Can be extended to define addidtional actions by the plugin. Note that
 * the list is loaded asyncronically by default. Disable if not wanted.
 *
 * @author Timon Amstutz <timon.amstutz@ilub.unibe.ch>
 * @version $Id:
 * @extends ilCloudPluginListGUI
 * @ingroup ModulesCloud
 */
class ilCloudPluginActionListGUI extends ilCloudPluginListGUI
{
    /**
     * @var ilcloudFileNode
     */
    protected $node = null;

    /**
     * @param bool $delete
     * @param ilCloudFileNode $node
     * @return string $html
     */
    public function getSelectionListItemsHTML($delete_item = false, $delete_folder = false, ilCloudFileNode $node)
    {

        global $lng, $ilCtrl;

        /**
         * @var ilCtrl $ilCtrl
         */

        $this->node = $node;

        if(($delete_item && !$node->getIsDir())|| ($delete_folder && $node->getIsDir()) ||$this->checkHasAction())
        {
            include_once("./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php");
            $this->selection_list = new ilAdvancedSelectionListGUI();
            $this->selection_list->setId("id_action_list_".$this->node->getId());
            $this->selection_list->setListTitle($lng->txt("actions"));
            $this->selection_list->setItemLinkClass("xsmall");

            if($this->getAsyncMode())
            {
                $this->selection_list->setAsynch(true);
                $this->selection_list->setAsynchUrl(html_entity_decode($ilCtrl->getLinkTargetByClass("ilobjcloudgui", "asyncGetActionListContent", false) . "&node_id=" . $node->getId()));
            }
            else
            {
                $this->addSelectionListItems($delete_item, $delete_folder);
            }

            return $this->selection_list->getHtml();
        }
        else
        {
            return "";
        }

    }

    protected function addSelectionListItems($delete_item, $delete_folder)
    {
        global $lng;
        $this->addSelectionListItemsBefore();
        if (($delete_item && !$this->node->getIsDir()) || ($delete_folder && $this->node->getIsDir()))
        {
            $this->selection_list->addItem($lng->txt("delete"), "delete_item", "javascript:il.CloudFileList.deleteItem(\'" . $this->node->getId() . "\');");
        }

        $this->addSelectionListItemsAfter();
    }

    /**
     * @param bool $delete_item
     * @param bool $delete_folder
     * @param ilCloudFileNode $node
     */
    public function asyncGetContent($delete_item = false, $delete_folder = false, ilCloudFileNode $node)
    {
        global $lng;
        $this->node = $node;
        include_once("./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php");
        $this->selection_list = new ilAdvancedSelectionListGUI();
        $this->selection_list->setId($this->node->getId());
        $this->selection_list->setListTitle($lng->txt("actions"));
        $this->selection_list->setItemLinkClass("xsmall");

        $this->addSelectionListItems($delete_item , $delete_folder);
        if ($this->selection_list->getItems() != null)
        {
            echo $this->selection_list->getHTML(true);
            exit;
        } else
        {
            echo $lng->txt("empty");
            exit;
        }
    }

    protected function addSelectionListItemsBefore(){}
    protected function addSelectionListItemsAfter(){}
    protected function checkHasAction(){}
    protected function getAsyncMode()
    {
        return true;
    }
}
?>