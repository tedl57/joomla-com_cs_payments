<?php
/**
 * @version     1.0.0
 * @package     com_cs_payments
 * @copyright   Copyright (C) Creative Spirits 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ted Lowe <lists@creativespirits.org> - http://www.creativespirits.org
 */

// No direct access
defined('_JEXEC') or die;

require_once JPATH_COMPONENT.'/controller.php';

// todos: separate file or in static helper
class ofPaypalCollector	// {{{1
{
	protected $_item_type;
	protected $_item_name;
	protected $_amount;
	protected $_item_id;
	protected $_custom;

	protected $_tid;
	protected $_host;
	protected $_component_name = "ppcol";
	protected $_debug = false;
	protected $_ema;
	protected $_image_url = "";

	//////////////////////////////////////////////////////////
	public function __construct($item_type,$item_name,$amount,$ema,$item_id=0)
	{
		$this->_item_type = $item_type;
		$this->_item_name = $item_name;
		$this->_amount = $amount;
		$this->_item_id = $item_id;
		$this->_host = $_SERVER[HTTP_HOST];
		$this->_ema = $ema;
	}

	public function setDebug()
	{
		$this->_debug = true;
	}

	public function setImageURL( $image_url )
	{
		$this->_image_url = $image_url;
	}

	public function setCustom( $custom )
	{
		$this->_custom = $custom;
	}


	public function gotoPayPal($pdata = NULL)
	{
		$url = $this->getURL($pdata);
		mosRedirect( $url );
		exit();
	}

	//////////////////////////////////////////////////////////
	public function getURL($pdata = NULL)
	{
$this->setDebug(); // todos:
	//	$gConfigHomeDir = configGetParm("HomeDir","HomeDir_NOT_SET");
		// get unique transacton id to track thru paypal
		$data["item_type"] = $this->_item_type;
		$data["item_name"] = $this->_item_name;
		if ( ! empty( $this->_custom ) )
			$data["custom"] = $this->_custom;
		if ( $this->_item_id )
			$data["item_id"] = $this->_item_id;
		$data["amount"] = $this->_amount;
/*		$data[date_entered] = getTimeStampNow();
		$transid = $this->_tid = mdb2PutTableRow( TBLNAME, $data );
*/
		$host = $this->_host;
		$comp_name = $this->_component_name;
		$amount = $this->_amount;
		$cancel_return = urlencode( "http://$host/components/com_$comp_name/${comp_name}.php?action=cancelled&actid=$transid" );
		$completed_return = urlencode( "http://$host/components/com_$comp_name/${comp_name}.php?action=completed&actid=$transid");
		$notify_url = urlencode( "http://$host/components/com_$comp_name/${comp_name}.php?action=notify&actid=$transid");

		$image_option = "";

		if ( ! empty( $this->_image_url ) )
			$image_option = "&image_url=" . urlencode( $this->_image_url );

		if ( ! empty( $this->_custom ) )
			$custom_option = "&custom=" . urlencode( $this->_custom );

		$debug = "";
		$ppema = $this->_ema;
		if ( $this->_debug )
		{
			// use sandbox email to be able to enter non-paypal user credit card payments
			$ppema = "tedl57_1189482713_biz@gmail.com";
			$debug = ".sandbox";
		}
		$uitem = urlencode( $this->_item_name );


		// transid is passed through on a completed transaction
		// rm=2 means paypal will post return data back us

		$url = "https://www$debug.paypal.com/us/cgi-bin/webscr?cmd=_xclick$custom_option$image_option&amount=$amount&item_name=$uitem&no_shipping=1&no_note=1&invoice=$transid&business=$ppema&cancel_return=$cancel_return&rm=2&return=$completed_return&notify_url=$notify_url&first_name=Ted&last_name=Lowe";
/*
		require_once 'Zend/Log.php';
		require_once 'Zend/Log/Writer/Stream.php';
		$writer = new Zend_Log_Writer_Stream("$gConfigHomeDir/data/logs/www/ppcol.log");
		$logger = new Zend_Log($writer);
		log_obj($logger,"_url", $url);
*/
		return $url;
	}
}

/**
 * Payment controller class.
 */
