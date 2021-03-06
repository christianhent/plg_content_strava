<?php
/**
 * 
 * @category   GPX Extension Add-on
 * @package    Joomla.Plugin
 * @subpackage Content.Zatracks.Strava
 * @author     Christian Hent <hent.dev@googlemail.com>
 * @copyright  Copyright (C) 2017 Christian Hent
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://github.com/christianhent/plg_content_zatracks
 * 
 * @version    2.2.0
 * 
 */
defined('JPATH_BASE') or die;
// multipart/form-data
$doc = JFactory::getDocument();
$doc->addScriptDeclaration( '
	jQuery(document).ready(function(){
		jQuery( "form#item-form" ).attr( "enctype", "multipart/form-data" ).attr( "encoding", "multipart/form-data" );
	});
');

$app       = JFactory::getApplication();
$form      = $displayData->getForm();
$fieldSets = $form->getFieldsets();

$trackFieldSet = $form->getFieldsets('track');

if(!JFactory::getApplication()->getUserState("strava_token"))
{
	unset($trackFieldSet['strava']);
}

$fieldSets = array_merge($fieldSets, $trackFieldSet);

if (empty($fieldSets))
{
	return;
}

$ignoreFieldsets = $displayData->get('ignore_fieldsets') ?: array();
$ignoreFields    = $displayData->get('ignore_fields') ?: array();
$extraFields     = $displayData->get('extra_fields') ?: array();
$tabName         = $displayData->get('tab_name') ?: 'myTab';

if (!empty($displayData->hiddenFieldsets))
{
	// These are required to preserve data on save when fields are not displayed.
	$hiddenFieldsets = $displayData->hiddenFieldsets ?: array();
}

if (!empty($displayData->configFieldsets))
{
	// These are required to configure showing and hiding fields in the editor.
	$configFieldsets = $displayData->configFieldsets ?: array();
}

if ($displayData->get('show_options', 1))
{
	foreach ($fieldSets as $name => $fieldSet)
	{
		// Ensure any fieldsets we don't want to show are skipped (including repeating formfield fieldsets)
		if ((isset($fieldSet->repeat) && $fieldSet->repeat == true)
			|| in_array($name, $ignoreFieldsets)
			|| (!empty($configFieldsets) && in_array($name, $configFieldsets))
			|| (!empty($hiddenFieldsets) && in_array($name, $hiddenFieldsets))
		)
		{
			continue;
		}

		if (!empty($fieldSet->label))
		{
			$label = JText::_($fieldSet->label);
		}
		else
		{
			$label = strtoupper('JGLOBAL_FIELDSET_' . $name);
			if (JText::_($label) === $label)
			{
				$label = strtoupper($app->input->get('option') . '_' . $name . '_FIELDSET_LABEL');
			}
			$label = JText::_($label);
		}

		echo JHtml::_('bootstrap.addTab', $tabName, 'attrib-' . $name, $label);

		if (isset($fieldSet->description) && trim($fieldSet->description))
		{
			echo '<p class="alert alert-info">' . $this->escape(JText::_($fieldSet->description)) . '</p>';
		}

		$displayData->fieldset = $name;
		echo JLayoutHelper::render('joomla.edit.fieldset', $displayData);

		echo JHtml::_('bootstrap.endTab');
	}
}
else
{
	$html   = array();
	$html[] = '<div style="display:none;">';
	foreach ($fieldSets as $name => $fieldSet)
	{
		if (in_array($name, $ignoreFieldsets))
		{
			continue;
		}

		if (in_array($name, $hiddenFieldsets))
		{
			foreach ($form->getFieldset($name) as $field)
			{
				$html[] = $field->input;
			}
		}
	}
	$html[] = '</div>';

	echo implode('', $html);
}
