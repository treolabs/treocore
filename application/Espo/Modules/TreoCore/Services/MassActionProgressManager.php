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
use Espo\ORM\EntityCollection;

/**
 * MassAction ProgressManager class
 *
 * @author r.ratsun@zinitsolutions.com
 */
class MassActionProgressManager extends AbstractProgressManager implements ProgressJobInterface
{

    /**
     * Cache file path
     *
     * @var string
     */
    protected $filePath = 'data/mass_action_%s.json';

    /**
     * Push
     *
     * @param array $data
     */
    public function push(array $data): void
    {
        // prepare key
        $key = ($data['action'] == 'update') ? 'massUpdate' : 'remove';

        // prepare name
        $name = $this
            ->getInjection('language')
            ->translate($key, 'massActions', 'Global');

        // create id
        $data['fileId'] = Util::generateId();

        // set ids to file
        $this->setToFile($data['fileId'], $data['ids']);

        // delete cache
        unset($data['ids']);

        // push job
        $this
            ->getInjection('progressManager')
            ->push($data['entityType'] . '. ' . $name, 'massUpdate', $data);
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
            $records = [];
            while (count($records) < $this->getConfig()->get('massUpdateMax', 200)) {
                // prepare key
                $key = $this->getOffset() + count($records);

                // exit
                if (!isset($ids[$key])) {
                    break;
                }

                $records[] = $ids[$key];
            }

            // call mass action
            $service = $this->getServiceFactory()->create($entityType);

            if ($data['action'] == 'update') {
                $service->massUpdate($data['data'], ['ids' => $records]);
            } elseif ($data['action'] == 'delete') {
                $service->massRemove(['ids' => $records]);
            }

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
