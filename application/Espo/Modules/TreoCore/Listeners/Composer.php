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

namespace Espo\Modules\TreoCore\Listeners;

use Espo\Modules\TreoCore\Services\Composer as ComposerService;
use Espo\Modules\TreoCore\Traits\EventTriggeredTrait;
use Treo\Core\Utils\Metadata;
use Treo\Core\Utils\ModuleMover;

/**
 * Composer listener
 *
 * @author r.ratsun@zinitsolutions.com
 */
class Composer extends AbstractListener
{
    use EventTriggeredTrait;

    /**
     * @param array $data
     *
     * @return array
     */
    public function beforeComposerUpdate(array $data): array
    {
        // storing old composer.lock
        $this->getComposerService()->storeComposerLock();

        return $data;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function afterComposerUpdate(array $data): array
    {
        if (!empty($data)) {
            // push to stream
            $this->pushToStream('composerUpdate', $data['composer'], $data['createdById']);

            if (isset($data['composer']['status']) && $data['composer']['status'] === 0) {
                // save stable-composer.json file
                $this->getComposerService()->saveComposerJson();

                // get composer diff
                $composerDiff = $this->getComposerService()->getComposerLockDiff();

                // for install module
                if (!empty($composerDiff['install'])) {
                    foreach ($composerDiff['install'] as $row) {
                        // set createdById
                        $row['createdById'] = $data['createdById'];

                        // triggered event
                        $this->triggered('Composer', 'afterInstallModule', $row);
                    }
                }

                // for updated modules
                if (!empty($composerDiff['update'])) {
                    foreach ($composerDiff['update'] as $row) {
                        // set createdById
                        $row['createdById'] = $data['createdById'];

                        // triggered event
                        $this->triggered('Composer', 'afterUpdateModule', $row);
                    }
                }

                // for deleted modules
                if (!empty($composerDiff['delete'])) {
                    foreach ($composerDiff['delete'] as $row) {
                        // clear module activation and sort order data
                        $this->clearModuleData($row['id']);

                        // delete dir
                        ModuleMover::delete([$row['id'] => $row['package']]);

                        // set createdById
                        $row['createdById'] = $data['createdById'];

                        // triggered event
                        $this->triggered('Composer', 'afterDeleteModule', $row);
                    }
                }

                // drop cache
                $this->getContainer()->get('dataManager')->clearCache();
            }
        }

        return $data;
    }

    /**
     * After install module event
     *
     * @param array $data
     *
     * @return array
     */
    public function afterInstallModule(array $data): array
    {
        $this->notifyInstall($data['id'], $data['createdById']);

        return $data;
    }

    /**
     * After update module event
     *
     * @param array $data
     *
     * @return array
     */
    public function afterUpdateModule(array $data): array
    {
        $this->notifyUpdate($data['id'], $data['from'], $data['createdById']);

        return $data;
    }

    /**
     * After delete module event
     *
     * @param array $data
     *
     * @return array
     */
    public function afterDeleteModule(array $data): array
    {
        $this->notifyDelete($data['id'], $data['createdById']);

        return $data;
    }


    /**
     * Notify about install
     *
     * @param string $id
     * @param string $createdById
     */
    protected function notifyInstall(string $id, string $createdById)
    {
        // get package
        $package = $this
            ->getContainer()
            ->get('metadata')
            ->getModule($id);

        if (!empty($package)) {
            // get module name
            $moduleName = $this->getModuleName($package);

            // prepare message
            $message = "Module '<strong>%s</strong>' (%s) installed successfully.";
            $message .= " <a href=\"/#ModuleManager/list\">Details</a>";
            $message = sprintf($this->translate($message), $moduleName, $package['version']);

            /**
             * Notify users
             */
            $this->notify($message);

            // push to stream
            $this->pushToStream('installModule', ['package' => $package], $createdById);
        }
    }


    /**
     * Notify about update
     *
     * @param string $id
     * @param string $from
     * @param string $createdById
     */
    protected function notifyUpdate(string $id, string $from, string $createdById)
    {
        // get package
        $package = $this
            ->getContainer()
            ->get('metadata')
            ->getModule($id);

        if (!empty($package)) {
            // prepare data
            $from = Metadata::prepareVersion($from);
            $to = Metadata::prepareVersion($package['version']);

            if ($from != $to) {
                // get module name
                $name = $this->getModuleName($package);

                // prepare message
                $message = "Module '<strong>%s</strong>' updated from '%s' to '%s'.";
                $message .= " <a href=\"/#ModuleManager/list\">Details</a>";
                $message = sprintf($this->translate($message), $name, $from, $to);

                /**
                 * Notify users
                 */
                $this->notify($message);

                /**
                 * Stream push
                 */
                $this->pushToStream('updateModule', ['package' => $package], $createdById);

                // run migration
                $this->getContainer()->get('migration')->run($id, $from, $to);
            }
        }
    }


    /**
     * Notify about delete
     *
     * @param string $id
     * @param string $createdById
     */
    protected function notifyDelete(string $id, string $createdById)
    {
        // get package
        $package = $this->getService('Packagist')->getPackage($id);

        if (empty($package)) {
            return;
        }

        // get current language
        $currentLang = $this
            ->getLanguage()
            ->getLanguage();

        $moduleName = $id;
        if (!empty($package['name'][$currentLang])) {
            $moduleName = $package['name'][$currentLang];
        } elseif ($package['name']['default']) {
            $moduleName = $package['name']['default'];
        }

        // prepare message
        $message = "Module '<strong>%s</strong>' deleted successfully.";
        $message .= " <a href=\"/#ModuleManager/list\">Details</a>";
        $message = sprintf($this->translate($message), $moduleName);

        /**
         * Notify users
         */
        $this->notify($message);

        // prepare stream data
        $streamData = [
            'package' => [
                'extra' => [
                    'treoId'      => $package['treoId'],
                    'name'        => $package['name'],
                    'description' => $package['description'],
                ]
            ]
        ];

        /**
         * Stream push
         */
        $this->pushToStream('deleteModule', $streamData, $createdById);
    }

    /**
     * Notify
     *
     * @param string $message
     */
    protected function notify(string $message): void
    {
        if (!empty($users = $this->getAdminUsers())) {
            foreach ($users as $user) {
                // create notification
                $notification = $this->getEntityManager()->getEntity('Notification');
                $notification->set(
                    [
                        'type'    => 'Message',
                        'userId'  => $user->get('id'),
                        'message' => $message
                    ]
                );
                $this->getEntityManager()->saveEntity($notification);
            }
        }
    }

    /**
     * Push record to stream
     *
     * @param string $type
     * @param array  $data
     * @param string $createdById
     */
    protected function pushToStream(string $type, array $data, string $createdById): void
    {
        $note = $this->getEntityManager()->getEntity('Note');
        $note->set('type', $type);
        $note->set('parentType', 'ModuleManager');
        $note->set('data', $data);
        $note->set('createdById', (empty($createdById)) ? 'system' : $createdById);

        $this->getEntityManager()->saveEntity($note, ['skipCreatedBy' => true]);
    }

    /**
     * Clear module data from "module.json" file
     *
     * @param string $id
     *
     * @return bool
     */
    protected function clearModuleData(string $id): void
    {
        $this->getService('ModuleManager')->clearModuleData($id);
    }

    /**
     * Get admin users
     *
     * @return mixed
     */
    protected function getAdminUsers()
    {
        return $this
            ->getEntityManager()
            ->getRepository('User')
            ->where(['isAdmin' => true])
            ->find();
    }

    /**
     * Get module name
     *
     * @param array $package
     *
     * @return string
     */
    protected function getModuleName(array $package): string
    {
        // get current language
        $currentLang = $this
            ->getLanguage()
            ->getLanguage();

        // prepare result
        $result = $package['extra']['id'];

        if (!empty($package['extra']['name'][$currentLang])) {
            $result = $package['extra']['name'][$currentLang];
        } elseif ($package['extra']['name']['default']) {
            $result = $package['extra']['name']['default'];
        }

        return $result;
    }

    /**
     * Translate
     *
     * @param string $key
     *
     * @return string
     */
    protected function translate(string $key): string
    {
        return $this
            ->getLanguage()
            ->translate($key, 'messages', 'ModuleManager');
    }

    /**
     * Get Composer service
     *
     * @return ComposerService
     */
    protected function getComposerService(): ComposerService
    {
        return $this->getService('Composer');
    }
}
