<?php
defined('_JEXEC') or die; // No direct access

// base class
require_once JPATH_COMPONENT.'/assets/payproc/base.php';

class Cs_paymentsPayprocActionadd_member extends Cs_paymentsPayprocAction
{
	static public function getAuthLevel() { return 'core.edit.state'; }
	public function doConfirm() { return true; }
	public function isBuiltin() { return false; }
	public function getTitle()
	{
		$theModel = JModelLegacy::getInstance('payments', 'Cs_paymentsModel');
		if ( ( $data = $theModel->getData($this->id) ) === false )
			return false;
		
		if ($data->payment_type!="join")
				return false;

		if ($data->memid!=0)
			return false;

		// join try to match an existing user before adding a new one with the "same" data to avoid duplicates
		// find member by email address - todo: assumption: each member should have a unique email address
		$sql = "SELECT email FROM #__cs_members WHERE email='".$data->email."'";
		$db = JFactory::getDBO();
		$db->setQuery($sql);
		$rows = $db->loadAssocList();
			
		if ( ($nmembers = count( $rows )) >= 1 )
		{
			JFactory::getApplication()->enqueueMessage("Processing join payment #" . $this->id . ", $nmembers existing member(s) with email ".$data->email, 'warning');
		
			return false;
		}
		return "Create new member from this payment?";
	}
	public function executeAction()
	{
		// map the fields in the cs_payments table to cs_members
		
		$map = array(
				"last_name" => "lname",
				"first_name" => "fname",
				"address" => "address",
				"city" => "city",
				"usastate" => "state",
				"zipcode" => "zip",
				"email" => "email",
				"source" => "source",
				"source_more" => "appl_source",
				"newsletter_distribution" => "newsletter_dist" );


		// get the cs_payments record to populate the new member's record

		$theModel = JModelLegacy::getInstance('payments', 'Cs_paymentsModel');
		if ( ( $data = $theModel->getData($this->id) ) === false )
			return false;

		// save the appropriate phone number

		if ( $data->phone_type == "Cell" )
			$map["phone"] = "cphone";
		else if ( $data->phone_type == "Work" )
			$map["phone"] = "wphone";
		else
			$map["phone"] = "hphone";	// Home or Other

		// create the cs_members record from the cs_payments data

		$obj = new stdClass();
		$obj->id = 0;			// will be replaced with new auto_increment id after insert
		
		foreach ( $data as $k => $v )
		{	
			// only save the data that is mapped from cs_payments to cs_members

			if ( !isset($map[$k]))
				continue;
			
			$k = $map[$k];
			$obj->$k = $v;
		}

		$obj->date_entered = date('Y-m-d H:i:s');
		$obj->status = "Applied";
		$obj->appl_paymethod = "online";
		$obj->by_username = JFactory::getUser()->get('username');
		$arr = explode('|',$data->payment_reason);	// Individual|20|1|0|0
		$obj->memtype = $arr[0];
		$obj->appl_duesdue = $arr[1];

		// save the new member's cs_members record
		
		$result = JFactory::getDbo()->insertObject( "#__cs_members", $obj, 'id' );
		$memid = $obj->id;
	
		// todo: check result
				
		// add memid of new member to the cs_payments table
		$obj = new stdClass();
		$obj->id = $this->id;
		$obj->memid = $memid;
		$res = JFactory::getDbo()->updateObject("#__cs_payments", $obj, 'id');

		// todo: check result

		JFactory::getApplication()->enqueueMessage("Added Member # $memid",'success');
		
		return true;
	}
}
?>
