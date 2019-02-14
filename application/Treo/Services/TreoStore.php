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

namespace Treo\Services;

use Espo\Core\Utils\Json;
use Espo\Core\Utils\Util;

/**
 * Class TreoStore
 *
 * @author r.ratsun@treolabs.com
 */
class TreoStore extends \Espo\Core\Templates\Services\Base
{
    /**
     * Refresh cached data
     */
    public function refresh(): void
    {
        if (!empty($json = $this->getRemotePackages())) {
            $this->caching(Json::decode($json, true));
        }
    }

    /**
     * Send notification about new version of module
     */
    public function notify(): void
    {
        // get module notified versions
        $nativeNotifiedVersions = $this->getConfig()->get("moduleNotifiedVersion", []);

        // clone
        $notifiedVersions = $nativeNotifiedVersions;

        foreach ($this->getInstalled() as $package) {
            // prepare id
            $id = $package['id'];

            // get notified version
            $version = (isset($notifiedVersions[$id])) ? $notifiedVersions[$id] : null;

            if (isset($package['versions'][0]['version'])) {
                // prepare version
                $packageVersion = $package['versions'][0]['version'];

                if ($packageVersion != $version) {
                    // push
                    $notifiedVersions[$id] = $packageVersion;

                    // send
                    if (!is_null($version)) {
                        $this->sendNotification($package);
                    }
                }
            }
        }

        // set to config
        if ($nativeNotifiedVersions != $notifiedVersions) {
            $this->getConfig()->set("moduleNotifiedVersion", $notifiedVersions);
            $this->getConfig()->save();
        }
    }

    /**
     * Init
     */
    protected function init()
    {
        parent::init();

        $this->addDependency('language');
        $this->addDependency('metadata');
    }

    /**
     * @param array $data
     */
    protected function caching(array $data): void
    {
        // delete all
        $sth = $this
            ->getEntityManager()
            ->getPDO()
            ->prepare("DELETE FROM treo_store");
        $sth->execute();

        foreach ($data as $package) {
            $entity = $this->getEntityManager()->getEntity("TreoStore");
            $entity->id = $package['treoId'];
            $entity->set('packageId', $package['packageId']);
            $entity->set('url', $package['url']);
            $entity->set('status', $package['status']);
            $entity->set('versions', $package['versions']);
            foreach ($package['name'] as $locale => $value) {
                if ($locale == 'default') {
                    $entity->set('name', $value);
                } else {
                    $entity->set('name' . Util::toCamelCase(strtolower($locale), "_", true), $value);
                }
            }
            foreach ($package['description'] as $locale => $value) {
                if ($locale == 'default') {
                    $entity->set('description', $value);
                } else {
                    $entity->set('description' . Util::toCamelCase(strtolower($locale), "_", true), $value);
                }
            }

            $this->getEntityManager()->saveEntity($entity, ['skipAll' => true]);
        }
    }

    /**
     * @return string
     */
    protected function getRemotePackages(): string
    {
        // prepare params
        $params = [
            'allowUnstable' => $this->getConfig()->get('developMode', 0),
            'id'            => $this->getConfig()->get('treoId', 'common')
        ];

        return file_get_contents("https://packagist.treopim.com/api/v1/packages?" . http_build_query($params));
    }

    /**
     * @return array
     */
    protected function getInstalled(): array
    {
        // prepare result
        $result = [];

        // find
        $data = $this
            ->getRepository()
            ->where(['id' => $this->getInjection('metadata')->getModuleList()])
            ->find();

        if (count($data) > 0) {
            foreach ($data as $row) {
                $result[$row->get('id')] = $row->toArray();
                $result[$row->get('id')]['versions'] = json_decode(json_encode($row->get('versions')), true);
            }
        }

        return $result;
    }

    /**
     * @param array $package
     */
    protected function sendNotification(array $package): void
    {
        if (!empty($users = $this->getEntityManager()->getRepository('User')->getAdminUsers())) {
            // prepare id
            $id = $package['id'];

            // prepare config data
            $isDisabledGlobally = $this->getConfig()->get('notificationNewModuleVersionDisabled', false);

            // prepare preference key
            $key = 'receiveNewModuleVersionNotifications';

            foreach ($users as $user) {
                // prepare preferences
                $preferences = json_decode($user['data'], true);

                // is disabled for user
                $isDisabled = (isset($preferences[$key]) && !$preferences[$key]);

                if (!$isDisabled && !$isDisabledGlobally) {
                    // create notification
                    $notification = $this->getEntityManager()->getEntity('Notification');
                    $notification->set(
                        [
                            'type'   => 'TreoMessage',
                            'userId' => $user['id'],
                            'data'   => [
                                'id'              => $id,
                                'messageTemplate' => 'newModuleVersion',
                                'messageVars'     => [
                                    'moduleName'    => $package['name'],
                                    'moduleVersion' => $package['versions'][0]['version'],
                                ]
                            ],
                        ]
                    );
                    $this->getEntityManager()->saveEntity($notification);
                }
            }
        }
    }
}
