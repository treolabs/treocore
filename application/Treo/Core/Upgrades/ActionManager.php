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

declare(strict_types = 1);

namespace Treo\Core\Upgrades;

use Espo\Core\Upgrades\ActionManager as EspoActionManager;
use Espo\Core\Exceptions\Error;

/**
 * Class of ActionManager
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class ActionManager extends EspoActionManager
{
    /**
     * @var array
     */
    protected $objects;

    /**
     * Get object
     *
     * @return mixed
     * @throws Error
     */
    protected function getObject()
    {
        // prepare params
        $managerName = $this->getManagerName();
        $actionName  = $this->getAction();

        if (!isset($this->objects[$managerName][$actionName])) {
            $class = '\Treo\Core\Upgrades\Actions\\'.ucfirst($managerName).'\\'.ucfirst($actionName);

            if (!class_exists($class)) {
                $class = '\Espo\Core\Upgrades\Actions\\'.ucfirst($managerName).'\\'.ucfirst($actionName);
            }

            if (!class_exists($class)) {
                throw new Error('Could not find an action ['.ucfirst($actionName).'], class ['.$class.'].');
            }

            $this->objects[$managerName][$actionName] = new $class($this->getContainer(), $this);
        }

        return $this->objects[$managerName][$actionName];
    }
}
