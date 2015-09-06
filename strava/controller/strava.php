<?php

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controllerform');
jimport('joomla.oauth2.client');

class ContentControllerStrava extends JControllerForm
{
    protected $options;
    protected $http;
    protected $input;
    
    function __construct($config, JRegistry $options = null, JHttp $http = null, JInput $input = null)
    {
        $this->options = isset($options) ? $options : new JRegistry;
        $this->http = isset($http) ? $http : new JHttp($this->options);
        $this->input = isset($input) ? $input : JFactory::getApplication()->input;
        
        parent::__construct();
    }

    public function auth()
    {
        // get track id to build the oauth redirecturi
        $trackId = $this->input->get('id','0', 'INT');
        
        // get strava plugin params
        $plg = JPluginHelper::getPlugin('content','strava');
        $plgParams = new JRegistry();

        $plgParams->loadString($plg->params);
        
        // set auth options
        $this->options->set('authurl', 'https://www.strava.com/oauth/authorize');
        $this->options->set('clientid', $plgParams->get('client_id'));
        $this->options->set('consumersecret', $plgParams->get('client_secret'));
        $this->options->set('redirecturi', JURI::current() . '?option=com_content&view=article&layout=edit&id='.$trackId);
        $this->options->set('sendheaders', true);

        $oauth = new JOAuth2Client($this->options);
        
        $oauth->authenticate();

        /*
         * after auth, STRAVA answers with a code/string
         * which must be as next exchanged to get a
         * requested token. this is done inside the strava plugin.
        */
    }
}