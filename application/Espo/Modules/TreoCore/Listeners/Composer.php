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
use Espo\Core\Exceptions\Error;

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
            ->getService('Composer')
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
            $this->pushToStream($data);

            if (isset($data['status']) && $data['status'] === 0) {
                // save stable-composer.json file
                $this->getService('Composer')->saveComposerJson();

                // get composer diff
                $composerDiff = $_SESSION['composerDiff'];

                // for updated modules
                if (!empty($composerDiff['update'])) {
                    foreach ($composerDiff['update'] as $row) {
//                        // prepare data
//                        $to = '';
//
//                        // run migration
//                        $this->getContainer()
//                            ->get('migration')
//                            ->run($row['id'], $row['from'], $to);
                    }
                }

                // for deleted modules
                if (!empty($composerDiff['delete'])) {
                    foreach ($composerDiff['delete'] as $row) {
                        // clear module activation and sort order data
                        $this->clearModuleData($row['id']);

                        // delete dir
                        ComposerService::deleteTreoModule([$row['id'] => $row['package']]);
                    }
                }

                // drop cache
                $this->getContainer()->get('dataManager')->clearCache();
            }
        }

        return $data;
    }

    /**
     * Push to stream
     *
     * @param array $data
     *
     * @throws Error
     */
    protected function pushToStream(array $data): void
    {
        // create note
        $note = $this->getEntityManager()->getEntity('Note');
        $note->set('type', 'composerUpdate');
        $note->set('parentType', 'ModuleManager');
        $note->set('data', $data);

        // save note
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
}
