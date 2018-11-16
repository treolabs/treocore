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

namespace Treo\Listeners;

/**
 * Class CommonListener
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class CommonListener extends AbstractCommonListener
{
    /**
     * @var bool
     */
    private $isMassUpdate = false;

    /**
     * @inheritdoc
     */
    public function commonAction(string $target, string $action, array $data): array
    {
        if (method_exists($this, $action)) {
            $data = $this->$action($target, $data);
        }

        return $data;
    }

    /**
     * @param string $target
     * @param array  $data
     *
     * @return array
     */
    protected function beforeActionMassUpdate(string $target, array $data): array
    {
        // prepare select params
        $selectParams = $this->getSelectParams($target, $this->getWhere($data['data']));

        // get count
        $count = $this
            ->getEntityManager()
            ->getRepository($target)
            ->count($selectParams);

        if ($count > $this->getConfig()->get('massUpdateMax', 200)) {
            // modify where
            $data['data']->ids = ['no-such-id'];
            $data['data']->byWhere = false;

            // set flag
            $this->isMassUpdate = true;

            // prepare translate key
            $key = $this
                ->getContainer()
                ->get('language')
                ->translate('massUpdate', 'massActions', 'Global');

            // prepare job data
            $jobData = [
                'entity'       => $target,
                'selectParams' => $selectParams,
                'attributes'   => $data['data']->attributes
            ];

            // push job
            $this
                ->getContainer()
                ->get('queueManager')
                ->push("{$target}. {$key}", "QueueManagerMassUpdate", $jobData);

        }

        return $data;
    }

    /**
     * @param string $target
     * @param array  $data
     *
     * @return array
     */
    protected function afterActionMassUpdate(string $target, array $data): array
    {
        if ($this->isMassUpdate) {
            $data['result']['isMassUpdate'] = true;
        }

        return $data;
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
}
