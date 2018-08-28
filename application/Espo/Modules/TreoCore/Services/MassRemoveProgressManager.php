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

namespace Espo\Modules\TreoCore\Services;

use Espo\Core\Utils\Util;
use Espo\Core\Utils\Json;
use Espo\Core\ServiceFactory;

class MassRemoveProgressManager extends AbstractProgressManager implements ProgressJobInterface
{
    /**
     * Cache file path
     *
     * @var string
     */
    protected $filePath = 'data/mass_remove_%s.json';

    /**
     * Push
     *
     * @param array $data
     */
    public function push(array $data): void
    {
        // prepare name
        $name = $this
            ->getInjection('language')
            ->translate('remove', 'massActions', 'Global');

        // create id
        $data['fileId'] = Util::generateId();

        // prepare ids
        $ids = [];
        foreach ($data['collection'] as $entity) {
            $ids[] = $entity->get('id');
        }
        unset($data['collection']);

        // set ids to file
        $this->setToFile($data['fileId'], $ids);

        // push job
        $this
            ->getInjection('progressManager')
            ->push($data['entityType'] . '. ' . $name, 'massRemove', $data);
    }

    /**
     * Execute progress job
     *
     * @param array $data
     *
     * @return bool
     */
    public function executeProgressJob(array $data): bool
    {
        // set offset
        $this->setOffset($data['progressOffset']);

        // prepare data
        $data = Json::decode($data['data'], true);
        $this->setData($data);

        // prepare file id
        $fileId = $data['fileId'];

        // set status
        $this->setStatus('in_progress');

        // get file data
        $ids = $this->getDataFromFile($fileId);

        // prepare entityType
        $entityType = $data['entityType'];

        if (!empty($ids) && $this->getServiceFactory()->checkExists($entityType)) {
            // prepare max
            $max = $this->getConfig()->get('modules.massRemoveMax.default');
            if (!empty($this->getConfig()->get("modules.massRemoveMax.{$entityType}"))) {
                $max = $this->getConfig()->get("modules.massRemoveMax.{$entityType}");
            }

            $records = [];
            while (count($records) < $max) {
                // prepare key
                $key = $this->getOffset() + count($records);

                // exit
                if (!isset($ids[$key])) {
                    break;
                }

                $records[] = $ids[$key];
            }

            // get collection
            $collection = $this
                ->getEntityManager()
                ->getRepository($entityType)
                ->where(['id' => $records])
                ->find();

            // update
            $this->getServiceFactory()
                ->create($entityType)
                ->massUpdateIteration($collection, $data['data']);

            // set offset
            $this->setOffset($this->getOffset() + count($records));

            // set progress
            $this->setProgress(($key + 1) / $data['total'] * 100);

            if ($this->getOffset() == $data['total']) {
                // set status
                $this->setStatus('success');

                // set progress
                $this->setProgress(100);
            }
        }

        if (in_array($this->getStatus(), ['success', 'error'])) {
            // delete file
            $this->deleteFile($fileId);
        }

        return true;
    }

    /**
     * Init
     */
    protected function init()
    {
        parent::init();

        $this->addDependency('progressManager');
        $this->addDependency('language');
        $this->addDependency('serviceFactory');
    }

    /**
     * Set to file
     *
     * @param string $id
     * @param array  $data
     */
    protected function setToFile(string $id, array $data): void
    {
        // prepare path
        $path = sprintf($this->filePath, $id);

        // set to file
        $file = fopen($path, "w");
        fwrite($file, Json::encode($data));
        fclose($file);
    }

    /**
     * Get data from file
     *
     * @param string $id
     *
     * @return array
     */
    protected function getDataFromFile(string $id): array
    {
        // prepare result
        $result = [];

        // prepare path
        $path = sprintf($this->filePath, $id);

        if (file_exists($path)) {
            $result = Json::decode(file_get_contents($path), true);
        }

        return $result;
    }

    /**
     * Delete file
     *
     * @param string $id
     */
    protected function deleteFile(string $id): void
    {
        // prepare path
        $path = sprintf($this->filePath, $id);

        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Get ServiceFactory
     *
     * @return ServiceFactory
     */
    protected function getServiceFactory(): ServiceFactory
    {
        return $this->getInjection('serviceFactory');
    }
}