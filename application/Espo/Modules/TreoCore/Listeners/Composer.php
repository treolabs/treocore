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
    public function afterActionUpdate(array $data): array
    {
        if (!empty($res = $data['result']) && is_array($res)) {
            // push to stream
            $this->pushToStream($res);

            if (isset($res['status']) && $res['status'] === 0) {
                // save stable-composer.json file
                $this->getService('Composer')->saveComposerJson();
            }
        }

//        if ($result['status'] === 0) {
//            // run migration
//            $this->getInjection('migration')->run($id, $package['version'], $version);
//        }

        //delete
//        if ($result['status'] === 0) {
//            // prepare modules diff
//            $afterDelete = TreoComposer::getTreoModules();
//
//            // delete treo dirs
//            TreoComposer::deleteTreoModule(array_diff($beforeDelete, $afterDelete));
//
//            // clear module activation and sort order data
//            $this->clearModuleData($modules);
//
//            // drop cache
//            $this->getDataManager()->clearCache();
//
//            // triggered event
//            $eventData = ['modules' => $modules, 'composer' => $result];
//            $this->triggeredEvent('deleteModules', $eventData);
//        }

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
}
