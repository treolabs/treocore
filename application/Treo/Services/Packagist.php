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

namespace Treo\Services;

use Espo\Core\Utils\Json;
use Treo\Core\Utils\Composer as ComposerUtil;

/**
 * Packagist service
 *
 * @author r.ratsun r.ratsun@zinitsolutions.com
 */
class Packagist extends AbstractService
{
    /**
     * @var null|array
     */
    protected $packages = null;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $cacheFile = 'data/cache/packages.json';

    /**
     * @var string
     */
    private $notificationsFile = 'data/notifications.json';

    /**
     * Package constructor
     */
    public function __construct()
    {
        // get composer.json
        $json = file_get_contents(CORE_PATH . '/composer.json');

        // set repository
        $this->url = Json::decode($json, true)['repositories'][0]['url'] . '/api/v1/';
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
        // prepare result
        $result = [];

        // find package
        foreach ($this->getPackages() as $package) {
            if ($module == $package['treoId']) {
                $result = $package;
                break;
            }
        }

        return $result;
    }

    /**
     * Get packages
     *
     * @return array
     */
    public function getPackages(): array
    {
        if (is_null($this->packages)) {
            // caching
            if (!file_exists($this->cacheFile)) {
                $this->refresh();
            }

            $this->packages = Json::decode(file_get_contents($this->cacheFile), true);
        }

        return $this->packages;
    }

    /**
     * Refresh cache for module packages
     *
     * @return bool
     */
    public function refresh(): bool
    {
        // prepare params
        $params = [
            'allowUnstable' => $this->getConfig()->get('developMode', 0),
            'token'         => $this->getToken(),
        ];

        $data = file_get_contents($this->url . "package?" . http_build_query($params));
        $file = fopen($this->cacheFile, "w");
        fwrite($file, $data);
        fclose($file);

        return true;
    }

    /**
     * Clear cache file for module packages
     *
     * @return bool
     */
    public function clearCache(): bool
    {
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }

        return true;
    }

    /**
     * Notify admin users about new version of module, or about new module
     *
     * @return bool
     */
    public function notify(): bool
    {
        // prepare file data
        $fileData = [];
        if (file_exists($this->notificationsFile)) {
            $fileData = Json::decode(file_get_contents($this->notificationsFile), true);
        }

        // get metadata
        $metadata = $this->getContainer()->get('metadata');

        // get modules
        $modules = $metadata->getModuleList();

        // checking new versions of modules
        foreach ($modules as $id) {
            if (!empty($module = $metadata->getModule($id))) {
                // get version
                $package = $this->getPackage($id);
                $version = $package['versions'][0]['version'];

                if ($version != $module['version'] && !isset($fileData[$id]['version'][$version])) {
                    $this->sendNotification(
                        [
                            'data'             => [
                                'id'              => $id,
                                'messageTemplate' => 'newModuleVersion',
                                'messageVars'     => [
                                    'moduleName'    => $this->getModuleTranslateName($package),
                                    'moduleVersion' => $version,
                                ]
                            ],
                            'preferencesField' => 'receiveNewModuleVersionNotifications',
                            'configField'      => 'notificationNewModuleVersionDisabled'
                        ]
                    );
                    $fileData[$id]['version'][$version] = 1;
                }
            }
        }

        // checking if new modules exists
        foreach ($this->getPackages() as $module) {
            if (!in_array($module['treoId'], $modules) && !isset($fileData[$id])) {
                $this->sendNotification(
                    [
                        'data'             => [
                            'id'              => $id,
                            'messageTemplate' => 'newModule',
                            'messageVars'     => [
                                'moduleName' => $this->getModuleTranslateName($module)
                            ]
                        ],
                        'preferencesField' => 'receiveNewModuleNotifications',
                        'configField'      => 'notificationNewModuleDisabled'
                    ]
                );
                $fileData[$id] = 1;
            }
        }

        // set to file
        if (!empty($fileData)) {
            $file = fopen($this->notificationsFile, "w");
            fwrite($file, Json::encode($fileData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            fclose($file);
        }

        return true;
    }

    /**
     * Send notification(s)
     *
     * @param array $data
     */
    protected function sendNotification($data): bool
    {
        // get users
        $users = $this->getEntityManager()->getRepository('User')->getAdminUsers();

        //get config
        $configNotification = $this->getConfig()->get($data['configField']);

        if (!empty($users)) {
            foreach ($users as $user) {
                $preferences = json_decode($user['data'], true);
                if ($preferences[$data['preferencesField']]
                    || (!isset($preferences[$data['preferencesField']]) && $configNotification)) {
                    // create notification
                    $notification = $this->getEntityManager()->getEntity('Notification');
                    $notification->set(
                        [
                            'type'   => 'TreoMessage',
                            'userId' => $user['id'],
                            'data'   => $data['data']
                        ]
                    );
                    $this->getEntityManager()->saveEntity($notification);
                }
            }
        }

        return true;
    }

    /**
     * Get module name
     *
     * @param array $package
     *
     * @return string
     */
    protected function getModuleTranslateName(array $package): string
    {
        // get current language
        $currentLang = $this
            ->getContainer()
            ->get('language')
            ->getLanguage();

        // prepare result
        $result = $package['treoId'];
        if (!empty($package['name'][$currentLang])) {
            $result = $package['name'][$currentLang];
        } elseif ($package['name']['default']) {
            $result = $package['name']['default'];
        }

        return $result;
    }

    /**
     * Get gitlab token
     *
     * @return null|string
     */
    protected function getToken(): ?string
    {
        // prepare token
        $token = $this->getConfig()->get('gitlabToken');

        // get token
        if (empty($token)) {
            $token = null;
            $authData = (new ComposerUtil())->getAuthData();
            if (!empty($username = $authData['username'])) {
                $token = Json::decode(file_get_contents($this->url . "gitlab-token?username=$username"), true)['token'];

                // save
                $this->getConfig()->set('gitlabToken', $token);
                $this->getConfig()->save();
            }
        }

        return $token;
    }
}
