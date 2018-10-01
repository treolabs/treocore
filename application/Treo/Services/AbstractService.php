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

namespace Treo\Services;

use Espo\Entities\User;
use Espo\Core\Utils\Config;
use Espo\Orm\EntityManager;

/**
 * AbstractService class
 *
 * @author r.ratsun@zinitsolutions.com
 */
abstract class AbstractService
{
    use \Treo\Traits\ContainerTrait;

    /**
     * Reload dependency
     *
     * @param string $name
     */
    protected function reloadDependency($name)
    {
        $this->getContainer()->reload($name);
    }

    /**
     * Rebuild
     */
    protected function rebuild(): void
    {
        $this->reloadDependency('entityManager');
        $this->getContainer()->get('dataManager')->rebuild();
    }

    /**
     * Get EntityManager
     *
     * @return EntityManager
     */
    protected function getEntityManager(): EntityManager
    {
        return $this->getContainer()->get('entityManager');
    }

    /**
     * Get Config
     *
     * @return Config
     */
    protected function getConfig(): Config
    {
        return $this->getContainer()->get('config');
    }

    /**
     * Get User
     *
     * @return User
     */
    protected function getUser(): User
    {
        return $this->getContainer()->get('user');
    }

    /**
     * Triggered event
     *
     * @param string $target
     * @param string $action
     * @param array  $data
     *
     * @return array
     */
    protected function triggered(string $target, string $action, array $data = []): array
    {
        return $this
            ->getContainer()
            ->get('eventManager')
            ->triggered($target, $action, $data);
    }

    /**
     * Translate
     *
     * @param string     $label
     * @param string     $category
     * @param string     $scope
     * @param array|null $requiredOptions
     *
     * @return string
     */
    protected function translate(
        string $label,
        string $category = 'labels',
        string $scope = 'Global',
        array $requiredOptions = null
    ): string {
        return $this
            ->getContainer()
            ->get('language')
            ->translate($label, $category, $scope, $requiredOptions);
    }
}
