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

namespace Espo\Modules\TreoCore\Controllers;

use Espo\Controllers\EntityManager;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;

/**
 * TreoEntityManager controller
 *
 * @author r.ratsun@zinitsolutions.com
 */
class TreoEntityManager extends EntityManager
{
    /**
     * Create entity action
     *
     * @param $params
     * @param $data
     * @param $request
     *
     * @return bool
     */
    public function actionCreateEntity($params, $data, $request)
    {
        if (!$request->isPost()) {
            throw new BadRequest();
        }

        // prepare data
        $data = get_object_vars($data);

        if (empty($name = $data['name']) || empty($type = $data['type'])) {
            throw new BadRequest();
        }

        // filtering data
        $name = filter_var($name, \FILTER_SANITIZE_STRING);
        $type = filter_var($type, \FILTER_SANITIZE_STRING);

        // prepare params
        $params = [];
        if (!empty($data['labelSingular'])) {
            $params['labelSingular'] = $data['labelSingular'];
        }
        if (!empty($data['labelPlural'])) {
            $params['labelPlural'] = $data['labelPlural'];
        }
        if (!empty($data['stream'])) {
            $params['stream'] = $data['stream'];
        }
        if (!empty($data['disabled'])) {
            $params['disabled'] = $data['disabled'];
        }
        if (!empty($data['sortBy'])) {
            $params['sortBy'] = $data['sortBy'];
        }
        if (!empty($data['sortDirection'])) {
            $params['asc'] = $data['sortDirection'] === 'asc';
        }
        if (isset($data['textFilterFields']) && is_array($data['textFilterFields'])) {
            $params['textFilterFields'] = $data['textFilterFields'];
        }

        // prepare event data
        $eventData = [
            'name' => $name,
            'data' => $data
        ];

        // triggered event
        $this->triggeredEvent('beforeCreate', $eventData);

        // create entity
        $result = $this
            ->getContainer()
            ->get('entityManagerUtil')
            ->create($name, $type, $params);

        if ($result) {
            $tabList = $this->getConfig()->get('tabList', []);

            if (!in_array($name, $tabList)) {
                $tabList[] = $name;
                $this->getConfig()->set('tabList', $tabList);
                $this->getConfig()->save();
            }

            $this->getContainer()->get('dataManager')->rebuild();
        } else {
            throw new Error();
        }

        // triggered event
        $this->triggeredEvent('afterCreate', $eventData);

        return true;
    }

    /**
     * Triggered event
     *
     * @param string $action
     * @param array  $data
     *
     * @return void
     */
    protected function triggeredEvent(string $action, array $data = [])
    {
        $this
            ->getContainer()
            ->get('eventManager')
            ->triggered('EntityManager', $action, $data);
    }
}
