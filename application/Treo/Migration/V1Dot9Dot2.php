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

namespace Treo\Migration;

use DateTime;
use Espo\Core\Exceptions\Error;
use PDO;
use Treo\Core\Migration\AbstractMigration;

/**
 * Version 1.9.2
 *
 * @author r.ratsun@zinitsolutions.com
 */
class V1Dot9Dot2 extends AbstractMigration
{
    /**
     * Up to current
     */
    public function up(): void
    {
        // checking mysql version
        $this->checkMySQLVersion();

        // update users
        $this->updateUsers();

        // update Mysql character
        $this->updateMysqlCharacter();
    }

    /**
     * Check MySQL version
     *
     * @throws Error
     */
    protected function checkMySQLVersion(): void
    {
        $sth = $this
            ->getEntityManager()
            ->getPDO()
            ->prepare("SHOW VARIABLES LIKE 'version'");
        $sth->execute();

        $row = $sth->fetch(PDO::FETCH_ASSOC);

        if (empty($row['Value'])) {
            return;
        }

        if (version_compare($row['Value'], '5.5.3') == -1) {
            throw new Error('Your MySQL version is not supported. Please use MySQL 5.5.3 at least.');
        }
    }

    /**
     * Update users
     */
    protected function updateUsers(): void
    {
        // get roles
        $roleList = $this
            ->getEntityManager()
            ->getRepository('Role')
            ->find();

        foreach ($roleList as $role) {
            $data = $role->get('data');
            if ($data && isset($data->User) && is_object($data->User)) {
                $data = clone($data);
                $data->User->edit = 'no';
                $role->set('data', $data);

                $this->getEntityManager()->saveEntity($role);
            }
        }
    }

    /**
     * Update Mysql character
     */
    protected function updateMysqlCharacter(): void
    {
        // prepare exec time
        $executeTime = (new DateTime())
            ->modify('+1 minutes')
            ->format('Y-m-d H:i:s');

        // create job
        $job = $this->getEntityManager()->getEntity('Job');
        $job->set(
            [
                'serviceName' => 'MysqlCharacter',
                'methodName'  => 'jobConvertToMb4',
                'executeTime' => $executeTime
            ]
        );
        $this->getEntityManager()->saveEntity($job);

        // set to config
        $this->getConfig()->set('streamEmailNotificationsTypeList', ['Post', 'Status', 'EmailReceived']);
        $this->getConfig()->save();
    }
}
