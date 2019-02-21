<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2019 TreoLabs GmbH
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

namespace Treo\Core;

use Espo\Core\Exceptions\Error;
use Espo\Orm\EntityManager;
use Treo\Services\QueueManagerServiceInterface;

/**
 * Class QueueManager
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class QueueManager
{
    use \Treo\Traits\ContainerTrait;

    /**
     * @return bool
     */
    public function run(): bool
    {
        return true;
    }

    /**
     * @param string $name
     * @param string $serviceName
     * @param array  $data
     *
     * @return bool
     * @throws Error
     */
    public function push(string $name, string $serviceName, array $data = []): bool
    {
        // validation
        if (!$this->isService($serviceName)) {
            return false;
        }

        return $this->createQueueItem($name, $serviceName, $data);
    }

    /**
     * @param string $name
     * @param string $serviceName
     * @param array  $data
     *
     * @return bool
     * @throws Error
     */
    protected function createQueueItem(string $name, string $serviceName, array $data = []): bool
    {
        $item = $this->getEntityManager()->getEntity('QueueItem');
        $item->set(
            [
                'name'        => $name,
                'serviceName' => $serviceName,
                'data'        => $data,
                'sortOrder'   => $this->getNextSortOrder()
            ]
        );

        $this->getEntityManager()->saveEntity($item);

        return true;
    }

    /**
     * @return int
     */
    protected function getNextSortOrder(): int
    {
        // prepare result
        $result = 0;

        $data = $this
            ->getEntityManager()
            ->getRepository('QueueItem')
            ->select(['sortOrder'])
            ->find()
            ->toArray();

        if (!empty($data)) {
            $result = (max(array_column($data, 'sortOrder'))) + 1;
        }

        return $result;
    }

    /**
     * @param string $serviceName
     *
     * @return bool
     * @throws Error
     */
    protected function isService(string $serviceName)
    {
        if (!$this->getServiceFactory()->checkExists($serviceName)) {
            throw new Error("No such service '$serviceName'");
        }

        if (!$this->getServiceFactory()->create($serviceName) instanceof QueueManagerServiceInterface) {
            throw new Error("Service '$serviceName' should be instance of QueueManagerServiceInterface");
        }

        return true;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager(): EntityManager
    {
        return $this->getContainer()->get('entityManager');
    }

    /**
     * @return ServiceFactory
     */
    protected function getServiceFactory(): ServiceFactory
    {
        return $this->getContainer()->get('serviceFactory');
    }
}
