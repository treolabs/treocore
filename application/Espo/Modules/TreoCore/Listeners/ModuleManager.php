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

use Espo\Core\ORM\EntityManager;

/**
 * ModuleManager listener
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class ModuleManager extends AbstractListener
{
    /**
     * @param array $data
     */
    public function updateModuleActivation(array $data)
    {
        // get module name
        $moduleName = $this->getModuleName($data['package']);

        if (empty($data['disabled'])) {
            $template = "Module '<strong>%s</strong>' activated successfully.";
        } else {
            $template = "Module '<strong>%s</strong>' deactivated successfully.";
        }

        $message = sprintf($this->translate($template), $moduleName);

        /**
         * Notify users
         */
        $this->notify($message);

        /**
         * Stream push
         */
        $this->pushToStream('updateModuleActivation', $data);
    }

    /**
     * @param array $data
     */
    public function installModule(array $data)
    {
        if ($data['composer']['status'] === 0) {
            // get module name
            $moduleName = $this->getModuleName($data['package']);

            // prepare message
            $message = "Module '<strong>%s</strong>' (%s) installed successfully.";
            $message .= " <a href=\"/#ModuleManager/list\">Details</a>";
            $message = sprintf($this->translate($message), $moduleName, $data['version']);

            /**
             * Notify users
             */
            $this->notify($message);
        }

        /**
         * Stream push
         */
        $this->pushToStream('installModule', $data);
    }

    /**
     * @param array $data
     */
    public function updateModule(array $data)
    {
        if ($data['composer']['status'] === 0) {
            // prepare data
            $name = $this->getModuleName($data['packageTo']);
            $from = str_replace('v', '', $data['packageFrom']['version']);
            $to = str_replace('v', '', $data['packageTo']['version']);

            // prepare message
            $message = "Module '<strong>%s</strong>' updated from '%s' to '%s'.";
            $message .= " <a href=\"/#ModuleManager/list\">Details</a>";
            $message = sprintf($this->translate($message), $name, $from, $to);

            /**
             * Notify users
             */
            $this->notify($message);
        }

        /**
         * Stream push
         */
        $this->pushToStream('updateModule', $data);
    }

    /**
     * @param array $data
     */
    public function deleteModule(array $data)
    {
        if ($data['composer']['status'] === 0) {
            // get module name
            $moduleName = $this->getModuleName($data['package']);

            // prepare message
            $message = "Module '<strong>%s</strong>' deleted successfully.";
            $message .= " <a href=\"/#ModuleManager/list\">Details</a>";
            $message = sprintf($this->translate($message), $moduleName);

            /**
             * Notify users
             */
            $this->notify($message);
        }

        /**
         * Stream push
         */
        $this->pushToStream('deleteModule', $data);
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
        $note->set('parentId', 'modules');
        $note->set('parentType', 'ModuleManager');
        $note->set('data', $data);

        $this->getEntityManager()->saveEntity($note);
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
            ->getContainer()
            ->get('language')
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
            ->getContainer()
            ->get('language')
            ->translate($key, 'messages', 'ModuleManager');
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager(): EntityManager
    {
        return $this->getContainer()->get('entityManager');
    }
}
