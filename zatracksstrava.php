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

use Joomla\Utilities\ArrayHelper;

jimport('joomla.plugin.plugin');

class PlgContentZatracksstrava extends JPlugin
{
	public function __construct(&$subject, $config, JRegistry $options = null, JHttp $http = null, JInput $input = null)
	{
		parent::__construct($subject, $config);

		$this->loadLanguage();

		$this->app     = JFactory::getApplication();
		$this->options = isset($options) ? $options : new JRegistry;
		$this->http    = isset($http) ? $http : new JHttp($this->options);
		$this->input   = isset($input) ? $input : JFactory::getApplication()->input;
	}

	public function onContentPrepareForm($form, $data)
	{
		if (!$this->app->isAdmin())
		{
			return true;
		}

		if (!($form instanceof JForm))
		{
			$this->_subject->setError('JERROR_NOT_A_FORM');

			return false;
		}

		$name = $form->getName();

		if (!in_array($name, array('com_content.article')))
		{
			return true;
		}

		$zatracks           = JPluginHelper::getPlugin('content','zatracks');
		$zatracks_params    = new JRegistry();

		$zatracks_params->loadString($zatracks->params);

		$include_categories = $zatracks_params->get('include_categories');

		if (empty($include_categories))
		{
			return true;
		}

		if (empty($data))
		{
			$input = JFactory::getApplication()->input;
			$data  = (object) $input->post->get('jform', array(), 'array');
		}

		if (is_array($data))
		{
			$data = ArrayHelper::toObject($data);
		}

		if (empty($data->catid))
		{
			return true;
		}

		if (!in_array($data->catid, $include_categories))
		{
			return true;
		}

		if(!$this->app->getUserState("strava_token"))
		{
			JToolBarHelper::divider();
			JToolBarHelper::custom(
				'strava.auth',
				'power-cord.png',
				'power-cord_f2.png',
				'PLG_CONTENT_ZATRACKSSTRAVA_COM_CONTENT_TOOLBAR_STRAVAAUTH_BTN', 
				false
				);
		}
		else {

			JForm::addFormPath(__DIR__ . '/forms');
			$form->loadFile('strava');
		}

		#JForm::addFormPath(__DIR__ . '/forms');
		#$form->loadFile('strava');

		if (!empty($data->id))
		{

			if (!isset($data->track))
			{
				$data->track = new stdClass();
			}

			$data->track->strava_activity_id  = $this->_getStoredActivityId($data->id);

		}

		if($this->input->get('code'))
		{
			$token = $this->_exchangeToken();

			$this->app->setUserState("strava_token", $token);

			//$uri = JURI::current() . '?option=com_content&view=article&layout=edit&id='.$data->get('id');
			$uri = JURI::current() . '?option=com_content&view=article&layout=edit&id='.(int)$data->id;

			$this->app->redirect($uri, true );
		}

		if($this->app->getUserState("strava_token"))
		{	
			$activities = $this->_getActivitiesFromApi($this->app->getUserState("strava_token"));

			foreach ($activities as $activity) {
				$whitelist = array('id','name','start_date');
				$filtered_activities[] = array_intersect_key( $activity, array_flip( $whitelist ) );
			}

			$form->setValue('strava_athlete_activities', 'track', $filtered_activities);
		}

		JLayoutHelper::$defaultBasePath = __DIR__ . '/layouts';

		return true;
	}

	public function onContentAfterSave($context, $data, $isNew)
	{
		if (!$this->app->isAdmin())
		{
			return true;
		}

		if (!in_array($context, array('com_content.article')))
		{
			return true;
		}


		$zatracks = JPluginHelper::getPlugin('content','zatracks');
		$zatracks_params = new JRegistry();

		$zatracks_params->loadString($zatracks->params);

		$include_categories = $zatracks_params->get('include_categories');

		if (empty($include_categories))
		{
			return true;
		}

		if (!in_array($data->catid, $include_categories))
		{
			return true;
		}

		$input = JFactory::getApplication()->input;
		$formData  = (object) $input->post->get('jform', null, 'array');

		if (is_array($formData->track))
		{
			$formData->track = ArrayHelper::toObject($formData->track);
		}

		if (is_object($formData->track))
		{
			$stravaFormData = new stdClass();
			$stravaFormData->strava_activity_id = $formData->track->strava_activity_id;
		}
		else
		{

			return true;
		}

		$content_id = $data->id;

		if ($this->app->getUserState("strava_token") )
		{
			if ($formData->track->strava_activity_id != 0 && $formData->track->strava_activity_id != $this->_getStoredActivityId($data->id))
			{
				$activity = $this->_getActivityFromApi($this->app->getUserState("strava_token"), $formData->track->strava_activity_id);

				if(isset($activity))
				{
					$stravaFormData->name      = $activity['name'];
					$stravaFormData->custom    = $activity['description'];
					$stravaFormData->polyline  = $activity['map']['polyline'];
					$stravaFormData->polyline  = str_replace('\\', '\\\\', $stravaFormData->polyline);
					$stravaFormData->duration  = $activity['moving_time'];
					$stravaFormData->distance  = $activity['distance'] /  1000;
					$stravaFormData->starttime = $activity['start_date'];
					$stravaFormData->avs       = $activity['average_speed'] / 1000 * 60 * 60;
					#new
					$stravaFormData->min_elevation  = $activity['elev_low'];
					$stravaFormData->max_elevation  = $activity['elev_high'];
					$stravaFormData->elevation_gain = $activity['total_elevation_gain'];
					$stravaFormData->elevation_loss = $activity['elevation_loss'];

					$this->app->enqueueMessage(JText::_('PLG_CONTENT_ZATRACKSSTRAVA_ACTIVITY_IMPORT_SUCCESS_MESSAGE'), 'message');	
				}
			}	
		}

		$this->_saveTrack($content_id, $context, $stravaFormData);

		return true;
	}

