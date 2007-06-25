<?php
class ilPaymentCouponGUI extends ilPaymentBaseGUI
{
	var $ctrl;

	var $lng;	
	
	var $user_obj = null;
	var $coupon_obj = null;
	var $pobject = null;	

	function ilPaymentCouponGUI(&$user_obj)
	{
		global $ilCtrl;

		$this->ctrl =& $ilCtrl;
		$this->ctrl->saveParameter($this, 'baseClass');

		$this->ilPaymentBaseGUI();

		$this->user_obj =& $user_obj;		
		
		$this->__initCouponObject();
	}
	
	/**
	* execute command
	*/
	function &executeCommand()
	{
		global $tree;

		$cmd = $this->ctrl->getCmd();
		switch ($this->ctrl->getNextClass($this))
		{
			default:
				if (!$cmd = $this->ctrl->getCmd())
				{
					$cmd = 'showCoupons';
				}
				
				$this->$cmd();
				break;
		}
	}
	
	function resetFilter()
	{
		unset($_POST['title_type']);
		unset($_POST['title_value']);
		unset($_POST['type']);
		unset($_POST['from']['day']);
		unset($_POST['from']['month']);
		unset($_POST['from']['year']);
		unset($_POST['till']['day']);
		unset($_POST['till']['month']);
		unset($_POST['till']['year']);

		$this->showCoupons();
	}
	
	function showCoupons()
	{
		$this->showButton('addCoupon', $this->lng->txt('paya_coupons_add'));
		
		if ($_POST['updateView'] == '1')
		{
			$_SESSION['pay_coupons']['title_type'] = $_POST['title_type'];
			$_SESSION['pay_coupons']['title_value'] = $_POST['title_value'];
			$_SESSION['pay_coupons']['type'] = $_POST['type'];			
			$_SESSION['pay_coupons']['from']['day'] = $_POST['from']['day'];
			$_SESSION['pay_coupons']['from']['month'] = $_POST['from']['month'];
			$_SESSION['pay_coupons']['from']['year'] = $_POST['from']['year'];
			$_SESSION['pay_coupons']['till']['day'] = $_POST['till']['day'];
			$_SESSION['pay_coupons']['till']['month'] = $_POST['till']['month'];
			$_SESSION['pay_coupons']['till']['year'] = $_POST['till']['year'];			
		}		
		
		$this->tpl->addBlockfile('ADM_CONTENT', 'adm_content', 'tpl.paya_coupons.html', 'payment');
		
		$this->tpl->setVariable("TXT_FILTER", $this->lng->txt('pay_filter'));
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
		
		$this->tpl->setVariable("TXT_COUPON",$this->lng->txt('paya_coupons_title'));
		$this->tpl->setVariable("TXT_STARTING",$this->lng->txt('pay_starting'));
		$this->tpl->setVariable("TXT_ENDING",$this->lng->txt('pay_ending'));		
		$this->tpl->setVariable("TXT_TYPE",$this->lng->txt('paya_coupons_type'));
		$this->tpl->setVariable("TXT_TYPE_FIX",$this->lng->txt('paya_coupons_fix'));
		$this->tpl->setVariable("TXT_TYPE_PERCENTAGED",$this->lng->txt('paya_coupons_percentaged'));		
		$this->tpl->setVariable("TXT_VALID_DATE_FROM",$this->lng->txt('paya_coupons_from'));
		$this->tpl->setVariable("TXT_VALID_DATE_TILL",$this->lng->txt('paya_coupons_till'));
		$this->tpl->setVariable("TXT_UPDATE_VIEW",$this->lng->txt('pay_update_view'));
		$this->tpl->setVariable("TXT_RESET_FILTER",$this->lng->txt('pay_reset_filter'));
		
		$this->tpl->setVariable("TITLE_TYPE_" . $_SESSION["pay_coupons"]["title_type"], " selected=\"selected\"");
		$this->tpl->setVariable("TITLE_VALUE", ilUtil::prepareFormOutput($_SESSION["pay_coupons"]["title_value"], true));
		$this->tpl->setVariable("TYPE_" . strtoupper($_SESSION["pay_coupons"]["type"]), " selected=\"selected\"");	
		for ($i = 1; $i <= 31; $i++)
		{
			$this->tpl->setCurrentBlock("loop_from_day");
			$this->tpl->setVariable("LOOP_FROM_DAY", $i < 10 ? "0" . $i : $i);
			if ($_SESSION["pay_coupons"]["from"]["day"] == $i)
			{
				$this->tpl->setVariable("LOOP_FROM_DAY_SELECTED", " selected=\"selected\"");
			}
			$this->tpl->parseCurrentBlock("loop_from_day");
			$this->tpl->setCurrentBlock("loop_till_day");
			$this->tpl->setVariable("LOOP_TILL_DAY", $i < 10 ? "0" . $i : $i);
			if ($_SESSION["pay_coupons"]["till"]["day"] == $i)
			{
				$this->tpl->setVariable("LOOP_TILL_DAY_SELECTED", " selected=\"selected\"");
			}
			$this->tpl->parseCurrentBlock("loop_till_day");
		}
		for ($i = 1; $i <= 12; $i++)
		{
			$this->tpl->setCurrentBlock("loop_from_month");
			$this->tpl->setVariable("LOOP_FROM_MONTH", $i < 10 ? "0" . $i : $i);
			if ($_SESSION['pay_coupons']['from']['month'] == $i)
			{
				$this->tpl->setVariable("LOOP_FROM_MONTH_SELECTED", " selected=\"selected\"");
			}
			$this->tpl->parseCurrentBlock("loop_from_month");
			$this->tpl->setCurrentBlock("loop_till_month");
			$this->tpl->setVariable("LOOP_TILL_MONTH", $i < 10 ? "0" . $i : $i);
			if ($_SESSION['pay_coupons']['till']['month'] == $i)
			{
				$this->tpl->setVariable("LOOP_TILL_MONTH_SELECTED", " selected=\"selected\"");
			}
			$this->tpl->parseCurrentBlock("loop_till_month");
		}
		for ($i = 2004; $i <= date("Y") + 2; $i++)
		{
			$this->tpl->setCurrentBlock("loop_from_year");
			$this->tpl->setVariable("LOOP_FROM_YEAR", $i);
			if ($_SESSION["pay_coupons"]['from']['year'] == $i)
			{
				$this->tpl->setVariable("LOOP_FROM_YEAR_SELECTED", " selected=\"selected\"");
			}
			$this->tpl->parseCurrentBlock("loop_from_year");
			$this->tpl->setCurrentBlock("loop_till_year");
			$this->tpl->setVariable("LOOP_TILL_YEAR", $i);
			if ($_SESSION['pay_coupons']['till']['year'] == $i)
			{
				$this->tpl->setVariable("LOOP_TILL_YEAR_SELECTED", " selected=\"selected\"");
			}
			$this->tpl->parseCurrentBlock("loop_till_year");
		}				
		
		if (!count($coupons = $this->coupon_obj->getCoupons(true)))
		{
			ilUtil::sendInfo($this->lng->txt('paya_coupons_not_found'));
			//if ($_POST['updateView'] == '1') ilUtil::sendInfo($this->lng->txt('paya_coupons_not_found'));
			//else ilUtil::sendInfo($this->lng->txt('paya_coupons_not_available'));		
			
			return true;
		}		
		
		$counter = 0;
		foreach ($coupons as $coupon)
		{
			$f_result[$counter][] = $coupon['pc_title'];
			$f_result[$counter][] = $coupon['number_of_codes'];
			$f_result[$counter][] = $coupon['usage_of_codes'];
			
			if (!empty($coupon['objects']))
			{
				$objects = "";
				for ($i = 0; $i < count($coupon['objects']); $i++)
				{
					$tmp_obj =& ilObjectFactory::getInstanceByRefId($coupon['objects'][$i]);
					$objects .= $tmp_obj->getTitle();
					
					if ($i < count($coupon['objects']) - 1) $objects .= "<br />";
					
					unset($tmp_obj);	
				}				
			}
			else
			{
				$objects = "";
			}
			
			$f_result[$counter][] = $objects;			
			$f_result[$counter][] = $coupon['pc_from'] != '0000-00-00' ? ilFormat::formatDate($coupon['pc_from'], 'date') : '';
			$f_result[$counter][] = $coupon['pc_till'] != '0000-00-00' ? ilFormat::formatDate($coupon['pc_till'], 'date') : '';
			$this->ctrl->setParameter($this, 'coupon_id', $coupon['pc_pk']);
			$f_result[$counter][] = "<div class=\"il_ContainerItemCommands\"><a class=\"il_ContainerItemCommand\" href=\"".$this->ctrl->getLinkTarget($this, "addCoupon")."\">".$this->lng->txt("edit")."</a></div>";
					
			++$counter;
		}
		
		return $this->__showCouponsTable($f_result);
	}
	
