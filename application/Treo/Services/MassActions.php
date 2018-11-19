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

namespace Treo\Services;

/**
 * Class MassActions
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class MassActions extends AbstractService
{
    /**
     * @var int
     */
    private $massUpdatePart = 1;

    /**
     * @param string    $entityType
     * @param \stdClass $data
     *
     * @return array
     */
    public function massUpdate(string $entityType, \stdClass $data): array
    {
        // get ids
        $ids = $this->getMassActionIds($entityType, $data);

        // attributes
        $attributes = $data->attributes;

        if (count($ids) > $this->getConfig()->get('webMassUpdateMax', 200)) {
            // create jobs
            $this->createMassUpdateJobs($entityType, $attributes, $ids);

            return [
                'count'          => 0,
                'ids'            => [],
                'byQueueManager' => true
            ];
        }

        return $this->getService($entityType)->massUpdate($attributes, ['ids' => $ids]);
    }

    /**
     * @param string    $entityType
     * @param \stdClass $data
     *
     * @return array
     */
    public function massDelete(string $entityType, \stdClass $data): array
    {
        // get ids
        $ids = $this->getMassActionIds($entityType, $data);

        if (count($ids) > $this->getConfig()->get('webMassUpdateMax', 200)) {
            // create jobs
            $this->createMassDeleteJobs($entityType, $ids);

            return [
                'count'          => 0,
                'ids'            => [],
                'byQueueManager' => true
            ];
        }

        return $this->getService($entityType)->massRemove(['ids' => $ids]);
    }

    /**
     * @param string    $entityType
     * @param \stdClass $data
     *
     * @return array
     */
    protected function getMassActionIds(string $entityType, \stdClass $data): array
    {
        $res = $this
            ->getEntityManager()
            ->getRepository($entityType)
            ->select(['id'])
            ->find($this->getSelectParams($entityType, $this->getWhere($data)));

        return (empty($res)) ? [] : array_column($res->toArray(), 'id');
    }

    /**
     * @param string    $entityType
     * @param \stdClass $attributes
     * @param array     $ids
     */
    protected function createMassUpdateJobs(string $entityType, \stdClass $attributes, array $ids): void
    {
        if (count($ids) > $this->getConfig()->get('cronMassUpdateMax', 3000)) {
            foreach ($this->getParts($ids) as $part => $rows) {
                // prepare data
                $name = $entityType . ". " . sprintf($this->translate('massUpdatePartial', 'massActions'), $part);
                $data = [
                    'entityType' => $entityType,
                    'attributes' => $attributes,
                    'ids'        => $rows
                ];

                // push
                $this->qmPush($name, "QueueManagerMassUpdate", $data);
            }
        } else {
            // prepare data
            $name = $entityType . ". " . $this->translate('massUpdate', 'massActions');
            $data = [
                'entityType' => $entityType,
                'attributes' => $attributes,
                'ids'        => $ids
            ];

            // push
            $this->qmPush($name, "QueueManagerMassUpdate", $data);
        }
    }

    /**
     * @param string $entityType
     * @param array  $ids
     */
    protected function createMassDeleteJobs(string $entityType, array $ids): void
    {
        if (count($ids) > $this->getConfig()->get('cronMassUpdateMax', 3000)) {
            foreach ($this->getParts($ids) as $part => $rows) {
                // prepare data
                $name = $entityType . ". " . sprintf($this->translate('removePartial', 'massActions'), $part);
                $data = [
                    'entityType' => $entityType,
                    'ids'        => $rows
                ];

                // push
                $this->qmPush($name, "QueueManagerMassDelete", $data);
            }
        } else {
            // prepare data
            $name = $entityType . ". " . $this->translate('remove', 'massActions');
            $data = [
                'entityType' => $entityType,
                'ids'        => $ids
            ];

            // push
            $this->qmPush($name, "QueueManagerMassDelete", $data);
        }
    }

    /**
     * @param array $ids
     *
     * @return array
     */
    protected function getParts(array $ids): array
    {
        // prepare vars
        $result = [];
        $part = 1;
        $tmpIds = [];
        $cronMassUpdateMax = $this->getConfig()->get('cronMassUpdateMax', 3000);

        foreach ($ids as $id) {
            if (count($tmpIds) == $cronMassUpdateMax) {
                $result[$part] = $tmpIds;

                // clearing tmp ids
                $tmpIds = [];

                // increase parts
                $part++;
            }

            // push to tmp ids
            $tmpIds[] = $id;
        }

        if (!empty($tmpIds)) {
            $result[$part] = $tmpIds;
        }

        return $result;
    }

    /**
     * @param \stdClass $data
     *
     * @return array
     */
    protected function getWhere(\stdClass $data): array
    {
        // prepare where
        $where = [];
        if (property_exists($data, 'where') && !empty($data->byWhere)) {
            $where = json_decode(json_encode($data->where), true);
        } else {
            if (property_exists($data, 'ids')) {
                $values = [];
                foreach ($data->ids as $id) {
                    $values[] = [
                        'type'      => 'equals',
                        'attribute' => 'id',
                        'value'     => $id
                    ];
                }
                $where[] = [
                    'type'  => 'or',
                    'value' => $values
                ];
            }
        }

        return $where;
    }

    /**
     * @param string $entityType
     * @param array  $where
     *
     * @return array
     */
    protected function getSelectParams(string $entityType, array $where): array
    {
        return $this
            ->getContainer()
            ->get('selectManagerFactory')
            ->create($entityType)
            ->getSelectParams(['where' => $where], true, true);
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    protected function getService(string $name)
    {
        return $this
            ->getContainer()
            ->get('serviceFactory')
            ->create($name);
    }

    /**
     * @param string $name
     * @param string $serviceName
     * @param array  $data
     *
     * @return bool
     */
    private function qmPush(string $name, string $serviceName, array $data): bool
    {
        return $this
            ->getContainer()
            ->get('queueManager')
            ->push($name, $serviceName, $data);
    }
}
