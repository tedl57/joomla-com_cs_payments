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
        if(task == 'membershiptype.cancel'){
            Joomla.submitform(task, document.getElementById('membershiptype-form'));
        }
        else{
            
            if (task != 'membershiptype.cancel' && document.formvalidator.isValid(document.id('membershiptype-form'))) {
                
                Joomla.submitform(task, document.getElementById('membershiptype-form'));
            }
            else {
                alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED')); ?>');
            }
        }
    }
</script>

<form action="<?php echo JRoute::_('index.php?option=com_cs_payments&layout=edit&id=' . (int) $this->item->id); ?>" method="post" enctype="multipart/form-data" name="adminForm" id="membershiptype-form" class="form-validate">
    <div class="row-fluid">
        <div class="span10 form-horizontal">
            <fieldset class="adminform">

   				<input type="hidden" name="jform[id]" value="<?php echo $this->item->id; ?>" />
				<input type="hidden" name="jform[typ]" value="<?php echo $this->item->typ; ?>" />
				<input type="hidden" name="jform[dues]" value="<?php echo $this->item->dues; ?>" />
				<input type="hidden" name="jform[show_order]" value="<?php echo $this->item->show_order; ?>" />
				<input type="hidden" name="jform[lifetime_membership]" value="<?php echo $this->item->lifetime_membership; ?>" />
				<input type="hidden" name="jform[age_max]" value="<?php echo $this->item->age_max; ?>" />
				<input type="hidden" name="jform[age_min]" value="<?php echo $this->item->age_min; ?>" />
				
				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('typ'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('typ'); ?></div>
				</div>
				
				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('dues'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('dues'); ?></div>
				</div>
								
				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('show_order'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('show_order'); ?></div>
				</div>
								
				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('lifetime_membership'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('lifetime_membership'); ?></div>
				</div>
								
				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('age_max'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('age_max'); ?></div>
				</div>
								
				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('age_min'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('age_min'); ?></div>
				</div>
				
            </fieldset>
        </div>

        

        <input type="hidden" name="task" value="" />
        <?php echo JHtml::_('form.token'); ?>

    </div>
</form>