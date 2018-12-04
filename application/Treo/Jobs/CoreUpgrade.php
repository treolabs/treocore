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

namespace Treo\Jobs;

/**
 * CoreUpgrade job
 *
 * @author r.ratsun r.ratsun@zinitsolutions.com
 */
class CoreUpgrade extends \Espo\Core\Jobs\Base
{
    /**
     * Run cron job
     *
     * @return bool
     */
    public function run(): bool
    {
        // max version
        $version = array_pop($this->getVersions());

        if ($version != $this->getConfig()->get('notifiedVersion')) {
            // create notification(s)
            $this->notifyAboutNewVersion($version);

            // update config
            $this->getConfig()->set('notifiedVersion', $version);
            $this->getConfig()->save();
        }

        return true;
    }

    /**
     * Notify about new version
     *
     * @param string $version
     */
    protected function notifyAboutNewVersion(string $version): void
    {
        if (!empty($users = $this->getAdminUsers())) {
            // is notification disabled ?
            $isDisabled = $this->getConfig()->get('notificationNewSystemVersionDisabled');

            foreach ($users as $user) {
                // prepare user data
                $data = json_decode($user['data'], true);

                // prepare config key
                $key = 'receiveNewSystemVersionNotifications';

                if ((isset($data[$key]) && $data[$key]) || (!isset($data[$key]) && !$isDisabled)) {
                    // create notification
                    $notification = $this->getEntityManager()->getEntity('Notification');
                    $notification->set(
                        [
                            'type'    => 'Message',
                            'userId'  => $user['id'],
                            'message' => sprintf($this->notification('newCoreVersion'), $version)
                        ]
                    );
                    $this->getEntityManager()->saveEntity($notification);
                }
            }
        }
    }

    /**
     * @return array
     */
    protected function getAdminUsers(): array
    {
        return $this
            ->getEntityManager()
            ->getRepository('User')
            ->getAdminUsers();
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function notification(string $key): string
    {
        return $this
            ->getContainer()
            ->get('language')
            ->translate($key, 'treoNotifications', 'TreoNotification');
    }

    /**
     * @return array
     */
    protected function getVersions(): array
    {
        return array_column($this->getServiceFactory()->create('TreoUpgrade')->getVersions(), 'version');
    }
}
