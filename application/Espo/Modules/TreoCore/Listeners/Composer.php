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
use Espo\Modules\TreoCore\Services\ComposerModule as ComposerModuleService;

/**
 * Composer listener
 *
 * @author r.ratsun@zinitsolutions.com
 */
class Composer extends AbstractListener
{
    /**
     * @param array $data
     *
     * @return array
     */
    public function beforeComposerUpdate(array $data): array
    {
        // prepare diff
        $_SESSION['composerDiff'] = $this
            ->getComposerService()
            ->getComposerDiff();

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
            $this->pushToStream('composerUpdate', $data);

            if (isset($data['status']) && $data['status'] === 0) {
                // save stable-composer.json file
                $this->getComposerService()->saveComposerJson();

                // get composer diff
                $composerDiff = $_SESSION['composerDiff'];

                // for install module
                if (!empty($composerDiff['install'])) {
                    foreach ($composerDiff['install'] as $row) {
                        // notify
                        $this->notifyInstall($row['id']);
                    }
                }

                // for updated modules
                if (!empty($composerDiff['update'])) {
                    foreach ($composerDiff['update'] as $row) {
                        // notify
                        $this->notifyUpdate($row['id'], $row['from']);
                    }
                }

                // for deleted modules
                if (!empty($composerDiff['delete'])) {
                    foreach ($composerDiff['delete'] as $row) {
                        // clear module activation and sort order data
                        $this->clearModuleData($row['id']);

                        // delete dir
                        ComposerService::deleteTreoModule([$row['id'] => $row['package']]);

                        // notify
                        $this->notifyDelete($row['id']);
                    }
                }

                // drop cache
                $this->getContainer()->get('dataManager')->clearCache();
            }
        }

        return $data;
    }

    /**
     * Notify about install
     *
     * @param string $id
     */
    protected function notifyInstall(string $id)
    {
        // get package
        $package = $this
            ->getComposerModuleService()
            ->getModulePackage($id);

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
        $this->pushToStream('installModule', ['package' => $package]);
    }


    /**
     * Notify about update
     *
     * @param string $id
     * @param string $from
     */
    protected function notifyUpdate(string $id, string $from)
    {
        // get package
        $package = $this
            ->getComposerModuleService()
            ->getModulePackage($id);

        // prepare data
        $name = $this->getModuleName($package);
        $from = str_replace('v', '', $from);
        $to = str_replace('v', '', $package['version']);

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
        $this->pushToStream('updateModule', ['package' => $package]);

        // run migration
        $this->getContainer()->get('migration')->run($id, $from, $to);
    }


    /**
     * Notify about delete
     *
     * @param string $id
     */
    protected function notifyDelete(string $id)
    {
        // get package
        $package = $this
            ->getComposerModuleService()
            ->getModulePackage($id);

        // get module name
        $moduleName = $this->getModuleName($package);

        // prepare message
        $message = "Module '<strong>%s</strong>' deleted successfully.";
        $message .= " <a href=\"/#ModuleManager/list\">Details</a>";
        $message = sprintf($this->translate($message), $moduleName);

        /**
         * Notify users
         */
        $this->notify($message);

        /**
         * Stream push
         */
        $this->pushToStream('deleteModule', ['package' => $package]);
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
     */
    protected function pushToStream(string $type, array $data): void
    {
        $note = $this->getEntityManager()->getEntity('Note');
        $note->set('type', $type);
        $note->set('parentType', 'ModuleManager');
        $note->set('data', $data);

        $this->getEntityManager()->saveEntity($note);
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
     * Get ComposerModule service
     *
     * @return ComposerModuleService
     */
    protected function getComposerModuleService(): ComposerModuleService
    {
        return $this->getService('ComposerModule');
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
