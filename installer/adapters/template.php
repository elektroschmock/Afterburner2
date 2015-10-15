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

class RokInstallerTemplate extends JInstallerTemplate
{
    protected $master_id = 0;

    public function localPostInstall($extension, $coginfo)
    {
        if ($this->getInstallType() != 'update')
        {
            if (count($coginfo->style) > 0)
            {
                $this->removeStyles($extension->element);
            }

            foreach ($coginfo->style as $styleinfo)
            {
                $this->addStyle($extension->element, $styleinfo);
            }
        }
    }

    /**
     * @param $template_name
     */
    protected function removeStyles($template_name)
    {
        $db = $this->parent->getDbo();
        $query = 'DELETE FROM #__template_styles' . ' WHERE template = ' . $db->quote($template_name);
        $db->setQuery($query);
        $db->execute();
    }

    /**
     * @param $templateName
     * @param $styleInfo
     */
    protected function addStyle($templateName, &$styleInfo)
    {
        $params         = false;
        $this_is_master = false;
        $db             = $this->parent->getDbo();

        if (!empty($styleInfo['paramsfile']))
        {
            $paramfile = $this->parent->getPath('source') . '/' . (string) $styleInfo['paramsfile'];

            if (file_exists($paramfile))
            {
                $params = $this->getParamsFromFile($paramfile);
            }
        }
        elseif ($styleInfo->params)
        {
            $params = json_decode((string) $styleInfo->params);
        }


        if ($params && $this->master_id != 0)
        {
            $params->master = $this->master_id;
        }
        else
        {
            $params->master = 'true';
            $this_is_master = true;
        }

        if ($styleInfo['default'])
        {
            $default = (strtolower((string) $styleInfo['default']) == 'true') ? 1 : 0;
        }
        else
        {
            $default = 0;
        }

        if ($default)
        {
            // Reset the home fields for the client_id.
            $db->setQuery('UPDATE #__template_styles' . ' SET home = ' . $db->quote('0') . ' WHERE client_id = ' . (int) $this->client . ' AND home = ' . $db->quote('1'));

            $db->execute();
        }

        //insert record in #__template_styles
        $query = $db->getQuery(true)
            ->clear()
            ->insert('#__template_styles')
            ->set('template=' . $db->quote($templateName))
            ->set('client_id=' . $this->client)
            ->set('home=' . $db->quote($default))
            ->set('title=' . $db->quote($styleInfo['name']));

        if ($params)
        {
            $query->set('params=' . $db->quote(json_encode($params)));
        }

        $db->setQuery($query);
        $db->execute();

        if ($this_is_master)
        {
            $this->master_id = $db->insertid();
        }

        // Clean the cache.
        $cache = JFactory::getCache();
        $cache->clean('com_templates');
        $cache->clean('_system');
    }

    /**
     * @param $filepath
     *
     * @return array|bool|stdClass
     */
    public function getParamsFromFile($filepath)
    {
        //   xpath for names //form//field|//form//fields[@default]|//form//fields[@value]
        //   xpath for parents  ancestor::fields[@name][not(@ignore-group)]/@name|ancestor::set[@name]/@name
        $xml = JFactory::getXML($filepath);

        $params   = $xml->xpath('//form//field|//form//fields[@default]|//form//fields[@value]');
        $defaults = array();

        foreach ($params as $param)
        {
            $attrs    = $param->xpath('ancestor::fields[@name][not(@ignore-group)]/@name|ancestor::set[@name]/@name');
            $groups   = array_map('strval', $attrs ? $attrs : array());
            $groups[] = (string)$param['name'];
            array_walk($groups, array($this, '_array_surround'));
            $def_array_eval = '$defaults' . implode('', $groups) . ' = (string)$param[\'default\'];';

            if ($param['default'])
            {
                @eval($def_array_eval);
            }
        }

        $defaults = $this->arrayToObject($defaults);

        return $defaults;
    }

    /**
     * @param $item
     */
    protected function _array_surround(&$item)
    {
        $item = '[\'' . $item . '\']';
    }

    /**
     * @param $array
     *
     * @return bool|stdClass
     */
    protected function arrayToObject($array)
    {
        if (!is_array($array))
        {
            return $array;
        }

        $object = new stdClass();

        if (is_array($array) && count($array) > 0)
        {
            foreach ($array as $name=> $value)
            {
                $name = trim($name);

                if (!empty($name))
                {
                    $object->$name = $this->arrayToObject($value);
                }
            }

            return $object;
        }
        else
        {
            return false;
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

    public function getInstallType()
    {
        return $this->route;
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
