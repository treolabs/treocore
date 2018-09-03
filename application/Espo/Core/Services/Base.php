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

namespace Espo\Core\Services;

use Espo\Core\Container;
use Espo\Core\Exceptions\Error;
use Espo\Core\Interfaces\Injectable;
use Espo\Core\Utils\Config;
use Espo\Entities\User;
use Espo\Orm\EntityManager;
use Espo\Modules\TreoCore\Traits\EventTriggeredTrait;

/**
 * Abstract service Base
 *
 * @author r.ratsun@zinitsolutions.com
 * @todo   treoinject
 */
abstract class Base implements Injectable
{
    use EventTriggeredTrait;

    /**
     * @var array
     */
    protected $dependencies
        = [
            'config',
            'entityManager',
            'user',
            'dataManager',
            'eventManager'
        ];

    /**
     * @var Container
     */
    private $container = null;

    /**
     * Construct
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Set container
     *
     * @param Container $container
     *
     * @return Base
     */
    public function setContainer(Container $container)
    {
        if (is_null($this->container)) {
            $this->container = $container;
        }

        return $this;
    }

    /**
     * Get dependency list
     *
     * @return array
     */
    public function getDependencyList()
    {
        return $this->dependencies;
    }

    /**
     * Init
     */
    protected function init()
    {
    }

    /**
     * Get injection
     *
     * @param string $name
     *
     * @return mixed
     */
    protected function getInjection($name)
    {
        if (!in_array($name, $this->getDependencyList())) {
            throw new Error('No such dependency');
        }

        return $this->container->get($name);
    }

    /**
     * Add dependency
     *
     * @param string $name
     */
    protected function addDependency($name)
    {
        $this->dependencies[] = $name;
    }

    /**
     * Add dependency list
     *
     * @param array $list
     */
    protected function addDependencyList(array $list)
    {
        foreach ($list as $item) {
            $this->addDependency($item);
        }
    }

    /**
     * Reload dependency
     *
     * @param string $name
     */
    protected function reloadDependency($name)
    {
        $this->container->reload($name);
    }

    /**
     * Rebuild
     *
     * @throws Error
     */
    protected function rebuild(): void
    {
        $this->reloadDependency('entityManager');
        $this->getInjection('dataManager')->rebuild();
    }

    /**
     * Get EntityManager
     *
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getInjection('entityManager');
    }

    /**
     * Get Config
     *
     * @return Config
     */
    protected function getConfig()
    {
        return $this->getInjection('config');
    }

    /**
     * Get User
     *
     * @return User
     */
    protected function getUser()
    {
        return $this->getInjection('user');
    }
}
