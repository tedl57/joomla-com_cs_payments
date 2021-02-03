<?php
defined('_JEXEC') or die; // No direct access

// base class
require_once JPATH_COMPONENT.'/assets/payproc/base.php';

class Cs_paymentsPayprocActionEnter_Payment extends Cs_paymentsPayprocAction
{
	static public function getAuthLevel() { return 'core.edit.state'; }
	public function doConfirm() { return true; }
	public function isBuiltin() { return false; }
	public function getTitle()
	{
		$theModel = JModelLegacy::getInstance('payments', 'Cs_paymentsModel');
		if ( ( $data = $theModel->getData($this->id) ) === false )
			return false;
		
		// action only for join and renew payments
		if ($data->payment_type=="donate")
				return false;
		
		// memid should already be set by add_member action for joins
		
		if ($data->memid==0 && $data->payment_type=="join")
			return false;

		// make sure the memid is set prior to showing this action
		 
		if ($data->memid==0)
		{
			// 2021-02-02 - 1.0.5 fixes
			// 1. for renewals, ignore Duplicate to only pickup one email address
			// 2. for joins accidentally sent as renewals, make sure exactly 1 email address is found
			
			// find member by email address - todo: assumption: each member should have a unique email address
			$sql = "SELECT id,email,status FROM #__cs_members WHERE email='".$data->email."' AND status != 'Duplicate'";
			$db = JFactory::getDBO();
			$db->setQuery($sql);
			$rows = $db->loadAssocList();
			if ( $rows === null)
				return false;
			
			// 1.0.5 false renewal - will not find any existing member by email address
			
			if ( count( $rows ) == 0 )
			{
				JFactory::getApplication()->enqueueMessage("Processing payment #" . $this->id . ", no member found with email ".$data->email, 'warning');
			
				return false;
			}

			// must find exactly one member by email address

			if ( ($nmembers = count( $rows )) > 1 )
			{
				JFactory::getApplication()->enqueueMessage("Processing payment #" . $this->id . ", $nmembers members with email ".$data->email, 'warning');
				
				return false;
			}

			// add memid of member found to the cs_payments table before displaying this action
			$obj = new stdClass();
			$obj->id = $this->id;
			$obj->memid = $rows[0]["id"];
			//JFactory::getApplication()->enqueueMessage("adding memid while Processing payment #" . $this->id . ", " . json_encode( $rows ),'success');
			$res = JFactory::getDbo()->updateObject("#__cs_payments", $obj, 'id');
		}
	
		// show the "Enter Payment" action for this record

		return "Enter this payment now in the CRM app?";
	}
	public function executeAction()
	{
		$theModel = JModelLegacy::getInstance('payments', 'Cs_paymentsModel');
		if ( ( $data = $theModel->getData($this->id) ) === false )
			return false;

		$memid = $data->memid;
		$uri = "/index.php?option=com_cs_crm&action=showmemberenterpaymentform&actid=$memid";
		$app    = JFactory::getApplication();
		$app->redirect($uri);
		
		return true;
	}
}
?>
