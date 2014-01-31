<?php
// No direct access
defined('_JEXEC') or die;

// base class
require_once JPATH_COMPONENT.'/assets/payproc/base.php';

class Cs_paymentsPayprocActionDownload extends Cs_paymentsPayprocAction
{
	static public function getAuthLevel() { return 'core.edit'; }
	public function getTitle() { return "Download a CSV file containing the payment information?"; }
	public function executeAction()
	{
		//$row = mdb3GetTableRowByID( getTableName(TBLNAME), $id );
		
		$rows = maptocsv($row);
		
		exit(csvResultsTSA($rows,false,"$id.csv"));
		//jexit("username=".$this->username.", id=".$this->id.", action=".$this->action);
		//return true;
	}
}
////////////////////////////////////////////////////////////
// functions
////////////////////////////////////////////////////////////
//copied csvResults from show_results.php to support duplicate fieldnames per TSA
function csvResultsTSA( $kvpairs,$bScreen = false, $outfile = "tblfinances.csv"  ) //{{{1
{
	/*
	 * input:
	* array[0] = array(k=>v);
	* array[1] = array(k=>v);
	*/
	//$bScreen = true;
	//echo "<br>kvpairs:<br>";
	//Var_Dump::display($kvpairs);
	$flds = array();
	$data = array();
	foreach( $kvpairs as $arr )
	{
		$kvpair = each( $arr );
		$flds[] = $kvpair["key"];
		$data[] = $kvpair["value"];
	}

	//echo "<br>flds:<br>";
	//Var_Dump::display($flds);
	//echo "<br>data:<br>";
	//Var_Dump::display($data);
	ob_start();
	echo arrayToCSV( $flds, ',','"',true );
	echo $bScreen ? '<br>' : "";
	echo arrayToCSV( $data, ',','"',true );
	echo $bScreen ? '<br>' : "";
	$str = ob_get_contents();
	ob_end_clean();
	if ( ! $bScreen )
		putHeaderDownloadFileFromString( $outfile, $str );
	else
		echo $str;
}
function arrayToCSV($dataArray,$delimiter,$enclosure,$crbeforenl=false) //{{{1
{
	// Write a line to a file
	// $filePointer = the file resource to write to
	// $dataArray = the data to write out
	// $delimeter = the field separator

	// Build the string
	$string = "";

	// No leading delimiter
	$writeDelimiter = FALSE;
	foreach($dataArray as $dataElement)
	{
		// Replaces a double quote with two double quotes
		$dataElement=str_replace("\"", "\"\"", $dataElement);

		// Adds a delimiter before each field (except the first)
		if($writeDelimiter) $string .= $delimiter;

		// Encloses each field with $enclosure and adds it to the string
		$string .= $enclosure . $dataElement . $enclosure;

		// Delimiters are used every time except the first.
		$writeDelimiter = TRUE;
	} // end foreach($dataArray as $dataElement)

	if ( $crbeforenl )
		$string .= "\r";

	// Append new line
	$string .= "\n";

	// return CSV formatted string
	return $string;
}
function putHeaderDownloadFileFromString( $file, $str ) //{{{1
{
	$l = strlen( $str );

	//	header("Content-Length: $l" );

	// for ie to work
	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Cache-Control: private",false);
	header("Content-Type: application/octet-stream");
	header("Content-Length: $l" );

	//header("Content-Type: application/x-download");
	header("Content-Disposition: attachment; filename=\"$file\"");
	echo $str;
	//	readfile($file);
}
//
//
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
			"Central" => array( "Arkansas", "Illinois", "Indiana", "Iowa", "Kansas", "Louisiana", "Michigan", "Minnesota", "Missouri", "Ohio", "Oklahoma", "Texas", "Wisconsin" ),
			"Western" => array( "Alaska", "Arizona", "California", "Colorado", "Hawaii", "Idaho", "Montana", "Nebraska", "Nevada", "New Mexico", "North Dakota", "Oregon", "South Dakota", "Utah", "Washington", "Wyoming" ),
			"Eastern" => array( "Alabama", "Connecticut", "Delaware", "District of Columbia", "Florida", "Georgia", "Kentucky", "Maine", "Maryland", "Massachusetts", "Mississippi", "New Hampshire", "New Jersey", "New York", "North Carolina", "Pennsylvania", "Rhode Island", "South Carolina", "Tennessee", "Vermont", "Virginia", "West Virginia" )
	);
	//todos:$st = formMapStateAbbrToState( $stabbr );
	foreach( $district_map as $k => $arr )
	{
		if ( in_array($st,$arr) )
			return $k;
	}
	return "No District";

}

function maptocsv($row)
{
	//$is_don = $row["item_type"] == "donate";
	//$datrow = mdb2GetTableRowByID( $is_don ? "tbldonors" : "tblmembers", $row["item_id"] );
	// insert some data before mapping
	$datrow = unserialize($row["person"]);
	$datrow["date"]=date("m/d/Y",strtotime($row["date_paid"]));
	$datrow["amount"]=$row["amount"];
	$datrow["what"]=$row["what"];
	$datrow["id1"]="WEB1-".$row["id"];
	$datrow["id2"]="WEB2-".$row["id"];
	$datrow["id3"]="WEB3-".$row["id"];
	$datrow["addr_region"] = mapStateToDistrict( $datrow["state"] );
	list( $datrow["hphone"], $datrow["wphone"], $datrow["cphone"] ) = array("a","b","c");//todos: getPhoneTypeArray( $datrow["phone"], $datrow["phone_type"]);
	//$datrow[strtolower(substr($datrow["phone_type"],0,1))."phone"] = $datrow["phone"];
	//unset($datrow["phone_type"]);
	//unset($datrow["phone"]);

	//echo "<br>1:row<br>"; Var_Dump::display($row); echo "<br>";
	//echo "<br>1.5:datrow<br>"; Var_Dump::display($datrow); echo "<br>";
	$map = array( "j" =>
			array(
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
	));
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