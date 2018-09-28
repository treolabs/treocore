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

use Espo\Core\CronManager;
use Treo\Core\UpgradeManager;
use Treo\Core\Utils\ModuleMover;
use Treo\Core\Migration\Migration;

/**
 * Service TreoUpgrade
 *
 * @author r.ratsun r.ratsun@zinitsolutions.com
 */
class TreoUpgrade extends AbstractService
{

    const TREO_PACKAGES_URL = 'http://treo-packages.zinit1.com/api/v1/Packages/';
    const TREO_PACKAGES_PATH = 'data/upload/upgrades';

    /**
     * @var null|array
     */
    protected $versionData = null;

    /**
     * Get available version
     *
     * @return string|null
     */
    public function getAvailableVersion(): ?string
    {
        // prepare result
        $result = null;

        if (!empty($data = $this->getVersionData($this->getConfig()->get('version')))
            && !empty($data['version'])) {
            $result = (string)$data['version'];
        }

        return $result;
    }

    /**
     * Create upgrade job
     *
     * @return bool
     */
    public function createUpgradeJob(): bool
    {
        // prepare result
        $result = false;

        // get current version
        $currentVersion = $this->getConfig()->get('version');

        if (!empty($data = $this->getVersionData($currentVersion))
            && !empty($link = $data['link'])
            && !empty($version = $data['version'])) {
            // clearing cache
            if (file_exists(self::TREO_PACKAGES_PATH)) {
                ModuleMover::deleteDir(self::TREO_PACKAGES_PATH);
            }

            // create upgrade dir
            mkdir(self::TREO_PACKAGES_PATH, 0777, true);

            // prepare name
            $name = str_replace(".", "_", "{$currentVersion}_to_{$version}");

            // prepare extract dir
            $extractDir = self::TREO_PACKAGES_PATH . "/{$name}";

            // prepare zip name
            $zipName = self::TREO_PACKAGES_PATH . "/{$name}.zip";

            // download
            file_put_contents($zipName, fopen($link, 'r'));

            // create extract dir
            mkdir($extractDir, 0777, true);

            $zip = new \ZipArchive();
            $res = $zip->open($zipName);
            if ($res === true) {
                $zip->extractTo($extractDir);
                $zip->close();

                // delete archive
                unlink($zipName);
            }

            // update config
            $this->getConfig()->set('isSystemUpdating', true);
            $this->getConfig()->save();

            // create job
            $jobEntity = $this->getEntityManager()->getEntity('Job');
            $jobEntity->set(
                [
                    'name'        => 'Run TreoCore upgrade',
                    'status'      => CronManager::PENDING,
                    'executeTime' => (new \DateTime())->format('Y-m-d H:i:s'),
                    'serviceName' => 'TreoUpgrade',
                    'methodName'  => 'runUpgradeJob',
                    'data'        => [
                        'versionFrom' => $currentVersion,
                        'versionTo'   => $version,
                        'fileName'    => $name,
                        'createdById' => $this->getUser()->get('id')
                    ]
                ]
            );
            $this->getEntityManager()->saveEntity($jobEntity);

            // prepare result
            $result = true;
        }

        return $result;
    }

    /**
     * Run upgrade core job
     *
     * @param array $data
     */
    public function runUpgradeJob(array $data): void
    {
        if (!empty($versionFrom = $data['versionFrom'])
            && !empty($versionTo = $data['versionTo'])
            && !empty($fileName = $data['fileName'])) {
            // upgrade treocore
            $upgradeManager = new UpgradeManager($this->getContainer());
            $upgradeManager->install(['id' => $fileName]);

            // call migration
            $this
                ->getContainer()
                ->get('migration')
                ->run(Migration::CORE_NAME, $data['versionFrom'], $data['versionTo']);
        }
    }

    /**
     * Get version data
     *
     * @param string $version
     *
     * @return array
     */
    protected function getVersionData(string $version): array
    {
        if (is_null($this->versionData)) {
            // prepare result
            $this->versionData = [];

            // prepare path
            $path = self::TREO_PACKAGES_URL . $version;

            if ($this->getConfig()->get('allowUnstable')) {
                $path .= '?dev=1';
            }

            try {
                $json = file_get_contents($path);
                if (is_string($json)) {
                    $data = json_decode($json, true);
                }
            } catch (\Exception $e) {
            }

            if (!empty($data) && is_array($data)) {
                $item = array_pop($data);
                if (is_array($item)) {
                    $this->versionData = $item;
                }
            }
        }

        return $this->versionData;
    }
}
