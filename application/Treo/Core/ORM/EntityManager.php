<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2019 TreoLabs GmbH
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

namespace Treo\Core\ORM;

use Treo\ORM\DB\MysqlMapper;

/**
 * Class EntityManager
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class EntityManager extends \Espo\Core\ORM\EntityManager
{
    /**
     * @inheritdoc
     */
    public function getMapper($name)
    {
        // get mapper
        $mapper = parent::getMapper($name);

        if ($mapper instanceof MysqlMapper) {
            $mapper->setEventManager($this->getContainer()->get('eventManager'));
        }

        return $mapper;
    }

    /**
     * @inheritdoc
     */
    protected function getMapperClassName($name)
    {
        $className = null;

        switch ($name) {
            case 'RDB':
                $className = $this->getMysqlMapperClassName();
                break;
        }

        return $className;
    }


    /**
     * @return string
     */
    protected function getMysqlMapperClassName(): string
    {
        return MysqlMapper::class;
    }
}