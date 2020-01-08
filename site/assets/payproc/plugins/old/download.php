<?php
// No direct access
defined('_JEXEC') or die;

// base class
require_once JPATH_COMPONENT.'/assets/payproc/base.php';
require_once JPATH_COMPONENT.'/helpers/cs_payments.php';

class Cs_paymentsPayprocActionDownload extends Cs_paymentsPayprocAction
{
	static public function getAuthLevel() { return 'core.edit'; }
	public function getTitle() { return "Download a CSV file containing the payment information?"; }
	public function executeAction()
	{
		//$row = mdb3GetTableRowByID( getTableName(TBLNAME), $id );

		$theModel = JModelLegacy::getInstance('payments', 'Cs_paymentsModel');
		if ( ( $data = $theModel->getData($this->id) ) === false )	//todos: check return
			jexit('dl - cannot get data for id '.$this->id);
/*
var_dump($dat);		
public 'id' => string '1042' (length=4)
public 'amount' => string '108' (length=3)
public 'payment_type' => string 'renew' (length=5)
public 'payment_reason' => string 'Single Person|108|2|0|0' (length=23)
public 'datetimestamp' => string '2014-01-03 21:17:30' (length=19)
public 'date_paid' => string '2014-01-03 21:17:30' (length=19)
public 'processed_by' => string 'admin' (length=5)
public 'processed_date' => string '2014-01-05 09:58:20' (length=19)
public 'response' => string '2204298982' (length=10)
public 'created_by' => string '0' (length=1)
public 'first_name' => string 'Ted' (length=3)
public 'last_name' => string 'Lowe' (length=4)
public 'address' => string '' (length=0)
public 'city' => string '' (length=0)
public 'usastate' => string '' (length=0)
public 'zipcode' => string '' (length=0)
public 'phone' => string '555-555-5555' (length=12)
public 'phone_type' => string 'Home' (length=4)
public 'email' => string 'user@example.com' (length=9)
public 'source' => string '' (length=0)
public 'gender' => string '' (length=0)
public 'lang_pref' => string '' (length=0)
	*/
		$datarows = mapDataToCSV($data);
		
		exit(Cs_paymentsHelper::csvResults($datarows,false,$this->id.".csv"));
//jexit("username=".$this->username.", id=".$this->id.", action=".$this->action);
		//return true;
	}
}
////////////////////////////////////////////////////////////
// functions
/*
 Here is the "District map:"

Central District:
Arkansas, Illinois, Indiana, Iowa, Kansas, Louisiana, Michigan, Minnesota, Missouri, Ohio, Oklahoma, Texas, Wisconsin

Western District:
Alaska, Arizona, California, Colorado, Hawaii, Idaho, Montana, Nebraska, Nevada, New Mexico, North Dakota, Oregon, South Dakota, Utah, Washington, Wyoming

Eastern District:
Alabama, Connecticut, Delaware, District of Columbia, Florida, Georgia, Kentucky, Maine, Maryland, Massachusetts, Mississippi, New Hampshire, New Jersey, New York, North Carolina, Pennsylvania, Rhode Island, South Carolina, Tennessee, Vermont, Virginia, West Virginia
*/
function mapStateToDistrict($stabbr)
{
	$district_map = array(
			"Central" => array( "AK", "IL", "IN", "IA", "KS", "LA", "MI", "MN", "MO", "OH", "OK", "TX", "WI" ),
			"Western" => array( "AK", "AZ", "CA", "CO", "HI", "ID", "MT", "NE", "NV", "NM", "ND", "OR", "SD", "UT", "WA", "WY" ),
			"Eastern" => array( "AL", "CT", "DE", "DC", "FL", "GA", "KY", "ME", "MD", "MA", "MS", "NH", "NJ", "NY", "NC", "PA", "RI", "SC", "TN", "VT", "VA", "WV" )
	);
	foreach( $district_map as $k => $arr )
	{
		if ( in_array($stabbr,$arr) )
			return $k;
	}
	return "No District";
}
function getPhoneTypeArray($fon,$typ)
{
	$ret = array("","","");
	$ndx = 0;
	if ( $typ == "Work" )
		$ndx = 1;
	else if ( $typ == "Cell" )
		$ndx = 2;
	$ret[$ndx] = $fon;
	return $ret;
}
function mapDataToCSV($row)
{
	//$is_don = $row["item_type"] == "donate";
	//$datrow = mdb2GetTableRowByID( $is_don ? "tbldonors" : "tblmembers", $row["item_id"] );
	// insert some data before mapping
	$row = array();
	$row["id"] = "1849";
	$row["person"] = 'a:9:{s:5:"fname";s:8:"Robert S";s:5:"lname";s:6:"Milmer";s:7:"address";s:20:"1234 County Road 321";s:4:"city";s:4:"Main";s:5:"state";s:2:"CO";s:3:"zip";s:5:"55555";s:10:"phone_type";s:4:"Home";s:5:"phone";s:12:"555-555-5555";s:5:"email";s:22:"ddddddd.mmmmmmm@mm.com";}';
	$row["date_paid"] = "2014-01-02 22:20:20";
	$row["amount"] = "50";
	$row["what"] = "Annual Fund (matched)";
	$row["payment_reason"] = "d";
////////////////////
	$datrow = unserialize($row["person"]);
	$datrow["date"]=date("m/d/Y",strtotime($row["date_paid"]));
	$datrow["amount"]=$row["amount"];
	$datrow["what"]=$row["what"];
	$datrow["id1"]="WEB1-".$row["id"];
	$datrow["id2"]="WEB2-".$row["id"];
	$datrow["id3"]="WEB3-".$row["id"];
	$datrow["addr_region"] = mapStateToDistrict( $datrow["state"] );
	list( $datrow["hphone"], $datrow["wphone"], $datrow["cphone"] ) = getPhoneTypeArray( $datrow["phone"], $datrow["phone_type"]);
	//$datrow[strtolower(substr($datrow["phone_type"],0,1))."phone"] = $datrow["phone"];
	//unset($datrow["phone_type"]);
	//unset($datrow["phone"]);
	
	//echo "<br>1:row<br>"; Var_Dump::display($row); echo "<br>";
	//echo "<br>1.5:datrow<br>"; Var_Dump::display($datrow); echo "<br>";
	$map = array( 
			"j" => array(
					array("ImportID"=>"",),
					array("KeyInd"=>"I",),
					array("FirstName"=>"fname",),
					array("LastName"=>"lname",),
					array("PrimAddID"=>"45",),
					array("PrimSalID"=>"16",),
					array("AddrImpID"=>"id1",),
					array("PrefAddr"=>"Yes",),
					array("AddrType"=>"Home",),
					array("AddrLines"=>"address",),
					array("AddrCity"=>"city",),
					array("AddrState"=>"state",),
					array("AddrZIP"=>"zip",),
					array("PhoneAddrImpID"=>"id1",),
					array("PhoneImpID"=>"id2",),
					array("PhoneNum"=>"phone",),
					array("PhoneType"=>"phone_type",),
					array("PhoneAddrImpID"=>"id1",),
					array("PhoneImpID"=>"id3",),
					array("PhoneNum"=>"email",),
					array("PhoneType"=>"E-mail",),
					array("CAttrCat"=>"New Member Letter",),
					array("CAttrDesc"=>"2",),
					array("CAttrDate"=>"date",),	// just use mm/dd/yyyy paid
					array("CAttrCat"=>"Join Method",),
					array("CAttrDesc"=>"source",),
					array("CAttrDate"=>"date",),	// just use mm/dd/yyyy paid
					array("Bday"=>"bday",),			//  mm/dd/yyyy if collected
					array("AddrRegion"=>"addr_region",),
					array("Gender"=>"gender",),
					array("ConsCodeImpID"=>"",),	// leave blank
					array("ConsCode"=>"language",),	// preferred language
				),
			"d"=> array(
					array("First Name"=>"fname",),
					array("Last Name"=>"lname",),
					array("Street"=>"address",),
					array("City"=>"city",),
					array("State"=>"state",),
					array("Zip"=>"zip",),
					array("Home Phone"=>"hphone",),
					array("Work Phone"=>"wphone",),
					array("Cell Phone"=>"cphone",),
					array("Email"=>"email",),
					array("Donation Amount"=>"amount",),
					array("Donation Date"=>"date",),
					array("Donation Fund"=>"what",),
				),
			"r" => array(
					array("First Name"=>"fname",),
					array("Last Name"=>"lname",),
					array("Home Phone"=>"hphone",),
					array("Work Phone"=>"wphone",),
					array("Cell Phone"=>"cphone",),
					array("Email"=>"email",),
					array("Renewal Amount"=>"amount",),
					array("Renewal Date"=>"date",),
					array("Renewal Type"=>"what",),
				)
			);
	$usemap = $map[$row["payment_reason"]];
	//echo "<br>2:usemap<br>"; Var_Dump::display($usemap); echo "<br>";

	//$rowkeys = array_keys($datrow);
	//echo "<br>3:rowkeys<br>"; Var_Dump::display($rowkeys); echo "<br>";
	$mapout = array();
	$ndx = 0;
	foreach( $usemap as $arr )
	{
		list($k,$v) = each($arr);
		//Var_Dump::display($kvpair);echo "<br>";
		//$k = $kvpair["key"];
		//$v = $kvpair["value"];
		// if the data field is set, use it, else just it's fieldname
		$val = ( isset( $datrow[$v] ) ) ? $datrow[$v] : $v;
		$mapout[$ndx++] = array( $k=> $val);
		//$mapout[$k]= in_array($v,$rowkeys) ? $datrow[$v] : $v;
	}
	// test visa 4111111111111111
	// test visa 4 & 15 1's

	//echo "<br>4:mapout<br>"; Var_Dump::display($mapout); echo "<br>";
	return $mapout;
}
?>
