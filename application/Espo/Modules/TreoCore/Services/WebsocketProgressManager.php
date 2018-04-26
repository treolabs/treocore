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

use Espo\Modules\TreoCore\Websocket\AbstractService;
use Espo\Modules\TreoCore\Services\ProgressManager;
use PDO;

/**
 * ProgressManager websocket
 *
 * @author r.ratsun@zinitsolutions.com
 */
class WebsocketProgressManager extends AbstractService
{
    /**
     * Get data
     *
     * @return array
     */
    public function getData(): array
    {
        // prepare result
        $result = [];

        if (!empty($userId = $this->getFilter('userId'))) {
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
                ORDER BY status ASC, created_at DESC";

            // execute sql
            $sth = $this->getEntityManager()->getPDO()->prepare($sql);
            $sth->execute();
            $data = $sth->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($data)) {
                foreach ($data as $row) {
                    $statusKey = array_flip(ProgressManager::$progressStatus)[$row['status']];

                    $result[] = [
                        'id'       => $row['id'],
                        'name'     => $row['name'],
                        'progress' => round($row['progress'], 2),
                        'status'   => [
                            'key'       => $statusKey,
                            'translate' => $this
                                ->getInjection('language')
                                ->translate($statusKey, 'progressStatus', 'ProgressManager')
                        ],
                        'actions'  => $this
                            ->getItemActions($statusKey, $row),
                    ];
                }

            }
        }

        return $result;
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
     * Get item actions
     *
     * @param string $status
     * @param array  $record
     *
     * @return array
     */
    protected function getItemActions(string $status, array $record): array
    {
        // prepare config
        $config = $this->getInjection('progressManager')->getProgressConfig();

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
}