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

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.keepalive');

// Import CSS
$document = JFactory::getDocument();
$document->addStyleSheet('components/com_cs_payments/assets/css/cs_payments.css');
?>
<script type="text/javascript">
    js = jQuery.noConflict();
    js(document).ready(function(){
        
    });
    
    Joomla.submitbutton = function(task)
    {
        if(task == 'payment.cancel'){
            Joomla.submitform(task, document.getElementById('payment-form'));
        }
        else{
            
            if (task != 'payment.cancel' && document.formvalidator.isValid(document.id('payment-form'))) {
                
                Joomla.submitform(task, document.getElementById('payment-form'));
            }
            else {
                alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED')); ?>');
            }
        }
    }
</script>

<form action="<?php echo JRoute::_('index.php?option=com_cs_payments&layout=edit&id=' . (int) $this->item->id); ?>" method="post" enctype="multipart/form-data" name="adminForm" id="payment-form" class="form-validate">
    <div class="row-fluid">
        <div class="span10 form-horizontal">
            <fieldset class="adminform">

                				<input type="hidden" name="jform[id]" value="<?php echo $this->item->id; ?>" />
				<input type="hidden" name="jform[amount]" value="<?php echo $this->item->amount; ?>" />
				<input type="hidden" name="jform[payment_type]" value="<?php echo $this->item->payment_type; ?>" />
				<input type="hidden" name="jform[what]" value="<?php echo $this->item->what; ?>" />
				<input type="hidden" name="jform[datetimestamp]" value="<?php echo $this->item->datetimestamp; ?>" />
				<input type="hidden" name="jform[date_paid]" value="<?php echo $this->item->date_paid; ?>" />
				<input type="hidden" name="jform[date_completed]" value="<?php echo $this->item->date_completed; ?>" />
				<input type="hidden" name="jform[date_cancelled]" value="<?php echo $this->item->date_cancelled; ?>" />
				<input type="hidden" name="jform[processed_by]" value="<?php echo $this->item->processed_by; ?>" />
				<input type="hidden" name="jform[processed_date]" value="<?php echo $this->item->processed_date; ?>" />
				<input type="hidden" name="jform[response]" value="<?php echo $this->item->response; ?>" />

				<?php if(empty($this->item->created_by)){ ?>
					<input type="hidden" name="jform[created_by]" value="<?php echo JFactory::getUser()->id; ?>" />

				<?php } 
				else{ ?>
					<input type="hidden" name="jform[created_by]" value="<?php echo $this->item->created_by; ?>" />

				<?php } ?>			<div class="control-group">
				<div class="control-label"><?php echo $this->form->getLabel('first_name'); ?></div>
				<div class="controls"><?php echo $this->form->getInput('first_name'); ?></div>
			</div>
			<div class="control-group">
				<div class="control-label"><?php echo $this->form->getLabel('last_name'); ?></div>
				<div class="controls"><?php echo $this->form->getInput('last_name'); ?></div>
			</div>
			<div class="control-group">
				<div class="control-label"><?php echo $this->form->getLabel('address'); ?></div>
				<div class="controls"><?php echo $this->form->getInput('address'); ?></div>
			</div>
			<div class="control-group">
				<div class="control-label"><?php echo $this->form->getLabel('city'); ?></div>
				<div class="controls"><?php echo $this->form->getInput('city'); ?></div>
			</div>
			<div class="control-group">
				<div class="control-label"><?php echo $this->form->getLabel('usastate'); ?></div>
				<div class="controls"><?php echo $this->form->getInput('usastate'); ?></div>
			</div>
			<div class="control-group">
				<div class="control-label"><?php echo $this->form->getLabel('zipcode'); ?></div>
				<div class="controls"><?php echo $this->form->getInput('zipcode'); ?></div>
			</div>
			<div class="control-group">
				<div class="control-label"><?php echo $this->form->getLabel('phone'); ?></div>
				<div class="controls"><?php echo $this->form->getInput('phone'); ?></div>
			</div>
			<div class="control-group">
				<div class="control-label"><?php echo $this->form->getLabel('phone_type'); ?></div>
				<div class="controls"><?php echo $this->form->getInput('phone_type'); ?></div>
			</div>
			<div class="control-group">
				<div class="control-label"><?php echo $this->form->getLabel('email'); ?></div>
				<div class="controls"><?php echo $this->form->getInput('email'); ?></div>
			</div>
			<div class="control-group">
				<div class="control-label"><?php echo $this->form->getLabel('payment_reason'); ?></div>
				<div class="controls"><?php echo $this->form->getInput('payment_reason'); ?></div>
			</div>
			<div class="control-group">
				<div class="control-label"><?php echo $this->form->getLabel('source'); ?></div>
				<div class="controls"><?php echo $this->form->getInput('source'); ?></div>
			</div>


            </fieldset>
        </div>

        

        <input type="hidden" name="task" value="" />
        <?php echo JHtml::_('form.token'); ?>

    </div>
</form>