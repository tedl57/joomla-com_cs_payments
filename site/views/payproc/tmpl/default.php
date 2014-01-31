<?php
/**
 * @version     1.0.0
 * @package     com_cs_payments
 * @copyright   Copyright (C) Creative Spirits 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ted Lowe <lists@creativespirits.org> - http://www.creativespirits.org
 */
// no direct access
defined('_JEXEC') or die;

require_once JPATH_COMPONENT.'/helpers/cs_payments.php';

$db = JFactory::getDbo();
$db->setQuery("SELECT * FROM #__cs_payments WHERE date_paid IS NOT NULL AND processed_date IS NULL");
$items = $db->loadObjectList('id');
$nitems = count($items);
echo "<h3>Payment Processor</h3>";
echo "<h5>Items To Process: $nitems</h5>";
if ( $nitems ) :

?>
<table border="1" class="table table-striped">
	<thead>
		<tr>
			<th class="center">ID</th>
			<th class="center">Actions</th>
			<th class="center">Paid</th>
			<th class="center">Date Paid</th>
			<th class="center">Type</th>
			<th class="center">Payer</th>
		</tr>
	</thead>
	<tbody>
<?php

// load builtin and site-specific (plugin) action classes by bootstrapping base class
require_once JPATH_COMPONENT.'/assets/payproc/base.php';
$actions = Cs_paymentsPayprocAction::loadActions();

$item_n = 0;	// to stripe rows
foreach ($items as $item) :
?>
		<tr class="row<?php echo $item_n++ % 2; ?>">
			<td class="center"><?php echo $item->id; ?></td>
			<td class="center"><?php echo getActionsHTML($actions,$item->id); ?></td>				
			<td class="center"><?php echo '$'.$item->amount; ?></td>
			<td class="center"><?php echo $item->date_paid; ?></td>
			<td class="left"><?php echo getPaymentInfo($item); ?></td>
			<td class="left"><?php echo getPersonInfo($item); ?></td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>
<?php endif; 

$db->setQuery("SELECT * FROM #__cs_payments WHERE processed_date IS NOT NULL ORDER BY processed_date DESC LIMIT 10");
$items = $db->loadObjectList();
$nitems = count($items);
echo "<h5>Items Last Processed: $nitems</h5>";
if ( $nitems ) :
?>
<table border="1" class="table table-striped">
	<thead>
		<tr>
			<th class="center">ID</th>
			<th class="center">Paid</th>
			<th class="center">Date Paid</th>
			<th class="center">Type</th>
			<th class="center">Payer</th>
			<th class="center">Processed</th>
		</tr>
	</thead>
	<tbody>
<?php
$item_n = 0;
foreach ($items as $item) :
?>
		<tr class="row<?php echo $item_n++ % 2; ?>">
			<td class="center"><?php echo $item->id; ?></td>
			<td class="center"><?php echo '$'.$item->amount; ?></td>
			<td class="center"><?php echo $item->date_paid; ?></td>
			<td class="left"><?php echo getPaymentInfo($item); ?></td>
			<td class="left"><?php echo getPersonInfo($item); ?></td>
			<td class="left"><?php echo $item->processed_by . "<br />" . $item->processed_date; ?></td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>

<?php 
endif;

function getActionsHTML( $actions, $id )
{
	$ret = "";

	$loggeduser = JFactory::getUser();

	foreach ($actions as $action => $v)
	{
		$classname = Cs_paymentsPayprocAction::getChildClassName($action);
		$obj = new $classname($id,$loggeduser->username,$action);

		$name = $obj->getActionNameUpper();
		//todos: used to say "Are you sure you want to process ID $id?" - make configurable s/how
		$confirm = (!$obj->doConfirm()) ? "" : "onClick=\"return confirm('Are you sure you want to $name?');\"";
		$url = JRoute::_("index.php?option=com_cs_payments&view=payproc&task=payments.payproc&ppaction=".$action."&id=$id");
		$title = $obj->getTitle();
		$ret .= "<a href='$url' title='$title' $confirm>$name</a><br />&nbsp\n";
	}
	return $ret;
}
//////////////////////////////////////////////////////
function getPaymentInfo($item)
{
	$ret = ucwords($item->payment_type);
	$ret .= "<br />";
	$arr = explode('|',$item->payment_reason);	// Individual|20|1|0|0  or Donation Fund
	$ret .= $arr[0];
	$ret .= "<br />";
	if (isset($arr[2])&&$arr[2])	// if not lifetime or donation
	{
		$plural = "s";

		if ( $arr[2] == "1")
			$plural = "";
		$ret .= $arr[2] . " Year$plural";
	}
	return $ret;
}
function getPersonInfo($item,$le="<br />")
{
	// join or donate has all payer info including postal address
	$address = empty( $item->address ) ? "" :
		$item->address . $le . $item->city . ", " . $item->usastate . " " . $item->zipcode . $le;

	$ret = sprintf( "%s %s%s%s%s%s%s (%s)",
				$item->first_name, $item->last_name, $le,
				$address,
				$item->email, $le,
				$item->phone, $item->phone_type
		);

	return $ret;
}
?>