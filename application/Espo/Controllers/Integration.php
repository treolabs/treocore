<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM ist Open Source Product Information Managegement (PIM) application,
 * based on EspoCRM.
 * Copyright (C) 2017-2018 Zinit Solutions GmbH
 * Website: http://www.treopim.com
 *
 * TreoPIM as well es EspoCRM is free software: you can redistribute it and/or modify
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

namespace Espo\Controllers;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\BadRequest;

class Integration extends \Espo\Core\Controllers\Record
{
    protected function checkControllerAccess()
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Forbidden();
        }
    }

    public function actionIndex($params, $data, $request)
    {
        return false;
    }

    public function actionRead($params, $data, $request)
    {
        $entity = $this->getEntityManager()->getEntity('Integration', $params['id']);
        return $entity->toArray();
    }

    public function actionUpdate($params, $data, $request)
    {
        return $this->actionPatch($params, $data, $request);
    }

    public function actionPatch($params, $data, $request)
    {
        if (!$request->isPut() && !$request->isPatch()) {
            throw new BadRequest();
        }
        $entity = $this->getEntityManager()->getEntity('Integration', $params['id']);
        $entity->set($data);
        $this->getEntityManager()->saveEntity($entity);

        $integrationsConfigData = $this->getConfig()->get('integrations');

        if (!$integrationsConfigData || !($integrationsConfigData instanceof \StdClass)) {
            $integrationsConfigData = (object)[];
        }
        $integrationName = $params['id'];

        $integrationsConfigData->$integrationName = $entity->get('enabled');
        $this->getConfig()->set('integrations', $integrationsConfigData);

        $this->getConfig()->save();

        return $entity->toArray();
    }
}

