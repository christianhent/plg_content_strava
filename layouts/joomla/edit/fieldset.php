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

$app = JFactory::getApplication();
$form = $displayData->getForm();

$name = $displayData->get('fieldset');
$fieldSet = $form->getFieldset($name);

if (empty($fieldSet))
{
	return;
}

$ignoreFields = $displayData->get('ignore_fields') ? : array();
$extraFields = $displayData->get('extra_fields') ? : array();

if ($displayData->get('show_options', 1))
{
	if (isset($extraFields[$name]))
	{
		foreach ($extraFields[$name] as $f)
		{
			if (in_array($f, $ignoreFields))
			{
				continue;
			}
			if ($form->getField($f))
			{
				$fieldSet[] = $form->getField($f);
			}
		}
	}

	$html = array();

	foreach ($fieldSet as $field)
	{
		if ($field->getAttribute('name') == 'strava_athlete_activities')
		{
			if(JFactory::getApplication()->getUserState("strava_token"))
			{

			$document = JFactory::getDocument();
			$document->addScriptDeclaration("
				js = jQuery.noConflict();
				js(document).ready(function(){
					js( '#strava_athlete_activities' ).change(function() {
						var option = this.options[this.selectedIndex];
						var value = parseInt(js(option).val());
						js('input#jform_track_strava_activity_id').val(value);
					});
				});
			");
			
			$list_activities = $form->getData()->get('track')->strava_athlete_activities;

			$html[] = '<div class="control-group">';
			$html[] = '<div class="control-label">';
			$html[] = $field->label;
			$html[] = '</div>';
			$html[] = '<div class="controls">';
			
			$options = array('Select activity');
			
			foreach ($list_activities as $activity)
			{
				$options[] = JHTML::_('select.option', $activity['id'], $activity['name']);
	
			}

			$html[] = JHTML::_('select.genericlist', $options, 'strava_athlete_activities');
			$html[] = '</div>';
			$html[] = '</div>';

			}
		}
		else
		{
			$html[] = $field->renderField();
		}
	}

	echo implode('', $html);
}
else
{
	$html = array();
	$html[] = '<div style="display:none;">';
	foreach ($fieldSet as $field)
	{
		$html[] = $field->input;
	}
	$html[] = '</div>';

	echo implode('', $html);
}
