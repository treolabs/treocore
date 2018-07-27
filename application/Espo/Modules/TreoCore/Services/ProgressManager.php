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

namespace Espo\Modules\TreoCore\Services;

use Espo\Core\Utils\Json;
use Espo\Modules\TreoCore\Services\StatusActionInterface;
use Slim\Http\Request;

/**
 * ProgressManager service
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class ProgressManager extends AbstractProgressManager
{
    /**
     * @var int
     */
    public static $maxSize = 15;

    /**
     * Is need to show progress popup
     *
     * @return bool
     */
    public function isShowPopup(): bool
    {
        // prepare params
        $userId = $this->getUser()->get('id');
        $status = self::$progressStatus['new'];

        // prepare sql
        $sql
            = "SELECT 
                  COUNT(id) as `total_count`
                FROM
                  progress_manager
                WHERE deleted = 0 AND status='{$status}' AND created_by_id='{$userId}'";

        // execute sql
        $sth = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute();
        $result = $sth->fetch(\PDO::FETCH_ASSOC);

        return !empty($result['total_count']);
    }

    /**
     * Get data for progresses popup
     *
     * @param Request $request
     *
     * @return array
     */
    public function popupData(Request $request): array
    {
        // prepare result
        $result = [
            'total' => 0,
            'list'  => []
        ];

        // prepare request data
        $maxSize = (!empty($request->get('maxSize'))) ? (int)$request->get('maxSize') : self::$maxSize;

        if (!empty($data = $this->getDbData($maxSize))) {
            // prepare new records
            $newRecords = [];

            // set total
            $result['total'] = $this->getDbDataTotal();

            foreach ($data as $row) {
                // prepare status key
                $statusKey = array_flip(self::$progressStatus)[$row['status']];

                $result['list'][] = [
                    'id'       => $row['id'],
                    'name'     => $row['name'],
                    'progress' => round($row['progress'], 2),
                    'status'   => [
                        'key'       => $statusKey,
                        'translate' => $this->translate('progressStatus', $statusKey)
                    ],
                    'actions'  => $this->getItemActions($statusKey, $row),
                ];

                if ($statusKey == 'new') {
                    $newRecords[] = $row['id'];
                }
            }

            /**
             * Update status for new records
             */
            if (!empty($newRecords)) {
                $this->updateStatus($newRecords, 'in_progress');
            }
        }

        return $result;
    }

    /**
     * Get item actions
     *
     * @param string $status
     * @param array  $record
     *
     * @return array
     */
    public function getItemActions(string $status, array $record): array
    {
        // prepare config
        $config = $this->getProgressConfig();

        // prepare data
        $data = [];

        /**
         * For status action
         */
        if (isset($config['statusAction'][$status]) && is_array($config['statusAction'][$status])) {
            $data = array_merge($data, $config['statusAction'][$status]);
        }

        /**
         * For type action
         */
        if (isset($config['type'][$record['type']]['action'][$status])) {
            $data = array_merge($data, $config['type'][$record['type']]['action'][$status]);
        }

        /**
         * Set items to result
         */
        $result = [];
        foreach ($data as $action) {
            if (isset($config['actionService'][$action])) {
                // create service
                $service = $this->getInjection('serviceFactory')->create($config['actionService'][$action]);

                if (!empty($service) && $service instanceof StatusActionInterface) {
                    $result[] = [
                        'type' => $action,
                        'data' => $service->getProgressStatusActionData($record),
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Translate field
     *
     * @param string $tab
     * @param string $key
     *
     * @return string
     */
    public function translate(string $tab, string $key): string
    {
        return $this->getInjection('language')->translate($key, $tab, 'ProgressManager');
    }

    /**
     * Init
     */
    protected function init()
    {
        parent::init();

        $this->addDependency('language');
        $this->addDependency('serviceFactory');
        $this->addDependency('progressManager');
    }

    /**
     * Get DB data
     *
     * @param int $maxSize
     *
     * @return array
     */
    protected function getDbData(int $maxSize): array
    {
        // prepare user id
        $userId = $this->getUser()->get('id');

        // prepare sql
        $sql
            = "SELECT
                  id              as `id`,
                  name            as `name`,
                  deleted         as `deleted`,
                  progress        as `progress`,
                  progress_offset as `progressOffset`,
                  type            as `type`,
                  data            as `data`,
                  status          as `status`,
                  created_at      as `createdAt`,
                  created_by_id   as `createdById`
                FROM
                  progress_manager
                WHERE 
                     deleted=0 
                 AND created_by_id='{$userId}' 
                ORDER BY status ASC, created_at DESC 
                LIMIT {$maxSize} OFFSET 0";

        // execute sql
        $sth = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute();
        $data = $sth->fetchAll(\PDO::FETCH_ASSOC);

        return (!empty($data)) ? $data : [];
    }

    /**
     * Get DB data total
     *
     * @return int
     */
    protected function getDbDataTotal(): int
    {
        // prepare user id
        $userId = $this->getUser()->get('id');

        // prepare sql
        $sql
            = "SELECT
                   COUNT(id) as `total_count`
                FROM
                  progress_manager
                WHERE
                  deleted = 0 AND created_by_id='{$userId}'";

        // execute sql
        $sth = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute();
        $data = $sth->fetch(\PDO::FETCH_ASSOC);

        return (!empty($data['total_count'])) ? (int)$data['total_count'] : 0;
    }


    /**
     * Update status
     *
     * @param array $ids
     *
     * @return void
     */
    protected function updateStatus(array $ids, string $status): void
    {
        // prepare params
        $status = self::$progressStatus[$status];
        $ids = implode("','", $ids);

        // prepare sql
        $sql = "UPDATE progress_manager SET `status`='{$status}' WHERE id IN ('{$ids}')";

        $sth = $this
            ->getEntityManager()
            ->getPDO()
            ->prepare($sql);
        $sth->execute();
    }

    /**
     * Get progress config
     *
     * @return array
     */
    protected function getProgressConfig(): array
    {
        return $this->getInjection('progressManager')->getProgressConfig();
    }
}