	function __showCouponsTable($f_result)
	{		
		$tbl =& $this->initTableGUI();
		$tpl =& $tbl->getTemplateObject();

		$tpl->setCurrentBlock("tbl_form_header");

		$tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));
		$tpl->parseCurrentBlock();	

		$tbl->setTitle($this->lng->txt("paya_coupons_coupons"), "icon_pays.gif", $this->lng->txt("paya_coupons_coupons"));
		$tbl->setHeaderNames(array($this->lng->txt("paya_coupons_title"),
								   $this->lng->txt("paya_coupons_number_of_codes"),
								   $this->lng->txt("paya_coupons_usage_of_codes"),
								   $this->lng->txt("paya_coupons_objects"),
								   $this->lng->txt("paya_coupons_from"),
								   $this->lng->txt("paya_coupons_till"), ''));
		$header_params = $this->ctrl->getParameterArray($this, '');
		$tbl->setHeaderVars(array('pc_title',
								  'number_of_codes',
								  'usage_of_codes',
								  'objects',
								  'pc_from',
								  'pc_till',
								  'options'), 
								  $header_params
							);
		$offset = $_GET['offset'];
		$order = $_GET['sort_by'];
		$direction = $_GET['sort_order'] ? $_GET['sort_order'] : 'desc';
		$tbl->setOrderColumn($order,'pc_title');
		$tbl->setOrderDirection($direction);
		$tbl->setOffset($offset);
		$tbl->setLimit($_GET['limit']);
		$tbl->setMaxCount(count($f_result));
		$tbl->setFooter('tblfooter', $this->lng->txt('previous'), $this->lng->txt('next'));
		$tbl->setData($f_result);
		$tbl->render();

		$this->tpl->setVariable('COUPONS_TABLE', $tbl->tpl->get());
		
		return true;	
	}

	function saveCouponForm()
	{
		$error = "";
		
		if ($_POST["title"] == "") $error .= "paya_coupons_title,";
		if ($_POST["type"] == "") $error .= "paya_coupons_type,";
		if ($_POST["value"] == "") $error .= "paya_coupons_value,";
		else $_POST["value"] = ilFormat::checkDecimal($_POST["value"]);				
		
		if ($error == "")
		{
			$from = "";
			$till = "";
			
			if (ereg("^[0-9]{4}\-[0-9]{1,2}\-[0-9]{1,2}$", $_POST['from']['year']."-".$_POST['from']['month']."-".$_POST['from']['day']))
			{
				$from = date("Y-m-d", mktime(0, 0 , 0, $_POST['from']['month'], $_POST['from']['day'], $_POST['from']['year']));
			}
			
			if (ereg("^[0-9]{4}\-[0-9]{1,2}\-[0-9]{1,2}$", $_POST['till']['year']."-".$_POST['till']['month']."-".$_POST['till']['day']))
			{
				$till = date("Y-m-d", mktime(0, 0 , 0, $_POST['till']['month'], $_POST['till']['day'], $_POST['till']['year']));
			}			
			
			$this->coupon_obj->setCouponUser($this->user_obj->getId());
			$this->coupon_obj->setTitle(ilUtil::stripSlashes($_POST["title"]));
			$this->coupon_obj->setDescription(ilUtil::stripSlashes($_POST["description"]));			
			$this->coupon_obj->setType(ilUtil::stripSlashes($_POST["type"]));
			$this->coupon_obj->setValue(ilUtil::stripSlashes($_POST["value"]));			
			$this->coupon_obj->setFromDate(ilUtil::stripSlashes($from));
			$this->coupon_obj->setTillDate(ilUtil::stripSlashes($till));
			$this->coupon_obj->setUses(ilUtil::stripSlashes($_POST["usage"]));
			
			if ($_GET['coupon_id'] != "")
			{
				$this->coupon_obj->setId($_GET['coupon_id']);
				
				$this->coupon_obj->update();
			}
			else
			{
				$_GET['coupon_id'] = $this->coupon_obj->add();				 
			}
			
			ilUtil::sendInfo($this->lng->txt("saved_successfully"));
		}
		else
		{			
			if (is_array($e = explode(",", $error)))
			{				
				$mandatory = "";
				for ($i = 0; $i < count($e); $i++)
				{
					$e[$i] = trim($e[$i]);
					if ($e[$i] != "")
					{
						$mandatory .= $this->lng->txt($e[$i]);
						if (array_key_exists($i + 1, $e) && $e[$i + 1] != "") $mandatory .= ", ";
					}
				}
				ilUtil::sendInfo($this->lng->txt("fill_out_all_required_fields") . ": " . $mandatory);
			}			
		}		
		
		$this->addCoupon();
	}
	
	function addCoupon()
	{
		$coupon = array();
		
		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.paya_coupons_add.html','payment');		
		
		if (isset($_GET['coupon_id']))
		{
			$this->ctrl->setParameter($this, "coupon_id", $_GET['coupon_id']);			
			
			$this->__showButtons();
			
			$coupon = $this->coupon_obj->getCoupon($_GET['coupon_id']);	
			
			$from = explode("-", $coupon['pc_from']);
			$_POST['from']['day'] = isset($_POST['from']['day']) ? $_POST['from']['day'] : $from[2];
			$_POST['from']['month'] = isset($_POST['from']['month']) ? $_POST['from']['month'] : $from[1];
			$_POST['from']['year'] = isset($_POST['from']['year']) ? $_POST['from']['year'] : $from[0];
			
			$till = explode("-", $coupon['pc_till']);
			$_POST['till']['day'] = isset($_POST['till']['day']) ? $_POST['till']['day'] : $till[2];
			$_POST['till']['month'] = isset($_POST['till']['month']) ? $_POST['till']['month'] : $till[1];
			$_POST['till']['year'] = isset($_POST['till']['year']) ? $_POST['till']['year'] : $till[0];	
		}		
		
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
		
		$this->tpl->setVariable("TXT_HEADLINE", isset($_GET['coupon_id']) ? $this->lng->txt('paya_coupons_edit') : $this->lng->txt('paya_coupons_add'));
		$this->tpl->setVariable("TXT_TITLE", $this->lng->txt('paya_coupons_title'));
		$this->tpl->setVariable("TXT_DESCRIPTION", $this->lng->txt('paya_coupons_description'));
		$this->tpl->setVariable("TXT_TYPE", $this->lng->txt('paya_coupons_type'));
		$this->tpl->setVariable("TXT_FIX", $this->lng->txt('paya_coupons_fix'));
		$this->tpl->setVariable("TXT_PERCENTAGED", $this->lng->txt('paya_coupons_percentaged'));
		$this->tpl->setVariable("TXT_VALUE", $this->lng->txt('paya_coupons_value'));
		$this->tpl->setVariable("TXT_VALID_FROM", $this->lng->txt('paya_coupons_from'));
		$this->tpl->setVariable("TXT_VADID_TILL", $this->lng->txt('paya_coupons_till'));
		$this->tpl->setVariable("TXT_USAGE", $this->lng->txt('paya_coupons_availability'));
		
		$this->tpl->setVariable("TITLE", ilUtil::prepareFormOutput(isset($_POST["title"]) ? $_POST["title"] : $coupon["pc_title"] , true));
		$this->tpl->setVariable("DESCRIPTION", ilUtil::prepareFormOutput(isset($_POST["description"]) ? $_POST["description"] : $coupon["pc_description"], true));
		$this->tpl->setVariable("TYPE_" . strtoupper(isset($_POST["type"]) ? $_POST["type"] : $coupon["pc_type"]), " selected=\"selected\"");
		$this->tpl->setVariable("VALUE", ilUtil::prepareFormOutput(isset($_POST["value"]) ? $_POST["value"] : $coupon["pc_value"], true));
		$this->tpl->setVariable("USAGE", ilUtil::prepareFormOutput(isset($_POST["usage"]) ? $_POST["usage"] : $coupon["pc_uses"], true));
		
		for ($i = 1; $i <= 31; $i++)
		{
			$this->tpl->setCurrentBlock("loop_from_day");
			$this->tpl->setVariable("LOOP_FROM_DAY", $i < 10 ? "0" . $i : $i);
			if ($_POST["from"]["day"] == $i)
			{
				$this->tpl->setVariable("LOOP_FROM_DAY_SELECTED", " selected=\"selected\"");
			}
			$this->tpl->parseCurrentBlock("loop_from_day");
			$this->tpl->setCurrentBlock("loop_till_day");
			$this->tpl->setVariable("LOOP_TILL_DAY", $i < 10 ? "0" . $i : $i);
			if ($_POST["till"]["day"] == $i)
			{
				$this->tpl->setVariable("LOOP_TILL_DAY_SELECTED", " selected=\"selected\"");
			}
			$this->tpl->parseCurrentBlock("loop_till_day");
		}
		for ($i = 1; $i <= 12; $i++)
		{
			$this->tpl->setCurrentBlock("loop_from_month");
			$this->tpl->setVariable("LOOP_FROM_MONTH", $i < 10 ? "0" . $i : $i);
			if ($_POST["from"]["month"] == $i)
			{
				$this->tpl->setVariable("LOOP_FROM_MONTH_SELECTED", " selected=\"selected\"");
			}
			$this->tpl->parseCurrentBlock("loop_from_month");
			$this->tpl->setCurrentBlock("loop_till_month");
			$this->tpl->setVariable("LOOP_TILL_MONTH", $i < 10 ? "0" . $i : $i);
			if ($_POST["till"]["month"] == $i)
			{
				$this->tpl->setVariable("LOOP_TILL_MONTH_SELECTED", " selected=\"selected\"");
			}
			$this->tpl->parseCurrentBlock("loop_till_month");
		}
		for ($i = 2004; $i <= date("Y") + 2; $i++)
		{
			$this->tpl->setCurrentBlock("loop_from_year");
			$this->tpl->setVariable("LOOP_FROM_YEAR", $i);
			if ($_POST["from"]["year"] == $i)
			{
				$this->tpl->setVariable("LOOP_FROM_YEAR_SELECTED", " selected=\"selected\"");
			}
			$this->tpl->parseCurrentBlock("loop_from_year");
			$this->tpl->setCurrentBlock("loop_till_year");
			$this->tpl->setVariable("LOOP_TILL_YEAR", $i);
			if ($_POST["till"]["year"] == $i)
			{
				$this->tpl->setVariable("LOOP_TILL_YEAR_SELECTED", " selected=\"selected\"");
			}
			$this->tpl->parseCurrentBlock("loop_till_year");
		}
		
		$this->tpl->setVariable("TXT_REQUIRED_FIELDS", $this->lng->txt("required_field"));
		$this->tpl->setVariable("TXT_SAVE",$this->lng->txt('save'));
		$this->tpl->setVariable("TXT_CANCEL",$this->lng->txt('cancel'));
		$this->tpl->setVariable("COUPONS", "showCoupons");
	}
	
	function deleteAllCodes()
	{		
		$this->showCodes("all");
		
		return true;
	}
	
	function performDeleteAllCodes()
	{
		$this->coupon_obj->deleteAllCodesByCouponId($_GET['coupon_id']);	
		
		$this->showCodes();

		return true;
	}
	
	function deleteCodes()
	{
		$_SESSION['paya_delete_codes'] = $_POST['codes'];
		
		if (!is_array($_POST['codes']))
		{
			ilUtil::sendInfo($this->lng->txt('paya_coupons_no_codes_selected'));
			
			$this->showCodes();

			return true;
		}
		
		$this->showCodes("selected");
		
		return true;
	}

	function performDeleteCodes()
	{
		if (is_array($_SESSION['paya_delete_codes']))
		{			
			foreach($_SESSION['paya_delete_codes'] as $id)
			{
				$this->coupon_obj->deleteCode($id);
			}
		}
		unset($_SESSION['paya_delete_codes']);
		ilUtil::sendInfo($this->lng->txt('paya_coupons_code_deleted_successfully'));
		
		$this->showCodes();

		return true;
	}
	
	function cancelDelete()
	{
		unset($_SESSION['paya_delete_codes']);
		
		$this->showCodes();

		return true;
	}
	
	function showCodes($a_show_delete = "")
	{		
		$this->coupon_obj->setId($_GET['coupon_id']);
		
		if (!count($codes = $this->coupon_obj->getCodesByCouponId($_GET['coupon_id'])))
		{
			ilUtil::sendInfo($this->lng->txt('paya_coupons_codes_not_available'));			
			
			$this->generateCodes();			
			
			return true;
		}		
		
		$this->ctrl->setParameter($this, 'coupon_id', $_GET['coupon_id']);		
		$this->__showButtons();	
		
		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.paya_coupons_codes.html','payment');	
				
		if ($a_show_delete == "all")
		{
			ilUtil::sendInfo($this->lng->txt('paya_coupons_sure_delete_all_codes'));
			$this->tpl->setCurrentBlock("confirm_delete_all");
			$this->tpl->setVariable("CONFIRM_ALL_FORMACTION",$this->ctrl->getFormAction($this));
			$this->tpl->setVariable("TXT_CANCEL_ALL",$this->lng->txt('cancel'));
			$this->tpl->setVariable("TXT_CONFIRM_ALL",$this->lng->txt('delete_all'));
			$this->tpl->parseCurrentBlock();
		}
		if ($a_show_delete == "selected")
		{
			ilUtil::sendInfo($this->lng->txt('paya_coupons_sure_delete_selected_codes'));
			$this->tpl->setCurrentBlock("confirm_delete");
			$this->tpl->setVariable("CONFIRM_FORMACTION",$this->ctrl->getFormAction($this));
			$this->tpl->setVariable("TXT_CANCEL",$this->lng->txt('cancel'));
			$this->tpl->setVariable("TXT_CONFIRM",$this->lng->txt('delete'));
			$this->tpl->parseCurrentBlock();
		}
		
		
		
		$_SESSION['paya_delete_codes'] = $_SESSION['paya_delete_codes'] ? $_SESSION['paya_delete_codes'] : array();
		
		$counter = 0;
		foreach ($codes as $code)
		{
			$f_result[$counter][]	= ilUtil::formCheckbox(in_array($code['pcc_pk'], $_SESSION['paya_delete_codes']) ? 1 : 0,
															   "codes[]",
															   $code['pcc_pk']);
			$f_result[$counter][] = $code['pcc_code'];			
			++$counter;
		}
						
		$tbl =& $this->initTableGUI();
		$tpl =& $tbl->getTemplateObject();
		
		$tpl->setCurrentBlock("tbl_form_header");		
		$tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
		$tpl->parseCurrentBlock();
		$tbl->setTitle($this->lng->txt("paya_coupons_codes"), "icon_pays.gif", $this->lng->txt("paya_coupons_codes"));
		$tbl->setHeaderNames(array('', $this->lng->txt("paya_coupons_code")));		
		$this->ctrl->setParameter($this, "cmd", "showCodes");
		$header_params = $this->ctrl->getParameterArray($this, '');
		$tbl->setHeaderVars(array('', 'pcc_code'), $header_params);
		$offset = $_GET['offset'];
		$order = $_GET['sort_by'];
		$direction = $_GET['sort_order'] ? $_GET['sort_order'] : 'desc';
		$tbl->setOrderColumn($order,'pcc_code');
		$tbl->setOrderDirection($direction);
		$tbl->setOffset($offset);
		$tbl->setLimit($_GET['limit']);
		$tbl->setMaxCount(count($f_result));
		$tbl->setFooter('tblfooter', $this->lng->txt('previous'), $this->lng->txt('next'));
		$tbl->setData($f_result);
		$tpl->setVariable('COLUMN_COUNTS', 2);
		
		$tbl->enable('select_all');
		$tbl->setFormName('cmd');
		$tbl->setSelectAllCheckbox('codes');
		
		$tpl->setVariable("IMG_ARROW", ilUtil::getImagePath("arrow_downright.gif"));
		
		$tpl->setCurrentBlock("tbl_action_button");
		$tpl->setVariable("BTN_NAME","deleteCodes");
		$tpl->setVariable("BTN_VALUE",$this->lng->txt("delete"));
		$tpl->parseCurrentBlock();		
				
		$tpl->setCurrentBlock('plain_button');
		$tpl->setVariable('PBTN_NAME', 'generateCodes');
		$tpl->setVariable('PBTN_VALUE', $this->lng->txt('paya_coupons_generate_codes'));
		$tpl->parseCurrentBlock();
		
		$tpl->setCurrentBlock('plain_button');
		$tpl->setVariable('PBTN_NAME', 'deleteAllCodes');
		$tpl->setVariable('PBTN_VALUE', $this->lng->txt('delete_all'));
		$tpl->parseCurrentBlock();		
		
		$tpl->setCurrentBlock("plain_button");
		$tpl->setVariable("PBTN_NAME","exportCodes");
		$tpl->setVariable("PBTN_VALUE",$this->lng->txt("export"));
		$tpl->parseCurrentBlock();
				
		$tbl->setColumnWidth(array("10%","90%"));		
		$tbl->render();		

		$this->tpl->setVariable('CODES_TABLE', $tbl->tpl->get());		
		
		return true;
	}
	
	function exportCodes()
	{
		$codes = $this->coupon_obj->getCodesByCouponId($_GET["coupon_id"]);
		
		if (is_array($codes))
		{
			include_once('./Services/Utilities/classes/class.ilCSVWriter.php');			
			
			$csv = new ilCSVWriter();
			$csv->setDelimiter("");			
			
			foreach($codes as $data)
			{							
				if ($data["pcc_code"])
				{					
					$csv->addColumn($data["pcc_code"]);
					$csv->addRow();					
				}
			}
			
			ilUtil::deliverData($csv->getCSVString(), "code_export_".date("Ymdhis").".csv");
		}
		
		$this->showCodes();
		
		return true;
	}
	
	function saveCodeForm()
	{
		if (isset($_POST["generate"]["length"])) $_SESSION["pay_coupons"]["code_length"] = $_POST["generate"]["length"];
		else $_POST["generate"]["length"] = $_SESSION["pay_coupons"]["code_length"];
		
		if (isset($_POST["generate"]["type"])) $_SESSION["pay_coupons"]["code_type"] = $_POST["generate"]["type"];
		else $_POST["generate"]["type"] = $_SESSION["pay_coupons"]["code_type"];
		
		if ($_POST["generate"]["type"] == "self")
		{
			if ($_GET["coupon_id"] && is_array($_POST["codes"]))
			{				
				$count_inserts = 0;
				
				for ($i = 0; $i < count($_POST["codes"]); $i++)
				{
					$_POST["codes"][$i] = trim($_POST["codes"][$i]);
					
					if ($_POST["codes"][$i] != "")
					{					
						$code = $this->__makeCode($_POST["codes"][$i], $_SESSION["pay_coupons"]["code_length"]);
						
						if ($code != "")
						{
							if ($this->coupon_obj->addCode(ilUtil::stripSlashes($code), $_GET["coupon_id"]))
							{
								++$count_inserts;
							}
						}
					}
				}
				
				if ($count_inserts) 
				{
					ilUtil::sendInfo($this->lng->txt("saved_successfully"));
					$this->showCodes();
				}
				else
				{
					ilUtil::sendInfo($this->lng->txt("paya_coupons_no_codes_generated"));
					$this->generateCodes();
				}				
			}
			else if (!is_numeric($_POST["generate"]["number"]) ||  $_POST["generate"]["number"] <= 0)
			{
				ilUtil::sendInfo($this->lng->txt("fill_out_all_required_fields"));					
				
				$this->generateCodes();	
			}
			else
			{
				$this->generateCodes("input");
			}
		}
		else if ($_POST["generate"]["type"] == "auto")
		{
			if ($_GET["coupon_id"] && is_numeric($_POST["generate"]["number"]) && $_POST["generate"]["number"] > 0)
			{				
				for ($i = 0; $i < $_POST["generate"]["number"]; $i++)
				{
					$code = $this->__makeCode("", $_SESSION["pay_coupons"]["code_length"]);
					
					if ($code != "")
					{
						$this->coupon_obj->addCode($code, $_GET["coupon_id"]);
					}
				}
				
				ilUtil::sendInfo($this->lng->txt("saved_successfully"));
				
				$this->showCodes();					
			}
			else
			{	
				ilUtil::sendInfo($this->lng->txt("fill_out_all_required_fields"));					
				
				$this->generateCodes();
			}
		}
	}
	
	function __makeCode($a_code = "", $a_length = 32)
	{
		if ($a_code == "") $a_code = md5(uniqid(rand()));
	
		if (strlen($a_code) > $a_length)
		{
			$a_code = substr($a_code, 0, $a_length);
		}		
				
		return $a_code;
	}
	
	function generateCodes($view = "choice")
	{		
		$this->coupon_obj->setId($_GET['coupon_id']);
		
		$this->ctrl->setParameter($this, 'coupon_id', $_GET['coupon_id']);
		$this->__showButtons();
		
		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.paya_coupons_codes_generate.html','payment');
				
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TXT_HEADLINE", $this->lng->txt('paya_coupons_code_generation'));				
		
		if ($view == "choice")
		{	
			$this->tpl->touchBlock("code_choice");			
			
			$this->tpl->setVariable("TXT_TYPE", $this->lng->txt('paya_coupons_generate_codes'));
			$this->tpl->setVariable("TXT_AUTO", $this->lng->txt('paya_coupons_type_auto'));
			$this->tpl->setVariable("TXT_SELF", $this->lng->txt('paya_coupons_type_self'));
			$this->tpl->setVariable("TXT_NUMBER_OF_CODES", $this->lng->txt('paya_coupons_number_of_codes'));
			$this->tpl->setVariable("TXT_LENGTH", $this->lng->txt('paya_coupons_code_length'));
			$this->tpl->setVariable("TXT_CHARS", $this->lng->txt('paya_coupons_code_chars'));			
			
			$this->tpl->setVariable("TYPE_".strtoupper(isset($_POST["generate"]["type"]) ? $_POST["generate"]["type"] : "auto"), " checked=\"checked\"");
			$this->tpl->setVariable("LENGTH", ilUtil::prepareFormOutput($_POST["generate"]["length"] ? $_POST["generate"]["length"] : 12, true));
			
			$this->tpl->setVariable("TXT_REQUIRED_FIELDS", $this->lng->txt("required_field"));	
		}
		else if ($view == "input")
		{
			$this->tpl->touchBlock("code_input");
			
			if (is_numeric($_POST["generate"]["number"]) && $_POST["generate"]["number"] > 0)
			{
				for ($i = 0; $i < $_POST["generate"]["number"]; $i++)
				{
					$this->tpl->setCurrentBlock("loop");
					$this->tpl->setVariable("LOOP_CODE_INDEX", $i + 1);
					$this->tpl->parseCurrentBlock();
				}
			}
		}		
		
		$this->tpl->setVariable("TXT_SEND",$this->lng->txt('send'));
		
		$this->tpl->setVariable("IMPORT_ACTION", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TXT_IMPORT",$this->lng->txt('import'));
					
		return true;
	}
	
	function assignObjects()
	{
		if (is_array($_POST['objects']))
		{
			$this->coupon_obj->setId($_GET["coupon_id"]);		
			foreach($_POST['objects'] as $id)
			{							
				if ($id)
				{					
					$this->coupon_obj->assignObjectToCoupon($id);
				}
			}			
		}
		
		$this->showObjects();
		
		return true;
	}
	
	function unassignObjects()
	{
		if (is_array($_POST['objects']))
		{			
			$this->coupon_obj->setId($_GET["coupon_id"]);				
			foreach($_POST['objects'] as $id)
			{							
				if ($id)
				{
					$this->coupon_obj->unassignObjectFromCoupon($id);
				}
			}			
		}
		
		$this->showObjects();
		
		return true;
	}
	
	function showObjects()
	{
		$this->coupon_obj->setId($_GET['coupon_id']);
		
		$this->__initPaymentObject();		
		
		$this->ctrl->setParameter($this, 'coupon_id', $_GET['coupon_id']);
		$this->__showButtons();
		
		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.paya_coupons_objects.html','payment');		
						
		$objects = $this->pobject->_getObjectsData($this->user_obj->getId());
				
		$counter_assigned = 0;
		$counter_unassigned = 0;
		$f_result_assigned = array();
		$f_result_unassigned = array();
		foreach($objects as $data)
		{					
			if ($this->coupon_obj->isObjectAssignedToCoupon($data['ref_id']))
			{
				$p_counter =& $counter_assigned;
				$p_result =& $f_result_assigned;
			}
			else
			{
				$p_counter =& $counter_unassigned;
				$p_result =& $f_result_unassigned;
			}
			
			$tmp_obj =& ilObjectFactory::getInstanceByRefId($data['ref_id']);
			
			$p_result[$p_counter][]	= ilUtil::formCheckbox(0, 'objects[]', $data['ref_id']);			
			$p_result[$p_counter][] = $tmp_obj->getTitle();
			switch($data['status'])
			{
				case $this->pobject->STATUS_BUYABLE:
					$p_result[$p_counter][] = $this->lng->txt('paya_buyable');
					break;

				case $this->pobject->STATUS_NOT_BUYABLE:
					$p_result[$p_counter][] = $this->lng->txt('paya_not_buyable');
					break;
					
				case $this->pobject->STATUS_EXPIRES:
					$p_result[$p_counter][] = $this->lng->txt('paya_expires');
					break;
			}
			switch($data['pay_method'])
			{
				case $this->pobject->PAY_METHOD_NOT_SPECIFIED:
					$p_result[$p_counter][] = $this->lng->txt('paya_pay_method_not_specified');
					break;

				case $this->pobject->PAY_METHOD_BILL:
					$p_result[$p_counter][] = $this->lng->txt('pays_bill');
					break;

				case $this->pobject->PAY_METHOD_BMF:
					$p_result[$p_counter][] = $this->lng->txt('pays_bmf');
					break;

				case $this->pobject->PAY_METHOD_PAYPAL:
					$p_result[$p_counter][] = $this->lng->txt('pays_paypal');
					break;
			}
			
			++$p_counter;				
							
			unset($tmp_obj);			
		}
		
		$this->ctrl->setParameter($this, "cmd", "showObjects");
		
		if (count($f_result_assigned) > 0)
		{		
			$tbl =& $this->initTableGUI();
			$tpl =& $tbl->getTemplateObject();
			$tbl->setPrefix('assigned');
			
			$tpl->setCurrentBlock("tbl_form_header");		
			$tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
			$tpl->parseCurrentBlock();	
			$tbl->setTitle($this->lng->txt("paya_coupons_assigned_objects"),"icon_pays.gif",$this->lng->txt("paya_coupons_assigned_objects"));
			$tbl->setHeaderNames(array("", 
									   $this->lng->txt("title"),
								   	   $this->lng->txt("status"),
								   	   $this->lng->txt("paya_pay_method")));
			$header_params = $this->ctrl->getParameterArray($this,'');
			$tbl->setHeaderVars(array("", 
									  "title",
								  	  "status",
								  	  "pay_method"),$header_params);		
			$offset = $_GET['assignedoffset'];
			$order = $_GET['assignedsort_by'];
			$direction = $_GET['assignedsort_order'] ? $_GET['assignedsort_order'] : 'desc';		
			$tbl->setOrderColumn($order,'title');
			$tbl->setOrderDirection($direction);
			$tbl->setOffset($offset);
			$tbl->setLimit($_GET['limit']);
			$tbl->setMaxCount(count($f_result_assigned));
			$tbl->setFooter('tblfooter', $this->lng->txt('previous'), $this->lng->txt('next'));
			$tbl->setData($f_result_assigned);								  
			$tpl->setVariable('COLUMN_COUNTS', 4);		
			$tpl->setVariable("IMG_ARROW", ilUtil::getImagePath("arrow_downright.gif"));		
			$tpl->setCurrentBlock("tbl_action_button");
			$tpl->setVariable("BTN_NAME","unassignObjects");
			$tpl->setVariable("BTN_VALUE",$this->lng->txt("remove"));
			$tpl->parseCurrentBlock();						
			$tbl->setColumnWidth(array("10%","20%","20%","20%"));			
			$tbl->render();
			
			$this->tpl->setVariable('LINKED_OBJECTS_TABLE', $tbl->tpl->get());
		}
		
		if (count($f_result_unassigned) > 0)
		{		
			$tbl =& $this->initTableGUI();
			$tpl =& $tbl->getTemplateObject();
			$tpl->setCurrentBlock("tbl_form_header");		
			$tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
			$tpl->parseCurrentBlock();	
			$tbl->setTitle($this->lng->txt("paya_coupons_unassigned_objects"),"icon_pays.gif",$this->lng->txt("paya_coupons_unassigned_objects"));
			$tbl->setHeaderNames(array("", 
									   $this->lng->txt("title"),
								   	   $this->lng->txt("status"),
								   	   $this->lng->txt("paya_pay_method")));
			$header_params = $this->ctrl->getParameterArray($this,'');
			$tbl->setHeaderVars(array("", 
									  "title_ua",
								  	  "status_ua",
								  	  "pay_method_ua"),$header_params);		
			$offset = $_GET['offset'];
			$order = $_GET['sort_by'];
			$direction = $_GET['sort_order'] ? $_GET['sort_order'] : 'desc';		
			$tbl->setOrderColumn($order,'title_ua');
			$tbl->setOrderDirection($direction);
			$tbl->setOffset($offset);
			$tbl->setLimit($_GET['limit']);
			$tbl->setMaxCount(count($f_result_unassigned));
			$tbl->setFooter('tblfooter', $this->lng->txt('previous'), $this->lng->txt('next'));
			$tbl->setData($f_result_unassigned);								  
			$tpl->setVariable('COLUMN_COUNTS', 4);		
			$tpl->setVariable("IMG_ARROW", ilUtil::getImagePath("arrow_downright.gif"));		
			$tpl->setCurrentBlock("tbl_action_button");
			$tpl->setVariable("BTN_NAME","assignObjects");
			$tpl->setVariable("BTN_VALUE",$this->lng->txt("add"));
			$tpl->parseCurrentBlock();						
			$tbl->setColumnWidth(array("10%","20%","20%","20%"));			
			$tbl->render();		
	
			$this->tpl->setVariable('UNLINKED_OBJECTS_TABLE', $tbl->tpl->get());
		}
		
		return true;
	}
	
	function importCodes()
	{	
		include_once('./Services/Utilities/classes/class.ilCSVReader.php');
		
		if ($_FILES["codesfile"]["tmp_name"] != "")
		{
			$csv = new ilCSVReader();
			$csv->setDelimiter("");
			
			if ($csv->open($_FILES["codesfile"]["tmp_name"]))
			{		
				$data = $csv->getDataArrayFromCSVFile();
				
				for ($i = 0; $i < count($data); $i++)
				{
					for ($j = 0; $j < count($data[$i]); $j++)
					{
						$this->coupon_obj->addCode(ilUtil::stripSlashes($data[$i][$j]), $_GET["coupon_id"]);	
					}
				}				
				
				ilUtil::sendInfo($this->lng->txt("paya_coupon_codes_import_successful"));
				
				$this->showCodes();
			}
			else
			{
				ilUtil::sendInfo($this->lng->txt("paya_coupons_import_error_opening_file"));
				
				$this->showCodeImport();
			}
		}
		else
		{
			ilUtil::sendInfo($this->lng->txt("fill_out_all_required_fields"));
			
			$this->showCodeImport();					
		}
		
		return true;
	}
	
	function showCodeImport()
	{
		$this->ctrl->setParameter($this, 'coupon_id', $_GET['coupon_id']);
		$this->__showButtons();
		
		$this->tpl->addBlockfile('ADM_CONTENT','adm_content','tpl.paya_coupons_codes_import.html','payment');
		
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));		
		$this->tpl->setVariable("TXT_HEADLINE", $this->lng->txt('paya_coupons_codes_import'));
		
		$this->tpl->setVariable("TXT_FILE",$this->lng->txt('file'));
		
		$this->tpl->setVariable("TXT_UPLOAD",$this->lng->txt('upload'));
		$this->tpl->setVariable("TXT_REQUIRED_FIELDS", $this->lng->txt("required_field"));
		
		return true;
	}
	
	function __showButtons()
	{
		$this->showButton('addCoupon', $this->lng->txt('paya_coupons_edit'));
		$this->showButton('showCodes', $this->lng->txt('paya_coupons_edit_codes'));
		$this->showButton('showObjects', $this->lng->txt('paya_coupons_edit_objects'));
		
		return true;		
	}
	
	function __initPaymentObject($a_pobject_id = 0)
	{
		include_once './payment/classes/class.ilPaymentObject.php';

		$this->pobject =& new ilPaymentObject($this->user_obj, $a_pobject_id);

		return true;
	}
	
	function __initCouponObject()
	{
		include_once './payment/classes/class.ilPaymentCoupons.php';	

		$this->coupon_obj =& new ilPaymentCoupons($this->user_obj, true);

		return true;
	}
}
?>
