<?php
/**
 * This file is part of EspoCRM and/or TreoCore.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoCore is EspoCRM-based Open Source application.
 * Copyright (C) 2017-2019 TreoLabs GmbH
 * Website: https://treolabs.com
 *
 * TreoCore as well as EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TreoCore as well as EspoCRM is distributed in the hope that it will be useful,
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
 * and "TreoCore" word.
 */

declare(strict_types=1);

namespace Treo\Core\Portal;

use Espo\Entities\Portal;
use Espo\Core\AclManager;
use Espo\Core\Portal\Acl as PortalAcl;
use Espo\Core\Portal\AclManager as PortalAclManager;
use Espo\Core\Portal\Utils\ThemeManager;
use Treo\Core\Container as Base;

/**
 * Class Container
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Container extends Base
{
    /**
     * Set portal
     *
     * @param Portal $portal
     *
     * @return Container
     */
    public function setPortal(Portal $portal): Container
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

        return $this;
    }

    /**
     * @return mixed
     */
    protected function loadAclManager()
    {
        // get metadata
        $metadata = $this->get('metadata');

        // prepare class names
        $className = $metadata->get('app.serviceContainerPortal.classNames.aclManager', PortalAclManager::class);
        $mainClassName = $metadata->get('app.serviceContainer.classNames.aclManager', AclManager::class);

        $obj = new $className($this);
        $obj->setMainManager(new $mainClassName($this));

        return $obj;
    }

    /**
     * @return mixed
     */
    protected function loadAcl()
    {
        // prepare class name
        $className = $this
            ->get('metadata')
            ->get('app.serviceContainerPortal.classNames.acl', PortalAcl::class);

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
}