class Cs_paymentsControllerPayments extends Cs_paymentsController
{
	public function payproc()
	{
		require_once JPATH_COMPONENT.'/assets/payproc/base.php';

		$app = JFactory::getApplication();
		$loggeduser = JFactory::getUser();
		$id = $app->input->get('id');
		$action = $app->input->get('ppaction', "");

		if ( empty( $action ) )
			return;	// todos: better error leg?
		
		if ( Cs_paymentsPayprocAction::loadActionClass(null,$action) === null )
			jexit("payproc: can't load class for action $action");
		
		$classname = Cs_paymentsPayprocAction::getChildClassName($action);
		if ( ! class_exists( $classname ))
			jexit("payproc: no class $classname");
		$obj = new $classname($id,$loggeduser->username,$action);
/*		
		if ( ! method_exists($actionObj, 'executeAction'))
			jexit("no way to execute in $classname");
*/
		// authorize user before processing to protect against possible URL spoofing 
/*		if ( ! $loggeduser->authorise($obj->getAuthLevel(), 'com_cs_payments') )
		{
			$app->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'error');
			//todos: setredirect to home page
			$this->setRedirect(JRoute::_("index.php",false));
			return false;
		}
*/
		$obj->executeAction(); // todos: check return

		$this->setRedirect(JRoute::_("index.php?option=com_cs_payments&view=payproc", false));
	}
	/**
	 * Method to authorize & capture a credit card payment on authorize.net
	 *
	 * @return	void
	 * @since	1.6
	 */
	protected function doAuthorizeDotNet( $data, $bReal = false )
	{
		// By default, this sample code is designed to post to our test server for
		// developer accounts: https://test.authorize.net/gateway/transact.dll
		// For real accounts (even in test mode), please make sure that you are
		// posting to: https://secure.authorize.net/gateway/transact.dll
		$test_mode = $bReal ? "FALSE" : "TRUE";
		//$post_url = "https://test.authorize.net/gateway/transact.dll";
		$post_url = "https://secure.authorize.net/gateway/transact.dll";

		// get login id and transaction key from component params
		$adn_login = JComponentHelper::getParams('com_cs_payments')->get('authorizedotnet_login_id');
		$adn_tran_key = JComponentHelper::getParams('com_cs_payments')->get('authorizedotnet_transaction_key');

		$post_values = array(
	
				"x_login"			=> $adn_login,
				"x_tran_key"		=> $adn_tran_key,
				"x_test_request"	=> $test_mode,	// test mode until ready to submit actual transactions
				"x_version"			=> "3.1",
				"x_delim_data"		=> "TRUE",
				"x_delim_char"		=> "|",
				"x_relay_response"	=> "FALSE",
	
				"x_type"			=> "AUTH_CAPTURE",
				"x_method"			=> "CC",
	
				"x_card_num"		=> $data["ccnum"],		//test visa "4111111111111111" (4 and 15 ones)
				"x_exp_date"		=> $data["ccexpdate"],	// mmyy
	
				"x_amount"			=> $data["amount"],
				"x_description"		=> $data["trans_desc"],
	
				"x_first_name"		=> $data["fname"],
				"x_last_name"		=> $data["lname"],
				"x_card_code"		=> $data["ccv"],
				//"x_address"		=> $data["address"],	// optional - only used if address verification is enabled
				//"x_city"			=> $data["city"],		// optional
				//"x_state"			=> $data["state"],		// optional
				"x_invoice_num"		=> $data["conf_num"],	// unique autoincrement db id
				"x_email" 			=> $data["email"],		// optional - used to contact declines if desired
				"x_phone" 			=> $data["phone"],		// optional - used to contact declines if desired

				// Additional fields can be added here as outlined in the AIM integration
				// guide at: http://developer.authorize.net
				//"merchant_defined_1" => "test123",
		);
	
		if ( isset( $data["zip"] ) )
			$post_values["x_zip"] = $data["zip"];	// only used for avs/$0 visa trans
		/*
		GOOD result:
		1. 1
		2. 1
		3. 1
		4. (TESTMODE) This transaction has been approved.
		5. 000000
		6. P
		7. 0
		8. fid
		9. Sample Transaction
		10. 0.01
		11. CC
		12. auth_capture
		13. memid
		14. John
		15. Doe
		16.
		17. 1234 Street
		18. Seattle
		19. WA
		20. 98004
		21.
		...
		37.
		38. 4F89AAAE835FA33DC3A6DEB7AFBB9F94
		39.
		...
		68.
		69. test321
		70. test123
	
		BAD result:
		# 3
		# 1
		# 6
		# (TESTMODE) The credit card number is invalid.
		# 000000
		# P
		# 0
		# fid
		# Sample Transaction
		# 0.01
		# CC
		# auth_capture
		
		*/

		// This section takes the input fields and converts them to the proper format
		// for an http post.  For example: "x_login=username&x_tran_key=a1B2c3D4"
		$post_string = "";
		foreach( $post_values as $key => $value )
		{
			$post_string .= "$key=" . urlencode( $value ) . "&";
		}
		$post_string = rtrim( $post_string, "& " );

		// This sample code uses the CURL library for php to establish a connection,
		// submit the post, and record the response.
		// If you receive an error, you may want to ensure that you have the curl
		// library enabled in your php configuration
		//echo "adn1: post_string=\"$post_string\"<br>";
		$request = curl_init($post_url); // initiate curl object
		curl_setopt($request, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($request, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($request, CURLOPT_POSTFIELDS, $post_string); // use HTTP POST to send form data
		curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response.
		$post_response = curl_exec($request); // execute curl post and store results in $post_response
		// additional options may be required depending upon your server configuration
		// you can find documentation on curl options at http://www.php.net/curl_setopt
		curl_close ($request); // close curl object
	
		// This line takes the response and breaks it into an array using the specified delimiting character
		return explode($post_values["x_delim_char"],$post_response);
	
		// individual elements of the array could be accessed to read certain response
		// fields.  For example, response_array[0] would return the Response Code,
		// response_array[2] would return the Response Reason Code.
		// for a list of response fields, please review the AIM Implementation Guide
		//
	}
	protected function isPaymentAuthorized( $data, $conf_num, $reason )
	{
		// todos:  store an attempt to pay in the DB for staff processing
		// The DB record will be updated when a payment is actually authorized

if ( $data["card_first_name"] == "Ted" && $data["card_last_name"] == "Lowe")
	return (array("success_response"=>"testing123"));
	
		// prepare to call authorize.net -
		// form will not submit unless payment authorization/capture is successful
		$kv = array();
		$kv["fname"] = $data["card_first_name"];
		$kv["lname"] = $data["card_last_name"];
		$kv["ccexpdate"] = $data["cardexpmonth"] . substr($data["cardexpyear"],-2);
		$kv["ccnum"] = $data["cardno"];
		$kv["ccv"] = $data["cardccv"];
		$kv["amount"] = $data["amount"];
		$kv["trans_desc"] = $reason . "|" . $data["payment_reason"];
		$kv["zip"] = "60187"; // todos: only for $0 visa transactions or future AVS
		$kv["conf_num"] = $conf_num;
		$kv["email"] = $data["email"];	// optional info to contact declines if desired
		$kv["phone"] = $data["phone"];	// optional info to contact declines if desired

		$response_array = $this->doAuthorizeDotNet($kv,true);	// true = live/real

		$ret = array();
		
		if ( $response_array[0] != "1" )
		{
			$ret["error_response"] = $response_array[3];
		}
		else
		{
			$ret["success_response"] = $response_array[6];
		}

		return $ret;
	}
	public function payform()
	{
		require_once JPATH_COMPONENT.'/helpers/cs_payments.php';

		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN')."2");

		$reason = Cs_paymentsHelper::getReasonOrElse();
		
		// determine payment method
		// if authorize.net is configured, use it
		// else if paypal is configured, use it
		// else configuration error
		
		// get authorize.net login id and transaction key from component params
		$adn_login = JComponentHelper::getParams('com_cs_payments')->get('authorizedotnet_login_id');
		$adn_tran_key = JComponentHelper::getParams('com_cs_payments')->get('authorizedotnet_transaction_key');
		if ( (!empty($adn_login)) && (!empty($adn_tran_key)))
			$this->setRedirect(JRoute::_("index.php?option=com_cs_payments&reason=$reason&view=payform", false));
		else
		{				
			$paypal_email_address = JComponentHelper::getParams('com_cs_payments')->get('org_paypal_email_address');
			if ( !empty($paypal_email_address))
				$this->setRedirect(JRoute::_("index.php?option=com_cs_payments&reason=$reason&view=paypalform", false));
			else
			{
				JFactory::getApplication()->enqueueMessage(JText::_('COM_CS_PAYMENTS_CONFIG_ERROR_NO_PAYMENT_PROCESSOR'), 'error');
				$this->setRedirect(JRoute::_("index.php", false));
			}
		}
	}

	/**
	 * Method to submit payment to payment processor and checks for response
	 *
	 * @return	void
	 * @since	1.6
	 */
	public function gotopaypal()
	{
		require_once JPATH_COMPONENT.'/helpers/cs_payments.php';
	
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
	
		$app	= JFactory::getApplication();
		$reason = Cs_paymentsHelper::getReasonOrElse();
		/*xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
 com_donatenami/donatenami.php:		$ppc = new ofPayPalCollector( "donate", $memtype_str, $dues, configGetOrgEmailAddress("paypal"), $conf_num );
com_join/join.php:		$ppc = new ofPayPalCollector( "join", $memtype_str, $dues, configGetOrgEmailAddress("paypal"), $conf_num );
com_joinnami/joinnami.php:		$ppc = new ofPayPalCollector( "join", $memtype_str, $dues, configGetOrgEmailAddress("paypal"), $conf_num );
com_paypal/paypal.php:	$ppc = new ofPayPalCollector( $data[noun], $data[reason], $data[amount], configGetOrgEmailAddress("paypal") );
com_ppcol/ppcol.php:class ofPaypalCollector	// {{{1
com_renew/renew.php:		$ppc = new ofPayPalCollector( "renew", $memtype_str, $dues, configGetOrgEmailAddress("paypal"), $conf_num );
com_workshopregister/workshopregister.php:		$ppc = new ofPayPalCollector( "workshop", "ISEA Workshop Registration", $total_due, configGetOrgEmailAddress("paypal"), $conf_num );

		 xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
		 */
		$ppc = new ofPayPalCollector( "renew", "Individual", "20", "tedl57@gmail.com", "123" );
		$ppc->setCustom( "fname lname, note" );
		$ppurl = $ppc->getURL();

		// build paypal URL
		//$ppurl = "https://sandbox.paypal.com/us/cgi-bin/webscr?cmd=_xclick&custom=Ted+Lowe%2C+tttt&amount=15&item_name=membership+dues&no_shipping=1&no_note=1&invoice=836&business=paypal@fveaa.org&cancel_return=http%3A%2F%2Fwww.fveaa.org%2Fcomponents%2Fcom_ppcol%2Fppcol.php%3Faction%3Dcancelled%26actid%3D836&rm=2&return=http%3A%2F%2Fwww.fveaa.org%2Fcomponents%2Fcom_ppcol%2Fppcol.php%3Faction%3Dcompleted%26actid%3D836&notify_url=http%3A%2F%2Fwww.fveaa.org%2Fcomponents%2Fcom_ppcol%2Fppcol.php%3Faction%3Dnotify%26actid%3D836";
		$this->setRedirect($ppurl);
	}

	/**
	 * Method to submit payment to payment processor and checks for response
	 *
	 * @return	void
	 * @since	1.6
	 */
	public function confirmpayment()
	{
		require_once JPATH_COMPONENT.'/helpers/cs_payments.php';

		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$app	= JFactory::getApplication();
		$reason = Cs_paymentsHelper::getReasonOrElse();
		$now = date('Y-m-d H:i:s', time() );
		
		// save reasondata in DB prior to payment authorization attempt:
		//	1) to have conf_num to pass in authorization request so payment processor transaction can be traced to org's payment record
		//  2) to track failed attempts (to see if there are technical or user errors)
		
		$data = $app->getUserState('com_cs_payments.payment.data', array());
		$data["datetimestamp"] = $now;
		$data["payment_type"] = $reason;	// join, renew or donate
		if ( $reason != "donate" )
		{
			//todos: check for proper settings
			$arr = explode('|',$data["payment_reason"]);	// Individual|60|0|0
			$data["amount"] = $arr[ 1 ];
		}
		
		$theModel = JModelLegacy::getInstance('payments', 'Cs_paymentsModel');
		if ( ( $conf_num = $theModel->save($data) ) === false )	//todos: need conf_num and what happens on fail
		{
			jexit('Failed to save preauthorization data');	//todos:
		}
		unset($data["datetimestamp"]);

		// combine reasondata and ccdata to save in registry in a single place where loadData expects it
		
		$ccdata = $this->input->post->get('jform',array(),'array');
		$data = array_merge($data,$ccdata);
		
		// "call" payment processor to authorized payment
		$ret = $this->isPaymentAuthorized( $data, $conf_num, $reason );
		
		if ( isset( $ret["error_response"] ) )
		{
			JFactory::getApplication()->enqueueMessage("Payment Failed: ".$ret["error_response"], 'error');

			// save combined data to repopulate credit card form
			$app->setUserState('com_cs_payments.payment.data', $data);

			// save error to the pre-authorize-try payment record for (todos: doc perhaps future) analysis
			// include previous id to cause an update instead of an insert
			$data['id'] = $conf_num;
			$ipinfo = isset( $_SERVER["REMOTE_ADDR"]) ? " (ip addr=" . $_SERVER["REMOTE_ADDR"] . ")" : " (no ip addr)";
			$data['response'] = $ret["error_response"] . $ipinfo;
			if ( $theModel->save($data) === false )
			{
				jexit('Failed to update posterror data');	//todos:
			}
			$this->setRedirect(JRoute::_("index.php?option=com_cs_payments&reason=$reason&view=payform", false));

			return false;
		}
		// store payment confirmation number for use on payment confirmed view
		$app->setUserState('com_cs_payments.payment.id',$conf_num);
		// clear out all form data after successful payment
		$app->setUserState('com_cs_payments.payment.data',null);

		// save successful payment authorization to DB with payment processor's transaction id and set the time paid
		// include previous id to cause an update instead of an insert

		$paiddata = array();
		$paiddata["id"] = $conf_num;
		$paiddata["response"] = $ret["success_response"];
		$paiddata["date_paid"] = $now;
		if ( $theModel->save($paiddata) === false )
		{
			jexit('Failed to update postsuccess data');	//todos:
		}
		
		// send confirmation via email
		$this->onCompleted( $data, $conf_num, $now );

		// show user the payment succeeded
		$this->setRedirect(JRoute::_("index.php?option=com_cs_payments&reason=$reason&view=confirmpayment", false));

		return true;
	}
	/* create and return a text message that will be emailed back to the user and org rep
	 */
	public function getConfirmationMsg( $data, $conf_num, $now )
	{
		$reason_lower = $reason_noun = $addr = "";
		$addr = "";
		$payment_type = $data["payment_type"];
		$first_name= $data["first_name"];
		$last_name = $data["last_name"];
		$phone = $data["phone"];
		$phone_type= $data["phone_type"];
		$email = $data["email"];
	
		switch($payment_type)
		{
		case 'join': // membership application per Bev 6/2017
			$reason_lower = "membership application";
			$reason_noun = "Applicant";
			break;
		case 'renew':
			$reason_lower = "renewal";
			$reason_noun = "Member";
			break;
		case 'donate':
			$reason_lower = "donation";
			$reason_noun = "Donor";
			break;
		default:
			jexit('improper reason');
		}
		if ( $payment_type != "renew" )
			$addr = "
${data['address']}
${data['city']}, ${data['usastate']} ${data['zipcode']}";
		
		$reason_upper = ucwords($reason_lower);
		
		if ( $payment_type == "donate" )
			$info = "Fund: ${data['payment_reason']}
Amount: \$${data['amount']}";
		else
		{
			$arr = explode('|',$data["payment_reason"]);
			$typ = $arr[0];
			$len = $arr[2];
			$s = $len > 1 ? "s" : "";
			$dues = '$' . $data["amount"];
			
			$info = "Type: $typ
Length: $len Year$s
Dues: $dues";
		}
		$msg = "Thank you for your $reason_lower!
		
Your $reason_lower has been recorded as of $now with confirmation # $conf_num.
		
$reason_noun Information:
		
$first_name $last_name";
		$msg .= $addr;
		$msg .= "
$phone ($phone_type)
$email
		
";
if ( $payment_type == 'join' )
	$msg .= "Membership applied for:

$info

We will process your application as soon as possible and then send your confirmation email with more details.

Thank you!
";
else 
	$msg .= "$reason_upper Information:

$info
";
		return $msg;
	}
	public function onCompleted( $data, $conf_num, $now )
	{
		/*
donation:Array ( [first_name] => Ted [last_name] => Lowe [address] => 2003 Paddock Ct [city] => Wheaton [usastate] => IL [zipcode] => 60187 [phone] => 630-260-0424 [phone_type] => Cell [email] => lists@creativespirits.org [payment_reason] => Annual Fund (matched) [amount] => 1 [otheramount] => 1 [payment_type] => donate [card_first_name] => Ted [card_last_name] => Lowe [cardno] => 4111111111111112 [cardexpmonth] => 09 [cardexpyear] => 2016 [cardccv] => 123 )
join:Array ( [first_name] => Ted [last_name] => Lowe [address] => 2003 Paddock Ct [city] => Wheaton [usastate] => IL [zipcode] => 60187 [phone] => 630-260-0424 [phone_type] => Cell [email] => lists@creativespirits.org [birthdate] => 1957-11-06 [gender] => Male [lang_pref] => English [payment_reason] => Single Person|60|1|0|0 [source] => Web Browsing [amount] => 60 [payment_type] => join [card_first_name] => Ted [card_last_name] => Lowe [cardno] => 4111111111111112 [cardexpmonth] => 01 [cardexpyear] => 2032 [cardccv] => 123 )
renew:Array ( [first_name] => Ted [last_name] => Lowe [phone] => 630-260-0424 [phone_type] => Cell [email] => lists@creativespirits.org [payment_reason] => Single Person|60|1|0|0 [amount] => 60 [payment_type] => renew [card_first_name] => Ted [card_last_name] => Lowe [cardno] => 4111111111111112 [cardexpmonth] => 12 [cardexpyear] => 2032 [cardccv] => 123 )

		 */
	
		$to = $data["email"];
		$name = $data["first_name"] . " " . $data["last_name"];
		
		// prepare the email From: line
		$org_rep = JComponentHelper::getParams('com_cs_payments')->get('org_membership_email_address');
		$org_abbr = JComponentHelper::getParams('com_cs_payments')->get('org_name_abbr');
		$org_dept = $type == "donate" ? "Donation" : "Membership";
		$from = sprintf( "%s %s <%s>", $org_abbr, $org_dept, $org_rep );
		$addhdrs = "From: " . $from . "\r\n";
		
		// prepare the email Subject: line
		$type = $data["payment_type"];
		$subjtype = $type == "join" ? "Membership" : (($type == "renew") ? "Renewal" : "Donation");
		$subj = "$subjtype Confirmation for $name";
		
		//$person_info = getPersonInfo( $data, "\n" );
		//$what_info = getWhatInfo( $data, "\n" );

		$msg = $this->getConfirmationMsg( $data, $conf_num, $now );

		// if sending email to person, bcc org, else just email org
	
		if ( ! empty( $to ) )
		{
			$addhdrs .= "Bcc: " . $org_rep . "\r\n";
		}
		else
		{
			$to = $org_rep;
		}
	
		mail( $to, $subj, $msg, $addhdrs );
		
		//todos: check mail() return status???
	}

/**
 * Method to confirm information was added properly (server-side validation).
 *
 * @return	void
 * @since	1.6
 */
public function confirminfo()
{
	// Check for request forgeries.
	JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

	// Initialise variables.
	$app	= JFactory::getApplication();
	$model = $this->getModel('Payments', 'Cs_paymentsModel');

	// Get the user data.
	$data = JFactory::getApplication()->input->get('jform', array(), 'array');

	// Validate the posted data.
	$form = $model->getForm();
	if (!$form) {
		JError::raiseError(500, $model->getError());
		return false;
	}

	// set amount properly for all reasons
	
	if ( isset( $data["amount"]))
	{
		// donate
		
		if ( $data["amount"] == "-1" )
			$data["amount"] = (int) $data["otheramount"];
	}
	else
	{
		// join/renew
		$arr = explode('|',$data["payment_reason"]);	// Individual|60|0|0
		$data["amount"] = $arr[1];
	}

	$amount = $data["amount"];

	// Validate the posted data.
	$data = $model->validate($form, $data);

	// Check for errors.
	if ($data === false) {
		// Get the validation messages.
		$errors	= $model->getErrors();

		// Push up to three validation messages out to the user.
		for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++) {
			if ($errors[$i] instanceof Exception) {
				$app->enqueueMessage($errors[$i]->getMessage(), 'warning');
			} else {
				$app->enqueueMessage($errors[$i], 'warning');
			}
		}

		// Save the data in the session.
		$app->setUserState('com_cs_payments.payment.data', JRequest::getVar('jform'),array());

		// Redirect back to the form to allow the user to correct the errors caught in this server-side validation
		$id = (int) $app->getUserState('com_cs_payments.payment.id');
		$this->setRedirect(JRoute::_('index.php?option=com_cs_payments&reason='.$app->input->get('reason').'&view=infoform', false));
		return false;
	}

	// Save the data in the session.
	$app->setUserState('com_cs_payments.payment.data', array_merge(JRequest::getVar('jform'),array('amount'=>$amount)));

	// redirect forward to confirm screen
	$this->setRedirect(JRoute::_('index.php?option=com_cs_payments&reason='.$app->input->get('reason').'&view=confirminfo', false));
	return true;
} 
}
