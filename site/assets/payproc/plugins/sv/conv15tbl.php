<?php

/* read 1.5 version of gen_payments, convert data and write version 3 cs_payments

to use:
	- use phpmyadmin to export gen_payments from 1.5 site
	- import it in 3.x db, changing the prefix and name as necessary (see the expected tbl_in name below)
	- 
 rename _conv version to go live and have all previous history
 make sure the autoinc values have their own number-spaces
*/

defined('_JEXEC') or die;	// No direct access

// base class
require_once JPATH_COMPONENT.'/assets/payproc/base.php';
require_once JPATH_COMPONENT.'/helpers/cs_payments.php';

class Cs_paymentsPayprocActionConv15tbl extends Cs_paymentsPayprocAction
{
	static public function getAuthLevel() { return 'core.edit.state'; }
	public function getTitle() { return "Convert j1.5 tblfinaces to j3 cs_payments"; }
	public function executeAction()
	{
		$tbl_in = "#__cs_paymentsj15";
		$tbl_out = "#__cs_paymentsj3";
		$sql = "SELECT * FROM $tbl_in";
		$db = & JFactory::getDBO();
		$db->setQuery($sql);
		$items = $db->loadObjectList('id') ;

		foreach($items as $id => $obj )
		{
			$id=$obj->id;
			$amount=$obj->amount;
			$payment_type = $obj->payment_reason == "r" ? "renew " : $obj->payment_reason == "j" ? "join" : "donate";
			$payment_reason = $obj->what;
			$processed_by = $obj->processed_by;
			$response= $obj->response;
			$person = unserialize($obj->person);

			$datetimestamp= $obj->datetimestamp;
$processed_date= $obj->processed_date == "0000-00-00 00:00:00" ? "NULL" : "'$obj->processed_date'";
$date_paid= $obj->date_paid == NULL ? "NULL" : "'$obj->date_paid'";

			$first_name= $db->escape($person["fname"]);
			$last_name= $db->escape($person["lname"]);
			$address= $db->escape($person["address"]);
			$city= $db->escape($person["city"]);
			$usastate= $person["state"];
			$zipcode= $person["zip"];
			$email= $person["email"];
			$phone_type= $person["phone_type"];
			$phone= $person["phone"];
			$source= $db->escape($obj->source);
			$gender= $obj->gender;
			$lang_pref= $obj->language;
			$birthdate= $obj->bday;

			$sql = "INSERT INTO `joomla3`.`$tbl_out` (`id`, `amount`, `payment_type`, `payment_reason`, `datetimestamp`, `date_paid`, `processed_by`, `processed_date`, `response`, `created_by`, `first_name`, `last_name`, `address`, `city`, `usastate`, `zipcode`, `phone`, `phone_type`, `email`, `source`, `gender`, `lang_pref`, `birthdate`) VALUES ($id, '$amount', '$payment_type', '$payment_reason', '$datetimestamp', $date_paid, '$processed_by', $processed_date, '$response', 'created_by', '$first_name', '$last_name', '$address', '$city', '$usastate', '$zipcode', '$phone', '$phone_type', '$email', '$source', '$gender', '$lang_pref', '$birthdate');";
//echo "$sql<br />";
			$db->setQuery($sql);
			$db->execute();
		}

		return true;
	}
}
?>
