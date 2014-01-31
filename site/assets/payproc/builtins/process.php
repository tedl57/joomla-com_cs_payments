<?php
defined('_JEXEC') or die; // No direct access

// base class
require_once JPATH_COMPONENT.'/assets/payproc/base.php';

class Cs_paymentsPayprocActionProcess extends Cs_paymentsPayprocAction
{
	static public function getAuthLevel() { return 'core.edit.state'; }
	public function getTitle() { return "Processing the payment will remove it from this list and add it to the one below"; }
	public function doConfirm() { return true; }
	public function isBuiltin() { return true; }
	public function executeAction()
	{
		// set processed date and by which user's name
		$data = array();
		$data['id'] = $this->id;
		$data['processed_date'] = date('Y-m-d H:i:s', time() );	// todos: may be able to use JFactory::getDate();
		$data['processed_by'] = $this->username;
		$theModel = JModelLegacy::getInstance('payments', 'Cs_paymentsModel');
		if ( $theModel->save($data) === false )
		{
			jexit('Failed to update process data');	//todos:
		}
		return true;
	}
}
?>
