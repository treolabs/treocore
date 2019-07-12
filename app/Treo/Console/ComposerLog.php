<?php
/**
 * This file is part of EspoCRM and/or TreoCore.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoCore is EspoCRM-based Open Source application.
 * Copyright (C) 2017-2019 TreoLabs GmbH
 * Website: https://treolabs.com
 *
 * TreoCore as well as EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TreoCore as well as EspoCRM is distributed in the hope that it will be useful,
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
 * and "TreoCore" word.
 */

declare(strict_types=1);

namespace Treo\Console;

/**
 * Class ComposerLog
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ComposerLog extends AbstractConsole
{
    /**
     * @inheritdoc
     */
    public static function getDescription(): string
    {
        return 'Save composer log.';
    }

    /**
     * @inheritdoc
     */
    public function run(array $data): void
    {
        // prepare path
        $path = 'data/treo-composer.log';

        if (file_exists($path) && !empty($content = file_get_contents($path))) {
            // prepare status
            $status = 1;
            if (strpos($content, '{{success}}') !== false) {
                $status = 0;
            }

            // prepare content
            $content = str_replace(["{{success}}", "{{error}}"], ["", ""], $content);

            // prepare createdById
            $createdById = 'system';
            if (!empty($this->getConfig()->get('composerUser'))) {
                $createdById = $this->getConfig()->get('composerUser');
            }

            // get em
            $em = $this->getContainer()->get('entityManager');

            // prepare note
            $note = $em->getEntity('Note');
            $note->set('type', 'composerUpdate');
            $note->set('parentType', 'ModuleManager');
            $note->set('data', ['status' => $status, 'output' => $content]);
            $note->set('createdById', $createdById);

            // save
            $em->saveEntity($note, ['skipCreatedBy' => true]);

            // unset user
            $this->getConfig()->set('composerUser', null);
        }

        // unblock composer UI
        $this->getConfig()->set('isUpdating', false);

        // save config
        $this->getConfig()->save();

        self::show('Composer log saved successfully', self::SUCCESS, true);
    }
}
