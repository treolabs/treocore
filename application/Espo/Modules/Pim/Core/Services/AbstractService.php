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

namespace Espo\Modules\Pim\Core\Services;

use Espo\Core\Templates\Services\Base;
use \Espo\Core\Exceptions\Forbidden;

/**
 * AbstractService
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
class AbstractService extends Base
{

    /**
     * Find linked Entities with the use of custom relation
     * prepare selectParams for query and entityCollection for output
     *
     * @param string $link       name of the related entities
     * @param array  $params
     * @param string $customJoin custom relation (left, right or inner join)
     *
     * @return array
     * @throws Forbidden
     */
    protected function findCustomLinkedEntities(string $link, array $params, string $customJoin): array
    {
        // check acl for related entity
        if (!$this->getAcl()->check($link, 'read')) {
            throw new Forbidden();
        }
        // prepare select params
        $selectParams = $this->getSelectManager($link)->getSelectParams($params, true);
        $selectParams['customJoin'] = $customJoin;
        $this->getEntityManager()->getRepository($link)->handleSelectParams($selectParams);

        // find linked entities
        $collection = $this->getRepository()->findCustomLinkedEntities($link, $selectParams);
        // prepare entity for output
        $recordService = $this->getRecordService($link);
        foreach ($collection as $entity) {
            $recordService->loadAdditionalFieldsForList($entity);
            $recordService->prepareEntityForOutput($entity);
        }

        return [
            'collection' => $collection,
            'total' => $this->getRepository()->getCustomTotal($link, $selectParams)
        ];
    }
}
