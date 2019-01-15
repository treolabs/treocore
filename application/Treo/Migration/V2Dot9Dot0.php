<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2018 TreoLabs GmbH
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

namespace Treo\Migration;

use Treo\Core\Migration\AbstractMigration;

/**
 * Version 2.9.0
 *
 * @author r.ratsun@treolabs.com
 */
class V2Dot9Dot0 extends AbstractMigration
{
    /**
     * Up to current
     */
    public function up(): void
    {
        // to espo 5.4.5
        $this->espo545();
    }

    /**
     * Migration for espo 5.4.5
     */
    protected function espo545(): void
    {
        $job = $this->getEntityManager()->getEntity('Job');
        $dt = new \DateTime();
        $dt->modify('+1 minutes');
        $job->set(
            [
                'serviceName' => 'App',
                'methodName'  => 'jobPopulatePhoneNumberNumeric',
                'executeTime' => $dt->format('Y-m-d H:i:s')
            ]
        );
        $this->getEntityManager()->saveEntity($job);

        $job = $this->getEntityManager()->getEntity('Job');
        $dt = new \DateTime();
        $dt->modify('+4 minutes');
        $job->set(
            [
                'serviceName' => 'App',
                'methodName'  => 'jobPopulateArrayValues',
                'executeTime' => $dt->format('Y-m-d H:i:s')
            ]
        );
        $this->getEntityManager()->saveEntity($job);

        $job = $this->getEntityManager()->getEntity('Job');
        $dt = new \DateTime();
        $dt->modify('+10 minutes');
        $job->set(
            [
                'serviceName' => 'App',
                'methodName'  => 'jobPopulateNotesTeamUser',
                'executeTime' => $dt->format('Y-m-d H:i:s')
            ]
        );
        $this->getEntityManager()->saveEntity($job);

        $this->getConfig()->set('noteDeleteThresholdPeriod', '1 month');
        $this->getConfig()->set('noteEditThresholdPeriod', '7 days');
        $this->getConfig()->save();

        $job = $this->getEntityManager()->getEntity('Job');
        $dt = new \DateTime();
        $dt->modify('+30 minutes');
        $job->set(
            [
                'serviceName' => 'MysqlCharacter',
                'methodName'  => 'jobConvertToMb4',
                'executeTime' => $dt->format('Y-m-d H:i:s')
            ]
        );
        $this->getEntityManager()->saveEntity($job);
    }
}
