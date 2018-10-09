<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2018 Zinit Solutions GmbH
 * Website: http://www.treopim.com
 *
 * TreoPIM as well as EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TreoPIM as well as EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word
 * and "TreoPIM" word.
 */

declare(strict_types=1);

namespace Treo\Core\Portal;

use Espo\Entities\Portal;
use Espo\Core\Utils\Language;
use Espo\Core\Portal\Utils\Language as PortalLanguage;
use Espo\Core\Portal\Utils\Layout;
use Espo\Core\Portal\Utils\ThemeManager;

/**
 * Class Container
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Container extends \Treo\Core\Container
{
    /**
     * @param Portal $portal
     */
    public function setPortal(Portal $portal)
    {
        $this->set('portal', $portal);

        $data = array();
        foreach ($this->get('portal')->getSettingsAttributeList() as $attribute) {
            $data[$attribute] = $this->get('portal')->get($attribute);
        }
        if (empty($data['language'])) {
            unset($data['language']);
        }
        if (empty($data['theme'])) {
            unset($data['theme']);
        }
        if (empty($data['timeZone'])) {
            unset($data['timeZone']);
        }
        if (empty($data['dateFormat'])) {
            unset($data['dateFormat']);
        }
        if (empty($data['timeFormat'])) {
            unset($data['timeFormat']);
        }
        if (isset($data['weekStart']) && $data['weekStart'] === -1) {
            unset($data['weekStart']);
        }
        if (array_key_exists('weekStart', $data) && is_null($data['weekStart'])) {
            unset($data['weekStart']);
        }
        if (empty($data['defaultCurrency'])) {
            unset($data['defaultCurrency']);
        }

        foreach ($data as $attribute => $value) {
            $this->get('config')->set($attribute, $value, true);
        }
    }

    /**
     * @return mixed
     */
    protected function loadAclManager()
    {
        $className = $this->getServiceClassName('aclManager', '\\Espo\\Core\\Portal\\AclManager');
        $mainClassName = $this->getServiceMainClassName('aclManager', '\\Espo\\Core\\AclManager');

        $obj = new $className(
            $this->get('container')
        );
        $objMain = new $mainClassName(
            $this->get('container')
        );
        $obj->setMainManager($objMain);

        return $obj;
    }

    /**
     * @return mixed
     */
    protected function loadAcl()
    {
        $className = $this->getServiceClassName('acl', '\\Espo\\Core\\Portal\\Acl');
        return new $className(
            $this->get('aclManager'),
            $this->get('user')
        );
    }

    /**
     * @return ThemeManager
     */
    protected function loadThemeManager()
    {
        return new ThemeManager(
            $this->get('config'),
            $this->get('metadata'),
            $this->get('portal')
        );
    }

    /**
     * @return Layout
     */
    protected function loadLayout()
    {
        return new Layout(
            $this->get('fileManager'),
            $this->get('metadata'),
            $this->get('user')
        );
    }

    /**
     * @return PortalLanguage
     */
    protected function loadLanguage()
    {
        $language = new PortalLanguage(
            Language::detectLanguage($this->get('config'), $this->get('preferences')),
            $this->get('fileManager'),
            $this->get('metadata'),
            $this->get('useCache')
        );
        $language->setPortal($this->get('portal'));

        return $language;
    }

    /**
     * @param string $name
     * @param string $default
     *
     * @return string
     */
    protected function getServiceClassName($name, $default)
    {
        $metadata = $this->get('metadata');
        $className = $metadata->get('app.serviceContainerPortal.classNames.' . $name, $default);

        return $className;
    }

    /**
     * @param string $name
     * @param string $default
     *
     * @return string
     */
    protected function getServiceMainClassName($name, $default)
    {
        $metadata = $this->get('metadata');
        $className = $metadata->get('app.serviceContainer.classNames.' . $name, $default);

        return $className;
    }
}
