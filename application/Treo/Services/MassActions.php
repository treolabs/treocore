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
        $ids = $this->getMassUpdateIds($entityType, $data);

        // attributes
        $attributes = $data->attributes;

        if (count($ids) > $this->getConfig()->get('webMassUpdateMax', 200)) {
            // create jobs
            $this->createJobs($entityType, $attributes, $ids);

            return [
                'count'          => 0,
                'ids'            => [],
                'byQueueManager' => true
            ];
        }

        return $this->espoMassUpdate($entityType, $attributes, ['ids' => $ids]);
    }

    /**
     * @param string    $entityType
     * @param \stdClass $data
     *
     * @return array
     */
    public function getMassUpdateIds(string $entityType, \stdClass $data): array
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
    protected function createJobs(string $entityType, \stdClass $attributes, array $ids): void
    {
        // get cronMax
        $cronMax = $this->getConfig()->get('cronMassUpdateMax', 2000);

        // prepare data
        $data = [
            'entityType' => $entityType,
            'attributes' => $attributes,
            'ids'        => []
        ];

        if (count($ids) > $cronMax) {
            foreach ($ids as $id) {
                if (count($data['ids']) == $cronMax) {
                    // push
                    $this->qmPushMassUpdatePartial($entityType, $data);

                    // clearing tmp ids
                    $data['ids'] = [];
                }

                // push to tmp ids
                $data['ids'][] = $id;
            }
            // push
            $this->qmPushMassUpdatePartial($entityType, $data);
        } else {
            // prepare data
            $data['ids'] = $ids;

            // push
            $this->qmPushMassUpdate($entityType, $data);
        }
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
     * @param string    $entityType
     * @param \stdClass $attributes
     * @param array     $data
     *
     * @return mixed
     */
    protected function espoMassUpdate(string $entityType, \stdClass $attributes, array $data)
    {
        return $this
            ->getContainer()
            ->get('serviceFactory')
            ->create($entityType)
            ->massUpdate($attributes, $data);
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

    /**
     * @param string $entityType
     * @param string $serviceName
     * @param array  $data
     *
     * @return bool
     */
    private function qmPushMassUpdate(string $entityType, array $data): bool
    {
        // prepare name
        $name = $entityType . ". " . $this->translate('massUpdate', 'massActions');

        return $this->qmPush($name, "QueueManagerMassUpdate", $data);
    }

    /**
     * @param string $entityType
     * @param array  $data
     *
     * @return bool
     */
    private function qmPushMassUpdatePartial(string $entityType, array $data): bool
    {
        // prepare translate key
        $key = sprintf($this->translate('massUpdatePartial', 'massActions'), $this->massUpdatePart);

        // increase
        $this->massUpdatePart++;

        return $this->qmPush("$entityType. $key", "QueueManagerMassUpdate", $data);
    }
}
