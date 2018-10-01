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

namespace Espo\Modules\TreoCore\Listeners;

use Espo\Core\Container;
use Espo\Core\Services\Base as BaseService;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Language;
use Treo\Services\AbstractService;
use Treo\Core\Utils\Config;

/**
 * AbstractListener class
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
abstract class AbstractListener
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var array
     */
    protected $services = [];

    /**
     * Set container
     *
     * @param Container $container
     *
     * @return AbstractListener
     */
    public function setContainer(Container $container): AbstractListener
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Common listening of all actions
     *
     * @param string $action
     * @param array  $data
     *
     * @return array
     */
    public function common(string $action, array $data): array
    {
        return $data;
    }

    /**
     * Get container
     *
     * @return Container
     */
    protected function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Get service
     *
     * @param string $name
     *
     * @return BaseService|AbstractService
     */
    protected function getService(string $name)
    {
        if (!isset($this->services[$name])) {
            $this->services[$name] = $this
                ->getContainer()
                ->get('serviceFactory')
                ->create($name);
        }

        return $this->services[$name];
    }

    /**
     * Get entity manager
     *
     * @return EntityManager
     */
    protected function getEntityManager(): EntityManager
    {
        return $this->getContainer()->get('entityManager');
    }

    /**
     * Get config
     *
     * @return Config
     */
    protected function getConfig(): Config
    {
        return $this->getContainer()->get('config');
    }

    /**
     * Get language
     *
     * @return Language
     */
    protected function getLanguage(): Language
    {
        return $this->getContainer()->get('language');
    }
}
