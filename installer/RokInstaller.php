<?php
/**
 * @package   Installer Bundle Framework - RocketTheme
 * @version   1.4 February 21, 2015
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
 *
 * Installer uses the Joomla Framework (http://www.joomla.org), a GNU/GPLv2 content management system
 */

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

if (version_compare(JVERSION, '3.0', '<'))
{
    // Joomla 2.5 compatibility.
    jimport('joomla.installer.installer');
    JLoader::discover('JInstaller', JPATH_LIBRARIES . '/joomla/installer/adapters');
}

class RokInstaller extends JInstaller
{
    const EXCEPTION_NO_REPLACE = 'noreplace';

    protected $no_overwrite = array();
    protected $backup_dir;
    protected $cogInfo;
    protected $installtype;

    /**
     * Constructor
     *
     * @param null $basepath        Not used - needed for Joomla 3.4 compatibility.
     * @param null $classprefix     Not used - needed for Joomla 3.4 compatibility.
     * @param null $adapterfolder   Not used - needed for Joomla 3.4 compatibility.
     */
    public function __construct($basepath = null, $classprefix = null, $adapterfolder = null)
    {
        parent::__construct();

        $this->_basepath = dirname(__FILE__);
        $this->_classprefix = 'RokInstaller';
        $this->_adapterfolder = 'adapters';
    }

    /**
     * Returns a reference to the global Installer object, only creating it
     * if it does not already exist.
     *
     * @param null $basepath        Not used - needed for Joomla 3.4 compatibility.
     * @param null $classprefix     Not used - needed for Joomla 3.4 compatibility.
     * @param null $adapterfolder   Not used - needed for Joomla 3.4 compatibility.
     *
     * @return RokInstaller An installer object
     */
    public static function getInstance($basepath = null, $classprefix = null, $adapterfolder = null)
    {
        static $instance;

        if (!isset($instance))
        {
            $instance = new RokInstaller;
        }
        return $instance;
    }

    public function install($path = null)
    {
        $result = parent::install($path);

        $type = (string) $this->manifest->attributes()->type;

        $this->installtype = $this->_adapters[$type]->getInstallType();

        return $result;
    }

    public function getInstallType()
    {
        return $this->installtype;
    }

    /**
     * Method to parse through a files element of the installation manifest and take appropriate
     * action.
     *
     * @access    public
     *
     * @param    object     $element     The xml node to process
     * @param    int        $cid         Application ID of application to install to
     *
     * @return    boolean    True on success
     * @since     1.5
     */
    protected function prepExceptions($element, $cid = 0)
    {
        $config           = JFactory::getConfig();

        $this->backup_dir = $config->get('tmp_path') . '/' . uniqid('backup_');

        if (!JFolder::create($this->backup_dir))
        {
            JError::raiseWarning(1, 'JInstaller::install: ' . JText::_('Failed to create directory') . ' "' . $this->backup_dir . '"');

            return false;
        }

        // Get the client info
        jimport('joomla.application.helper');
        $client = JApplicationHelper::getClientInfo($cid);

        if (!is_a($element, 'JSimpleXMLElement') || !count($element->children()))
        {
            // Either the tag does not exist or has no children therefore we return zero files processed.
            return 0;
        }

        // Get the array of file nodes to process
        $files = $element->children();

        if (count($files) == 0)
        {
            // No files to process
            return 0;
        }

        /*
         * Here we set the folder we are going to remove the files from.
         */
        if ($client)
        {
            $pathname    = 'extension_' . $client->name;
            $destination = $this->getPath($pathname);
        }
        else
        {
            $pathname    = 'extension_root';
            $destination = $this->getPath($pathname);
        }

        // Process each file in the $files array (children of $tagName).
        /** @var SimpleXMLElement $file */
        foreach ($files as $file)
        {
            $exception_type = $file->attributes('type');
            $current_file   = $destination . '/' . $file->data();

            if ($exception_type == self::EXCEPTION_NO_REPLACE && file_exists($current_file))
            {
                $type = ($file->name() == 'folder') ? 'folder' : 'file';

                $backuppath['src']  = $current_file;
                $backuppath['dest'] = $this->backup_dir . '/' . $file->data();
                $backuppath['type'] = $type;

                $replacepath['src']  = $backuppath['dest'];
                $replacepath['dest'] = $backuppath['src'];
                $replacepath['type'] = $type;

                $this->no_overwrite[] = $replacepath;

                if (!$this->copyFiles(array($backuppath)))
                {
                    JError::raiseWarning(1, 'JInstaller::install: ' . JText::_('Failed to copy backup to ') . ' "' . $backuppath['dest'] . '"');

                    return false;
                }
            }
        }

        return true;
    }

    public function finishExceptions()
    {
        if (($this->upgrade && !empty($this->no_overwrite)) || !$this->upgrade)
        {
            foreach ($this->no_overwrite as $restore)
            {
                if (JPath::canChmod($restore['dest']))
                {
                    JPath::setPermissions($restore['dest']);
                }
            }

            if ($this->copyFiles($this->no_overwrite))
            {
                JFolder::delete($this->backup_dir);
            }
        }
    }

    public function copyFiles($files, $overwrite = null)
    {
        // To allow for manual override on the overwriting flag, we check to see if
        // the $overwrite flag was set and is a boolean value. If not, use the object
        // allowOverwrite flag.
        if (is_null($overwrite) || !is_bool($overwrite))
        {

            if (version_compare(JVERSION, '3.0', '<'))
            {
                // Joomla 2.5 compatibility.
                $overwrite = $this->_overwrite;
            }
            else
            {
                $overwrite = $this->overwrite;
            }
        }

        $ftp = JClientHelper::getCredentials('ftp');

        if (!$ftp['enabled'] && $overwrite && is_array($files))
        {
            foreach ($files as $file)
            {
                $filedest = JPath::clean($file['dest']);
                $filetype = array_key_exists('type', $file) ? $file['type'] : 'file';

                switch ($filetype)
                {
                    case 'file':
                        if (JFile::exists($filedest) && JPath::isOwner($filedest))
                        {
                            JPath::setPermissions($filedest);
                        }

                        break;

                    case 'folder':
                        if (JFolder::exists($filedest) && JPath::isOwner($filedest))
                        {
                            JPath::setPermissions($filedest);
                        }

                        break;
                }
            }
        }

        return parent::copyFiles($files, $overwrite);
    }

    public function setCogInfo($cogInfo)
    {
        $this->cogInfo = $cogInfo;
    }

    public function getCogInfo()
    {
        return $this->cogInfo;
    }
}
