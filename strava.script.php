<?php

defined('_JEXEC') or die;

class plgContentStravaInstallerScript
{
    
	public function preflight($type)
    {
        if ($type != "discover_install" && $type != "install")
        {
            return true;
        }

        $version = new JVersion;

        if (version_compare($version->getShortVersion(), "3", 'lt'))
        {
            Jerror::raiseWarning(null, JText::_('PLG_CONTENT_STRAVA_INSTALL_NOJ2_ERROR'));

            return false;
        }

        return true;
    }

    public function install($parent)
    {
        jimport('joomla.filesystem.folder');
        jimport('joomla.filesystem.file');

        $src  = $parent->getParent()->getPath('source');
        $from = $src.'/strava/controller/strava.php';
        $to   = JPATH_ADMINISTRATOR . '/components/com_content/controllers/strava.php';

        if (JFile::exists($from))
        {
            JFile::move($from,$to);
        }

        JFactory::getApplication()->enqueueMessage(JText::_('PLG_CONTENT_STRAVA_INSTALL_NOTICE'), 'notice');
    }

    public function uninstall($parent)
    {
        jimport('joomla.filesystem.folder');
        jimport('joomla.filesystem.file');

        $file = JPATH_ADMINISTRATOR . '/components/com_content/controllers/strava.php';

        if (JFile::exists($file))
        {
            JFile::delete($file);
        }       
    }
}