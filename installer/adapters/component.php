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

class RokInstallerComponent extends JInstallerComponent
{
    protected $installtype = 'install';
    protected $remove_admin_menu;

    const DEFAULT_REMOVE_ADMIN_MENU = 'false';

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

    protected function localPostInstall($extension, $coginfo)
    {
        $remove_admin_menu = ($coginfo['remove_admin_menu']) ? strtolower((string) $coginfo['remove_admin_menu']) : self::DEFAULT_REMOVE_ADMIN_MENU;

        if ($remove_admin_menu == 'true')
        {
            if (version_compare(JVERSION, '3.4', '<'))
            {
                // Joomla 2.5 & 3.3 support.
                $this->_removeAdminMenus($extension);
            }
            else
            {
                $this->_removeAdminMenus($extension->extensionid);
            }
        }
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

    protected function updateExtension($extension)
    {
        if ($extension)
        {
            $extension->access = $this->access;
            $extension->enabled = $this->enabled;
            $extension->protected = $this->protected;
            $extension->client_id = $this->client;
            $extension->ordering = $this->ordering;
            $extension->params = $this->params;
            $extension->store();
        }
    }

    public function install()
    {
        $result = parent::install();

        if ($result !== false)
        {
            $this->postInstall($result);
        }

        return $result;
    }

    public function postInstall($extensionId)
    {
        $coginfo = $this->parent->getCogInfo();

        $this->setAccess(($coginfo['access']) ? (int)$coginfo['access'] : self::DEFAULT_ACCESS);
        $this->setEnabled(($coginfo['enabled']) ? (string)$coginfo['enabled'] : self::DEFAULT_ENABLED);
        $this->setProtected(($coginfo['protected']) ? (string)$coginfo['protected'] : self::DEFAULT_PROTECTED);
        $this->setClient(($coginfo['client']) ? (string)$coginfo['client'] : self::DEFAULT_CLIENT);
        $this->setParams(($coginfo->params) ? (string)$coginfo->params : self::DEFAULT_PARAMS);
        $this->setOrdering(($coginfo['ordering']) ? (int)$coginfo['ordering'] : self::DEFAULT_ORDERING);

        $extension = $this->loadExtension($extensionId);

        // update the extension info
        $this->updateExtension($extension);

        $this->localPostInstall($extension, $coginfo);
    }

    protected function loadExtension($extensionId)
    {
        $row = JTable::getInstance('extension');
        $row->load($extensionId);

        if (!$row->extension_id) {
            throw new RuntimeException("Internal error in Joomla installer: extension {$extensionId} not found!");
        }

        return $row;
    }
}
