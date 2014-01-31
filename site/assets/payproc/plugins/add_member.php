<?php
defined('_JEXEC') or die; // No direct access

// base class
require_once JPATH_COMPONENT.'/assets/payproc/base.php';

class Cs_paymentsPayprocActionAdd_member extends Cs_paymentsPayprocAction
{
	static public function getAuthLevel() { return 'core.edit.state'; }
	public function getTitle() { return "Create new member from this payment?"; }
	public function doConfirm() { return true; }
	public function isBuiltin() { return false; }
	public function executeAction()
	{
		jexit('add member');	//todos:
		return true;
	}
}
?>