	public function onAfterDispatch()
	{

		JLayoutHelper::$defaultBasePath = "";
	}

	protected function _getStoredActivityId($article_id)
	{
		$db    = JFactory::getDBO();
		$query = $db->getQuery(true)
		->select('strava_activity_id')
		->from('#__zatracks')
		->where('content_id=' . (int)$article_id);
		$db->setQuery($query);

		try
		{
			$stored_activity_id = $db->loadResult();
		}
		catch (RuntimeException $e)
		{
			JError::raiseWarning(500, $e->getMessage());

			return false;
		}

		return (int) $stored_activity_id;
	}

	protected function _exchangeToken()
	{
		// get strava plugin params
		$plg = JPluginHelper::getPlugin('content','zatracksstrava');
		$plgParams = new JRegistry();

		$plgParams->loadString($plg->params);

		// set token exchange options
		$this->options->set('token_url', 'https://www.strava.com/oauth/token');
		$this->options->set('client_id', $plgParams->get('client_id'));
		$this->options->set('client_secret', $plgParams->get('client_secret'));
		$this->options->set('code', $this->input->get('code'));

		try 
		{
			$response = $this->http->post($this->options->get('token_url'), $this->options->toArray());

			if ($response->code === 200)
			{
				$result = json_decode($response->body);

				$this->app->enqueueMessage(JText::_('PLG_CONTENT_ZATRACKSSTRAVA_TOKEN_EXCHANGE_SUCCESS_MESSAGE'), 'message');

				return $result->access_token;
			}
			else
			{
				$result = json_decode($response->body, true);

				$this->app->enqueueMessage(JText::_('PLG_CONTENT_ZATRACKSSTRAVA_API_ERROR'.$result['message']), 'error');
			}	

		}
		catch (Exception $e) 
		{
			$this->setError($e);
		}
	}

	protected function _getActivityFromApi($access_token, $id)
	{
		$options = new JRegistry;

		$http = new JHttp($options);

		$url = 'https://www.strava.com/api/v3/activities/'.$id;

		$headers = array('Authorization' => 'Bearer '. $access_token);

		try
		{
			$response = $http->get($url, $headers);

			if ($response->code === 200)
			{
				$data = json_decode($response->body, true);

				return $data;
			}
			else
			{
				$result = json_decode($response->body, true);

				$this->app->enqueueMessage(JText::_('PLG_CONTENT_ZATRACKSSTRAVA_API_ERROR').$result['message'], 'error');
			}	
		}
		catch (Exception $e)
		{	
			$this->setError($e);
		}
	}

	protected function _getActivitiesFromApi($access_token)
	{
		$options = new JRegistry;

		$http = new JHttp($options);

		$query = array('per_page' => 10);

		$url = 'https://www.strava.com/api/v3/athlete/activities?per_page=' . $query['per_page'];

		$headers = array('Authorization' => 'Bearer '. $access_token);

		try
		{
			$response = $http->get($url, $headers);

			if ($response->code === 200)
			{
				$data = json_decode($response->body, true);

				return $data;
			}
			else
			{
				$result = json_decode($response->body, true);

				$this->app->enqueueMessage(JText::_('PLG_CONTENT_ZATRACKSSTRAVA_API_ERROR').$result['message'], 'error');
			}	
		}
		catch (Exception $e)
		{	
			$this->setError($e);
		}
	}

	protected function _saveTrack($content_id, $context, $stravaFormData)
	{
		$db     = JFactory::getDbo();
		$query  = $db->getQuery(true);
		$query->select($db->quoteName('content_id'))
		->from($db->quoteName('#__zatracks'))
		->where($db->quoteName('content_id') . ' = ' . $content_id);
		$db->setQuery($query);
		$db->execute();
		$exists = (bool) $db->getNumRows();

		$data                     = new stdClass;
		$data->content_id         = $content_id;
		$data->context            = $context;
		$data->strava_activity_id = $stravaFormData->strava_activity_id;
		$data->name               = $stravaFormData->name;
		$data->custom             = $stravaFormData->custom;
		$data->polyline           = $stravaFormData->polyline;
		$data->starttime          = $stravaFormData->starttime;
		$data->duration           = $stravaFormData->duration;
		$data->distance           = $stravaFormData->distance;
		$data->avs                = $stravaFormData->avs;
		#new
		$data->min_elevation      = $stravaFormData->min_elevation;
		$data->max_elevation      = $stravaFormData->max_elevation;
		$data->elevation_gain     = $stravaFormData->elevation_gain;
		$data->elevation_loss     = $stravaFormData->elevation_loss;

		if ($exists)
		{
			$db->updateObject('#__zatracks', $data, 'content_id');
		}
		else
		{
			$db->insertObject('#__zatracks', $data);
		}
	}
}
