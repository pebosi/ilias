<?php


require_once("classes/class.ilExplorer.php");
require_once("content/classes/AICC/class.ilAICCTree.php");
require_once("content/classes/AICC/class.ilAICCExplorer.php");

class ilHACPExplorer extends ilAICCExplorer
{

	/**
	* Constructor
	* @access	public
	* @param	string	scriptname
	* @param    int user_id
	*/
	function ilHACPExplorer($a_target, &$a_slm_obj)
	{
		parent::ilExplorer($a_target);
		$this->slm_obj =& $a_slm_obj;
		$this->tree = new ilAICCTree($a_slm_obj->getId());
		$this->root_id = $this->tree->readRootId();
		$this->checkPermissions(false);
		$this->outputIcons(true);
		$this->setOrderColumn("");
	}
	
/**
	* Creates output
	* recursive method
	* @access	private
	* @param	integer
	* @param	array
	* @return	string
	*/
	function formatObject($a_node_id,$a_option)
	{
		global $lng;
		//echo "hacp: ".$a_option["title"]." >> ".implode(", ",$a_option["tab"])."<br>";

		if (!isset($a_node_id) or !is_array($a_option))
		{
			$this->ilias->raiseError(get_class($this)."::formatObject(): Missing parameter or wrong datatype! ".
									"node_id: ".$a_node_id." options:".var_dump($a_option),$this->ilias->error_obj->WARNING);
		}

		$tpl = new ilTemplate("tpl.sahs_tree.html", true, true, true);

	 	if ($a_option["type"]=="sos")
			return;

		if ($a_option["type"]=="srs")
			return;

		if (is_array($a_option["tab"])) { //test if there are any tabs
			foreach ($a_option["tab"] as $picture)
			{
				if ($picture == 'plus')
				{
					$target = $this->createTarget('+',$a_node_id);
					$tpl->setCurrentBlock("expander");
					$tpl->setVariable("LINK_TARGET_EXPANDER", $target);
					$tpl->setVariable("IMGPATH", ilUtil::getImagePath("browser/plus.gif"));
					$tpl->parseCurrentBlock();
				}
	
				if ($picture == 'minus')
				{
					$target = $this->createTarget('-',$a_node_id);
					$tpl->setCurrentBlock("expander");
					$tpl->setVariable("LINK_TARGET_EXPANDER", $target);
					$tpl->setVariable("IMGPATH", ilUtil::getImagePath("browser/minus.gif"));
					$tpl->parseCurrentBlock();
				}
	
				if ($picture == 'blank' or $picture == 'winkel'
				   or $picture == 'hoch' or $picture == 'quer' or $picture == 'ecke')
				{
					$picture = 'blank';
					$tpl->setCurrentBlock("lines");
					$tpl->setVariable("IMGPATH_LINES", ilUtil::getImagePath("browser/".$picture.".gif"));
					$tpl->parseCurrentBlock();
				}
			}
		}
		
		if ($this->output_icons)	{
			if ($this->isClickable($a_option["type"], $a_node_id) && $a_option["type"]!="sbl")
				$this->getOutputIcons(&$tpl, $a_option, $a_node_id);
		}


		if ($this->isClickable($a_option["type"], $a_node_id))	// output link
		{
			$tpl->setCurrentBlock("link");
			//$target = (strpos($this->target, "?") === false) ?
			//	$this->target."?" : $this->target."&";
			//$tpl->setVariable("LINK_TARGET", $target.$this->target_get."=".$a_node_id.$this->params_get);
			//$tpl->setVariable("TITLE", ilUtil::shortenText($a_option["title"], $this->textwidth, true));
			$frame_target = $this->buildFrameTarget($a_option["type"], $a_node_id, $a_option["obj_id"]);
			if ($frame_target != "")
			{
//				if ($this->api == 1)
//				{
//					$tpl->setVariable("TITLE", ilUtil::shortenText($a_option["title"], $this->textwidth, true));
//					$tpl->setVariable("TARGET", " target=\"".$frame_target."\"");
//					//$tpl->setVariable("LINK_TARGET", $this->buildLinkTarget($a_node_id, $a_option["type"]));
//					$tpl->setVariable("LINK_TARGET", $this->buildLinkTarget($a_node_id, $a_option["type"]));
//				}
//				else
//				{
					if ($a_option["type"]=="sbl") {
						$tpl->setVariable("TITLE", ilUtil::shortenText($a_option["title"]." ($a_node_id)", $this->textwidth, true));
						$tpl->setVariable("TARGET", " target=\"".$frame_target."\"");
						$tpl->setVariable("LINK_TARGET", $this->buildLinkTarget($a_node_id, $a_option["type"]));
						
					} else {
						include_once("content/classes/AICC/class.ilAICCUnit.php");
						$unit =& new ilAICCUnit($a_node_id);
						
						//guess the url to be able to launch most contents
						$url=$unit->getCommand_line();
						if (strlen($url)==0)
							$url=$unit->getFilename();

						
						//relative path?	
						if (substr($url,0,7)!="http://")
							$url=$this->slm_obj->getDataDirectory("output")."/".$url;
							
						if (strlen($unit->getWebLaunch())>0)
							$url.="?".$unit->getWebLaunch();
						
						$hacpURL=ILIAS_HTTP_PATH."/content/sahs_server.php";
						
						//$url.="?aicc_url=$hacpURL&aicc_sid=".$this->slm_obj->ref_id;
						//$aicc_sid=$this->slm_obj->ref_id."%20".session_id();
						$aicc_sid=implode("_", array(session_id(), $this->slm_obj->ref_id, $a_node_id));
						if (strlen($unit->getWebLaunch())>0)
							$url.="&";
						else
							$url.="?";
						$url.="aicc_url=$hacpURL&aicc_sid=$aicc_sid";

/*					
						foreach ($this->slm_obj as $key=>$value)
							$output.="key=$key value=$value<br>";
						$tpl->setVariable("TITLE", $output);
*/	
						$tpl->setVariable("TITLE", ilUtil::shortenText($a_option["title"]." ($a_node_id)", $this->textwidth, true));
						$tpl->setVariable("LINK_TARGET", "javascript:void(0);");
						$tpl->setVariable("ONCLICK", " onclick=\"parent.$frame_target.location.href='$url'\"");

						
//					}
				}

			}
			$tpl->parseCurrentBlock();
		}
		else			// output text only
		{
			$tpl->setCurrentBlock("text");
			$tpl->setVariable("OBJ_TITLE", ilUtil::shortenText($a_option["title"], $this->textwidth, true));
			$tpl->parseCurrentBlock();
		}
		$this->formatItemTable($tpl, $a_node_id, $a_option["type"]);

		$tpl->setCurrentBlock("row");
		$tpl->parseCurrentBlock();

		$this->output[] = $tpl->get();
	}
}
?>
