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

class RokInstallerLibrary extends JInstallerLibrary
{
    protected $installtype = 'install';

    public function update()
    {
        $this->installtype = 'update';

        return parent::update();
    }

    public function getInstallType()
    {
        if (version_compare(JVERSION, '3.4', '<'))
        {
            return $this->installtype;
        }
        else
        {
            return $this->route;
        }
    }

    protected function localPostInstallRT($extension, $coginfo)
    {
    }

    // Move this code to RokInstallerAdapterTrait (keep identical in all adapters!)

    protected $access;
    protected $enabled;
    protected $client;
    protected $ordering;
    protected $protected;
    protected $params;

    const DEFAULT_ACCESS = 1;
    const DEFAULT_ENABLED = 'true';
    const DEFAULT_PROTECTED = 'false';
    const DEFAULT_CLIENT = 'site';
    const DEFAULT_ORDERING = 0;
    const DEFAULT_PARAMS = null;

    public function setAccess($access)
    {
        $this->access = $access;
    }

    public function getAccess()
    {
        return $this->access;
    }

    public function setClient($client)
    {
        switch (strtolower($client))
        {
            case 'site':
                $client = 0;
                break;
            case 'administrator':
                $client = 1;
                break;
            default:
                $client = (int) $client;
                break;
        }
        $this->client = $client;
    }

    public function getClient()
    {
        return $this->client;
    }

    public function setEnabled($enabled)
    {
        switch (strtolower($enabled))
        {
            case 'true':
                $enabled = 1;
                break;
            case 'false':
                $enabled = 0;
                break;
            default:
                $enabled = (int) $enabled;
                break;
        }
        $this->enabled = $enabled;
    }

    public function getEnabled()
    {
        return $this->enabled;
    }

    public function setOrdering($ordering)
    {
        $this->ordering = $ordering;
    }

    public function getOrdering()
    {
        return $this->ordering;
    }

    public function setProtected($protected)
    {
        switch (strtolower($protected))
        {
            case 'true':
                $protected = 1;
                break;
            case 'false':
                $protected = 0;
                break;
            default:
                $protected = (int) $protected;
                break;
        }
        $this->protected = $protected;
    }

    public function getProtected()
    {
        return $this->protected;
    }

    public function setParams($params)
    {
        $this->params = $params;
    }

    public function getParams()
    {
        return $this->params;
    }

    protected function updateExtensionRT($extension)
    {
        $extension->access = $this->access;
        $extension->enabled = $this->enabled;
        $extension->protected = $this->protected;
        $extension->client_id = $this->client;
        $extension->ordering = $this->ordering;
        $extension->params = $this->params;

        if (!$extension->store())
        {
            // Install failed, roll back changes
            throw new RuntimeException(
                JText::sprintf(
                    'JLIB_INSTALLER_ABORT_LIB_INSTALL_ROLLBACK',
                    $this->extension->getError()
                )
            );
        }
    }
/*
    public function install()
    {
        $extension_id = parent::install();

        if ($extension_id !== false)
        {
            $this->postInstallRT($extension_id);
        }

        return $extension_id;
    }
*/
    public function postInstallRT($extensionId)
    {
        $coginfo = $this->parent->getCogInfo();

        $this->setAccess(($coginfo['access']) ? (int)$coginfo['access'] : self::DEFAULT_ACCESS);
        $this->setEnabled(($coginfo['enabled']) ? (string)$coginfo['enabled'] : self::DEFAULT_ENABLED);
        $this->setProtected(($coginfo['protected']) ? (string)$coginfo['protected'] : self::DEFAULT_PROTECTED);
        $this->setClient(($coginfo['client']) ? (string)$coginfo['client'] : self::DEFAULT_CLIENT);
        $this->setParams(($coginfo->params) ? (string)$coginfo->params : self::DEFAULT_PARAMS);
        $this->setOrdering(($coginfo['ordering']) ? (int)$coginfo['ordering'] : self::DEFAULT_ORDERING);

        $extension = $this->getExtensionRT($extensionId);

        // update the extension info
        $this->updateExtensionRT($extension);

        $this->localPostInstallRT($extension, $coginfo);
    }

    protected function getExtensionRT($extensionId)
    {
        if (version_compare(JVERSION, '3.4', '<')) {
            // Joomla 2.5 and 3.3
            $extension = JTable::getInstance('extension');
            $extension->load($extensionId);
        } else {
            // Joomla 3.4
            $extension = $this->extension;
        }

        if (!$extension->extension_id) {
            throw new RuntimeException("Internal error in Joomla installer: extension {$extensionId} not found!");
        }

        return $extension;
    }
}
