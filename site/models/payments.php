<?php
/**
 * @version     1.0.0
 * @package     com_cs_payments
 * @copyright   Copyright (C) Creative Spirits 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ted Lowe <lists@creativespirits.org> - http://www.creativespirits.org
 */

// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.modelform');
jimport('joomla.event.dispatcher');

/**
 * Cs_payments model.
 */
class Cs_paymentsModelPayments extends JModelForm
{
    
    var $_item = null;
    
	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @since	1.6
	 */
	protected function populateState()	// todos: what does this do???
	{
		$app = JFactory::getApplication('com_cs_payments');

		// Load state from the request userState on edit or from the passed variable on default
        if (JFactory::getApplication()->input->get('layout') == 'edit') {
            $id = JFactory::getApplication()->getUserState('com_cs_payments.payment.id');
        } else {
            $id = JFactory::getApplication()->input->get('id');
            JFactory::getApplication()->setUserState('com_cs_payments.payment.id', $id);
        }
		$this->setState('payment.id', $id);

		// Load the parameters.
        $params = $app->getParams();
        $params_array = $params->toArray();
        if(isset($params_array['item_id'])){
            $this->setState('payment.id', $params_array['item_id']);
        }
		$this->setState('params', $params);

	}
  
	 /**
	  * Method to validate form data.
	  *
	  * @param   xxxxx   $form   JForm object
	  * @param   array   $data   An array of field values to validate.
	  * @param   string  $group  The optional dot-separated form group path on which to filter the
	  *                          fields to be validated.
	  *
	  * @return  mixed  True on sucess.
	  */

	public function validate($form, $data, $group = null)
	{	
		if ( ( ! isset( $data["amount"]) ) || ((int)$data["amount"]) <= 0 )
		{
			$this->setError("Please enter a proper amount.");
			return false;
		}
		
		if ( isset( $data["birthdate"]) )	// birthdate only set if required on join form
		{	
			// validate proper format of a birthdate

			if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$data["birthdate"]))
			{
				$this->setError("Please enter Birthdate using the format yyyy-mm-dd");
				return false;
			}
			
			// validate age-related values

			// calculate joinee's age from birthdate

			$today = new DateTime("now");
			$user = new DateTime($data["birthdate"]);
			$age_diff = $user->diff($today);
			$user_age = (int) $age_diff->format('%R%Y');
			
			// validate user is old enough to join if configured to check
					
			$params = JComponentHelper::getParams('com_cs_payments');
			$age_min = (int) $params->get('join_age_min');
			
			if ( $age_min > 0 )
			{				
				if ( $user_age < $age_min )
				{
					$this->setError("You must be at least $age_min years old to become a member");
				
					return false;
				}
			}
			
			// validate max age for a (eg, student) membership - payment_reason=Student|30|1|0|24
			$payment_reason_array = explode('|',$data["payment_reason"]);
			if ( isset( $payment_reason_array[4] ) )
			{
				$age_max = (int) $payment_reason_array[4];
				if ( $age_max > 0 && $user_age > $age_max )
				{
					$this->setError("You must $age_max or younger to become a " . $payment_reason_array[0] . " member");
					
					return false;
				}
			}

			// validate min age for a (eg, senior) membership - payment_reason=Senior|30|1|75|0
			$payment_reason_array = explode('|',$data["payment_reason"]);
			if ( isset( $payment_reason_array[3] ) )
			{
				$age_min = (int) $payment_reason_array[3];
				if ( $age_min > 0 && $user_age < $age_min )
				{
					$this->setError("You must $age_min or older to become a " . $payment_reason_array[0] . " member");
						
					return false;
				}
			}
		}
		
		return parent::validate($form, $data, $group);
	}

	/**
	 * Method to get an ojbect.
	 *
	 * @param	integer	The id of the object to get.
	 *
	 * @return	mixed	Object on success, false on failure.
	 */
	public function &getData($id = null)
	{
		if ($this->_item === null)
		{
			$this->_item = false;

			if (empty($id)) {
				$id = $this->getState('payment.id');
			}

			// Get a level row instance.
			$table = $this->getTable();

			// Attempt to load the row.
			if ($table->load($id))
			{
  /*              
                $user = JFactory::getUser();
                $id = $table->id;
   			   $canEdit = $user->authorise('core.edit', 'com_cs_payments') || $user->authorise('core.create', 'com_cs_payments');
                if (!$canEdit && $user->authorise('core.edit.own', 'com_cs_payments')) {
                    $canEdit = $user->id == $table->created_by;
                }

                if (!$canEdit) {
                    JError::raiseError('500', JText::_('JERROR_ALERTNOAUTHOR'));
                }
                
				// Check published state.
				if ($published = $this->getState('filter.published'))
				{
					if ($table->state != $published) {
						return $this->_item;
					}
				}
*/
				// Convert the JTable to a clean JObject.
				$properties = $table->getProperties(1);
				$this->_item = JArrayHelper::toObject($properties, 'JObject');
			} elseif ($error = $table->getError()) {
				$this->setError($error);
			}
		}

		return $this->_item;
	}
    
	public function getTable($type = 'Payment', $prefix = 'Cs_paymentsTable', $config = array())
	{   
        $this->addTablePath(JPATH_COMPONENT_ADMINISTRATOR.'/tables');
        return JTable::getInstance($type, $prefix, $config);
	}     

	/**
	 * Method to get the profile form.
	 *
	 * The base form is loaded from XML 
     * 
	 * @param	array	$data		An optional array of data for the form to interogate.
	 * @param	boolean	$loadData	True if the form is to load its own data (default case), false if not.
	 * @return	JForm	A JForm object on success, false on failure
	 * @since	1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		require_once JPATH_COMPONENT.'/helpers/cs_payments.php';

		$app = JFactory::getApplication();
		$reason = Cs_paymentsHelper::getReasonOrElse();
		$viewname = $app->input->get('view');
		
		// forms xml files are either named after the reason (join, renew, donate) or the viewname
		if (empty($viewname) || $viewname == 'infoform')
			$xmlfilename = $reason;
		else 
			$xmlfilename = "$viewname";	
		
		// get the proper form xml file
		$form = $this->loadForm('com_cs_payments.payment', $xmlfilename, array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form)) {
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return	mixed	The data for the form.
	 * @since	1.6
	 */
	protected function loadFormData()
	{
		$data = JFactory::getApplication()->getUserState('com_cs_payments.payment.data', array());
        if (empty($data)) {
            $data = $this->getData();
        }
        
        return $data;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param	array		The form data.
	 * @return	mixed		The user id on success, false on failure.
	 * @since	1.6
	 */
	public function save($data)
	{       
        $table = $this->getTable();
        if ($table->save($data) === true) {//todos: 2nd param can be ignore (assuming fields to ignore in data)
            //todos: return $id;
            return $table->id;
        } else {
            return false;
        }
        
	}  
}