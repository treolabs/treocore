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

/**
 * Class Store
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Store extends AbstractService
{
    /**
     * @var string
     */
    protected $url = null;

    /**
     * @var null|array
     */
    protected $packages = null;

    /**
     * @var string
     */
    protected $cache = 'data/cache/packages.json';

    /**
     * Refresh cached data
     */
    public function refresh(): void
    {
        // get auth data
        $authData = (new \Treo\Core\Utils\Composer())->getAuthData();

        // prepare params
        $params = [
            'allowUnstable' => $this->getConfig()->get('developMode', 0),
            'username'      => $authData['username'],
        ];

        // prepare path
        $path = $this->getUrl() . "packages?" . http_build_query($params);

        // get json data
        $json = file_get_contents($path);

        if (!empty($json)) {
            file_put_contents($this->cache, $json);
        }
    }

    /**
     * Get list
     *
     * @return array
     */
    public function getList(): array
    {
        // prepare result
        $result = [
            'total' => 0,
            'list'  => []
        ];

        if (!empty($packages = $this->getPackages())) {
            // get installed panel data
            $installed = $this->getInstalled();

            foreach ($packages as $package) {
                if (!in_array($package['treoId'], $installed)) {
                    $result['list'][] = [
                        'id'          => $package['treoId'],
                        'name'        => $this->packageTranslate($package['name'], $package['treoId']),
                        'description' => $this->packageTranslate($package['description'], '-'),
                        'status'      => $package['status'],
                        'versions'    => $package['versions']
                    ];
                }
            }

            // prepare total
            $result['total'] = count($result['list']);
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getPackages(): array
    {
        if (is_null($this->packages)) {
            // refresh cache if it needs
            if (!file_exists($this->cache)) {
                $this->refresh();
            }

            // prepare result
            $this->packages = [];
            if (file_exists($this->cache)) {
                $this->packages = Json::decode(file_get_contents($this->cache), true);
            }
        }

        return $this->packages;
    }

    /**
     * Get current module package
     *
     * @param string $module
     *
     * @return array
     */
    public function getPackage(string $module): array
    {
        foreach ($this->getPackages() as $package) {
            if ($module == $package['treoId']) {
                return $package;
            }
        }

        return [];
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

        foreach ($this->getModules() as $id) {
            // get notified version
            $version = (isset($notifiedVersions[$id])) ? $notifiedVersions[$id] : null;

            // get package
            $package = $this->getPackage($id);

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
     * @param array $package
     */
    protected function sendNotification(array $package): void
    {
        if (!empty($users = $this->getEntityManager()->getRepository('User')->getAdminUsers())) {
            // prepare id
            $id = $package['treoId'];

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
                                    'moduleName'    => $this->packageTranslate($package['name'], $id),
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

    /**
     * @return array
     */
    protected function getComposerDiff(): array
    {
        return $this->getContainer()->get('serviceFactory')->create('Composer')->getComposerDiff();
    }

    /**
     * @return array
     */
    protected function getModuleList(): array
    {
        return $this->getContainer()->get('metadata')->getModuleList();
    }

    /**
     * @return array
     */
    protected function getInstalled()
    {
        return array_merge($this->getModuleList(), array_column($this->getComposerDiff()['install'], 'id'));
    }

    /**
     * @param array  $field
     * @param string $default
     *
     * @return string
     */
    protected function packageTranslate(array $field, string $default = ''): string
    {
        // get current language
        $currentLang = $this->getContainer()->get('language')->getLanguage();

        $result = $default;
        if (!empty($field[$currentLang])) {
            $result = $field[$currentLang];
        } elseif ($field['default']) {
            $result = $field['default'];
        }

        return $result;
    }

    /**
     * @return string
     */
    protected function getUrl(): string
    {
        if (is_null($this->url)) {
            // get composer.json
            $json = file_get_contents('composer.json');

            $this->url = Json::decode($json, true)['repositories'][0]['url'] . '/api/v1/';
        }

        return $this->url;
    }

    /**
     * @return array
     */
    protected function getModules(): array
    {
        return $this->getContainer()->get('metadata')->getModuleList();
    }

    /**
     * @param string $id
     *
     * @return array
     */
    protected function getModule(string $id): array
    {
        return $this->getContainer()->get('metadata')->getModule($id);
    }
}
