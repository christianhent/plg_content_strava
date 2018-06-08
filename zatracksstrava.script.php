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
defined('_JEXEC') or die;

class plgContentZatracksstravaInstallerScript
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
            Jerror::raiseWarning(null, JText::_('PLG_CONTENT_ZATRACKSSTRAVA_INSTALL_NOJ2_ERROR'));

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
        #j4 test
        $to   = JPATH_ADMINISTRATOR . '/components/com_content/Controller/strava.php';


        if (JFile::exists($from))
        {
            JFile::move($from,$to);
        }

        JFactory::getApplication()->enqueueMessage(JText::_('PLG_CONTENT_ZATRACKSSTRAVA_INSTALL_NOTICE'), 'notice');
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