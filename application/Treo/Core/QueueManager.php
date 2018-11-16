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

namespace Treo\Core;

use Espo\Core\Exceptions\Error;
use Espo\Core\ORM\Entity;
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
        if (!$this->isRunning() && !empty($item = $this->getItemToRun())) {
            // create cron job
            $this->createCronJob($item);
        }

        // update statuses
        $this->updateStatuses();

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
        // prepare result
        $result = false;

        if ($this->isService($serviceName)) {
            $result = $this->createQueueItem($name, $serviceName, $data);
        }

        return $result;
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
     * @return bool
     */
    protected function isRunning(): bool
    {
        $count = $this
            ->getEntityManager()
            ->getRepository('Job')
            ->where(
                [
                    'queueItemId!=' => null,
                    'status'        => ["Pending", "Running"]
                ]
            )
            ->count();

        return !empty($count);
    }

    /**
     * @return null|Entity
     */
    protected function getItemToRun(): ?Entity
    {
        // prepare result
        $result = null;

        $sql
            = "SELECT
                      q.id
                    FROM queue_item AS q
                    LEFT JOIN job AS j ON q.id = j.queue_item_id AND j.deleted = 0
                    WHERE 
                          q.deleted=0
                      AND j.id IS NULL
                    ORDER BY q.sort_order ASC
                    LIMIT 1 OFFSET 0";

        $sth = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute();
        $data = $sth->fetch(\PDO::FETCH_ASSOC);

        if (!empty($data)) {
            $result = $this->getEntityManager()->getEntity('QueueItem', $data['id']);
        }

        return $result;
    }

    /**
     * @param Entity $item
     *
     * @return Entity
     * @throws Error
     */
    protected function createCronJob(Entity $item): Entity
    {
        $job = $this->getEntityManager()->getEntity('Job');
        $job->set(
            [
                'queueItemId' => $item->get('id'),
                'name'        => $item->get('name'),
                'executeTime' => (new \DateTime())->format('Y-m-d H:i:s'),
                'serviceName' => $item->get('serviceName'),
                'method'      => 'run',
                'data'        => $item->get('data')
            ]
        );
        $this->getEntityManager()->saveEntity($job);

        // set Running status for item
        $item->set('status', 'Running');
        $this->getEntityManager()->saveEntity($item);

        return $job;
    }

    /**
     * Update statuses
     */
    protected function updateStatuses(): void
    {
        $sql
            = "SELECT
                      q.id,
                      j.status
                    FROM queue_item AS q
                    LEFT JOIN job AS j ON q.id = j.queue_item_id AND j.deleted = 0
                    WHERE 
                          q.deleted=0
                      AND j.status IN ('Success', 'Failed')
                      AND j.status != q.status                      
                    ORDER BY q.sort_order ASC";

        $sth = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute();
        $data = $sth->fetchAll(\PDO::FETCH_ASSOC);

        if (!empty($data)) {
            $sql = '';
            foreach ($data as $row) {
                // prepare vars
                $id = $row['id'];
                $status = $row['status'];

                $sql .= "UPDATE `queue_item` SET status='{$status}' WHERE id='{$id}';";
            }
            $sth = $this
                ->getEntityManager()
                ->getPDO()
                ->prepare($sql);
            $sth->execute();
        }
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
