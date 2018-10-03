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

use Espo\Core\ORM\EntityManager;
use Espo\Core\ServiceFactory;
use Espo\Core\Utils\Util;
use Espo\Core\Utils\Json;
use Espo\Entities\User;
use Treo\Core\Utils\Config;
use Treo\Services\AbstractProgressManager;
use Treo\Services\ProgressJobInterface as PMInterface;
use Treo\Traits\ContainerTrait;
use Treo\Traits\EventTriggeredTrait;

/**
 * ProgressManager
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class ProgressManager
{

    /**
     * Traits
     */
    use ContainerTrait;
    use EventTriggeredTrait;

    const CACHE_PATH = 'data/pm-show-popup.json';

    /**
     * @var array
     */
    protected $progressConfig = null;

    /**
     * Create progress item
     *
     * @param string $name
     * @param string $type
     * @param array  $data
     * @param string $userId
     * @param bool   $isHidden
     *
     * @return bool
     */
    public function push(
        string $name,
        string $type,
        array $data = [],
        string $userId = '',
        bool $isHidden = false
    ): bool {
        // prepare result
        $result = false;

        // get config
        $config = $this->getProgressConfig();

        if (isset($config['type'][$type])) {
            // prepare userId
            $userId = empty($userId) ? $this->getUser()->get('id') : $userId;

            $result = $this->insert($name, $type, $data, $userId, $isHidden);
        }

        return $result;
    }

    /**
     * Get popup data
     *
     * @return array
     */
    public function getPopupData(): array
    {
        // prepare data
        $data = [];

        if (file_exists(self::CACHE_PATH)) {
            $data = Json::decode(file_get_contents(self::CACHE_PATH), true);
        }

        return $data;
    }

    /**
     * Hide popup
     *
     * @param string $userId
     */
    public function hidePopup(string $userId): void
    {
        // prepare data
        $data = $this->getPopupData();

        foreach ($data as $k => $v) {
            if ($v == "'$userId'") {
                unset($data[$k]);
            }
        }

        // set to file
        $this->savePopupFile($data);
    }

    /**
     * Run jobs
     */
    public function run(): void
    {
        if (!empty($jobs = $this->getJobs())) {
            // get config
            $config = $this->getProgressConfig();

            foreach ($jobs as $job) {
                if (!empty($serviceName = $config['type'][$job['type']]['service'])) {
                    try {
                        // create service
                        $service = $this->getServiceFactory()->create($serviceName);
                    } catch (\Exception $e) {
                        // set error status
                        $this->setErrorStatus($job, "No such service: {$serviceName}");
                    }

                    if (!empty($service) && $service instanceof PMInterface) {
                        // set job user as system user
                        if ($this->setJobUser($job['createdById'])) {
                            try {
                                $isExecuted = $service->executeProgressJob($job);
                            } catch (\Exception $e) {
                                $isExecuted = false;

                                // set error status
                                $message = 'ProgressManager job running failed: ' . $e->getMessage();
                                $this->setErrorStatus($job, $message);
                            }

                            if ($isExecuted) {
                                // update job
                                $this->updateJob($job['id'], $job['type'], $service);

                                // notify user
                                $this->notifyUser($service->getStatus(), $job);
                            }
                        } else {
                            // set error status
                            $this->setErrorStatus($job, 'No such user: ' . $job['createdById']);
                        }
                    } else {
                        // set error status
                        $message = 'ProgressManager service should be instance of: ' . PMInterface::class;
                        $this->setErrorStatus($job, $message);
                    }
                } else {
                    // set error status
                    $this->setErrorStatus($job, 'No such ProgressManager job type: ' . $job['type']);
                }
            }

            // set cron user as system user
            $this->setJobUser('system');
        }
    }

    /**
     * Get progress config
     *
     * @return array
     */
    public function getProgressConfig(): array
    {
        if (is_null($this->progressConfig)) {
            $this->progressConfig = include "application/Treo/Configs/ProgressManager.php";
            foreach ($this->getContainer()->get('metadata')->getModuleList() as $module) {
                // prepare path
                $path = "application/Espo/Modules/{$module}/Configs/ProgressManager.php";

                if (file_exists($path)) {
                    $data = include $path;

                    $this->progressConfig = array_merge_recursive($this->progressConfig, $data);
                }
            }
        }

        return $this->progressConfig;
    }

    /**
     * Insert
     *
     * @param string $name
     * @param string $type
     * @param array  $data
     * @param string $userId
     * @param bool   $isHidden
     *
     * @return bool
     */
    protected function insert(string $name, string $type, array $data, string $userId, bool $isHidden = false): bool
    {
        // prepare data
        $result = false;

        if (!empty($name) && !empty($type)) {
            // get pdo
            $pdo = $this->getEntityManager()->getPDO();

            // prepare params
            $id = Util::generateId();
            $name = $pdo->quote($name);
            $type = $pdo->quote($type);
            $data = $pdo->quote(Json::encode($data));
            $status = $pdo->quote(AbstractProgressManager::$progressStatus['new']);
            $userId = $pdo->quote($userId);
            $date = $pdo->quote(date('Y-m-d H:i:s'));
            $isHidden = (empty($isHidden)) ? 0 : 1;

            // prepare sql
            $sql = "INSERT INTO progress_manager SET "
                . "id='{$id}', progress_manager.name={$name}, progress_manager.type={$type}, "
                . "progress_manager.data={$data}, progress_manager.status={$status}, progress=0, "
                . "created_by_id={$userId}, created_at={$date}, modified_at={$date}, is_hidden={$isHidden};";

            $sth = $this
                ->getEntityManager()
                ->getPDO()
                ->prepare($sql);
            $sth->execute();

            // set user to popup data
            $popupData = $this->getPopupData();
            $popupData[] = $userId;
            $this->savePopupFile($popupData);

            // prepare result
            $result = true;
        }

        return $result;
    }

    /**
     * Get ProgressManager jobs
     *
     * @return array
     */
    protected function getJobs(): array
    {
        // prepare limit
        $limit = (int)$this->getConfig()->get('pmLimit');
        if (empty($limit)) {
            $limit = 5;
        }

        // prepare statuses
        $statuses = [
            AbstractProgressManager::$progressStatus['new'],
            AbstractProgressManager::$progressStatus['in_progress']
        ];
        $statuses = implode("','", $statuses);

        // prepare sql
        $sql
            = "SELECT
                  id              as `id`,
                  name            as `name`,
                  progress        as `progress`,
                  progress_offset as `progressOffset`,
                  type            as `type`,
                  is_hidden       as `isHidden`,
                  data            as `data`,
                  status          as `status`,
                  created_by_id   as `createdById`
                FROM
                  progress_manager
                WHERE 
                     deleted=0 
                 AND is_closed=0 
                 AND status IN ('{$statuses}')
                ORDER BY status DESC, created_at DESC 
                LIMIT {$limit} OFFSET 0";

        // execute sql
        $sth = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute();
        $data = $sth->fetchAll(\PDO::FETCH_ASSOC);

        return (!empty($data)) ? $data : [];
    }

    /**
     * Update job
     *
     * @param string      $id
     * @param string      $type
     * @param PMInterface $service
     *
     * @return bool
     */
    protected function updateJob(string $id, string $type, PMInterface $service): bool
    {
        // prepare result
        $result = false;

        if (!empty($id)) {
            // prepare params
            $date = date('Y-m-d H:i:s');
            $status = AbstractProgressManager::$progressStatus[$service->getStatus()];
            $progress = $service->getProgress();
            $offset = $service->getOffset();
            $data = Json::encode($service->getData());
            $eventData = [
                'id'       => $id,
                'type'     => $type,
                'status'   => $status,
                'progress' => $progress,
                'data'     => $data,
            ];

            // triggered event
            $this->triggered('ProgressManager', 'beforeUpdate', $eventData);

            // prepare sql
            $sql = "UPDATE progress_manager SET `status`='{$status}', `progress`={$progress}, "
                . "`progress_offset`={$offset}, `data`='{$data}', modified_at='{$date}' WHERE id='{$id}'";

            $sth = $this
                ->getEntityManager()
                ->getPDO()
                ->prepare($sql);
            $sth->execute();

            // prepare result
            $result = true;
        }

        return $result;
    }

    /**
     * Set error status to job
     *
     * @param string $id
     */
    protected function setErrorStatus(array $job, string $message): void
    {
        // prepare id
        $id = $job['id'];

        // prepare params
        $status = AbstractProgressManager::$progressStatus['error'];

        // prepare sql
        $sql = "UPDATE progress_manager SET `status`='{$status}' WHERE id='{$id}'";

        $sth = $this
            ->getEntityManager()
            ->getPDO()
            ->prepare($sql);
        $sth->execute();

        // notify user
        $this->notifyUser('error', $job);

        // to log
        $GLOBALS['log']->error($message);
    }

    /**
     * Notify user
     *
     * @param string $status
     * @param array  $job
     *
     * @return bool
     */
    protected function notifyUser(string $status, array $job): bool
    {
        // prepare result
        $result = false;

        if (in_array($status, ['success', 'error']) && empty($job['isHidden'])) {
            // prepare message
            $message = $this->translate('notificationMessages', $status);

            // create notification
            $notification = $this->getEntityManager()->getEntity('Notification');
            $notification->set(
                [
                    'type'    => 'Message',
                    'userId'  => $job['createdById'],
                    'message' => sprintf($message, $job['name'])
                ]
            );
            $this->getEntityManager()->saveEntity($notification);

            // prepare result
            $result = true;
        }

        return $result;
    }

    /**
     * Set user as system user
     *
     * @param string $userId
     */
    protected function setJobUser(string $userId): bool
    {
        // prepare result
        $result = false;

        $user = $this
            ->getEntityManager()
            ->getRepository('User')
            ->get($userId);

        if (!empty($user)) {
            $this->getEntityManager()->setUser($user);
            $this->getContainer()->setUser($user);

            // prepare result
            $result = true;
        }

        return $result;
    }

    /**
     * Save popup file
     *
     * @param array $data
     */
    protected function savePopupFile(array $data): void
    {
        $file = fopen(self::CACHE_PATH, "w");
        fwrite($file, Json::encode($data));
        fclose($file);
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
     * Get user
     *
     * @return User
     */
    protected function getUser(): User
    {
        return $this->getContainer()->get('user');
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
     * Get service factory
     *
     * @return ServiceFactory
     */
    protected function getServiceFactory(): ServiceFactory
    {
        return $this->getContainer()->get('serviceFactory');
    }

    /**
     * Translate field
     *
     * @param string $tab
     * @param string $key
     *
     * @return string
     */
    protected function translate(string $tab, string $key): string
    {
        return $this->getContainer()->get('language')->translate($key, $tab, 'ProgressManager');
    }
}
