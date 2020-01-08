<?php
defined('_JEXEC') or die; // No direct access

// base class
require_once JPATH_COMPONENT.'/assets/payproc/base.php';

class Cs_paymentsPayprocActionShow_data extends Cs_paymentsPayprocAction
{
	static public function getAuthLevel() { return 'core.edit.state'; }
	public function getTitle() { return "Show the data for this payment."; }
	public function doConfirm() { return false; }
	public function isBuiltin() { return true; }
	public function executeAction()
	{

		$theModel = JModelLegacy::getInstance('payments', 'Cs_paymentsModel');
		if ( ( $data = $theModel->getData($this->id) ) === false )	//todos: check return
			jexit('showdata - cannot get data for id '.$this->id);

		JFactory::getApplication()->enqueueMessage("Data for ID ".$this->id.":<br />".str_replace(',',',<br />',json_encode($data)), 'success');
		
		return true;
	}
}
?>
