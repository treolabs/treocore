<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2018 TreoLabs GmbH
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
use Espo\Core\Utils\Json;
use Treo\Core\Utils\Composer as ComposerUtil;

/**
 * Composer service
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Composer extends AbstractService
{
    /**
     * @var string
     */
    protected $moduleStableComposer = 'data/stable-composer.json';

    /**
     * @var string
     */
    protected $composerLock = 'composer.lock';

    /**
     * @var string
     */
    protected $oldComposerLock = 'data/old-composer.lock';

    /**
     * @var string
     */
    protected $moduleComposer = 'data/composer.json';

    /**
     * Is system updating now ?
     *
     * @return bool
     */
    public function isSystemUpdating(): bool
    {
        $count = $this
            ->getEntityManager()
            ->getRepository('Job')
            ->where(
                [
                    'name'   => 'run-treo-update',
                    'status' => [CronManager::PENDING, CronManager::RUNNING]
                ]
            )
            ->count();

        return ($count > 0);
    }

    /**
     * Create cron job for update composer
     *
     * @return bool
     */
    public function createUpdateJob(): bool
    {
        // prepare result
        $result = false;

        if (!$this->isSystemUpdating()) {
            // create job
            $this->insertJob();

            // prepare result
            $result = true;
        }

        return $result;
    }

    /**
     * Run composer UPDATE command by CLI
     *
     * @param array $data
     *
     * @return bool
     */
    public function runUpdateJob(array $data = []): bool
    {
        // prepare result
        $result = true;

        // prepare data
        $createdById = (isset($data['createdById'])) ? $data['createdById'] : null;

        try {
            $this->runUpdate($createdById);
        } catch (\Exception $e) {
            $GLOBALS['log']->error('Composer update failed. Error Details: ' . $e->getMessage());

            // prepare result
            $result = false;
        }

        return $result;
    }

    /**
     * Run composer UPDATE command
     *
     * @param string|null $createdById
     *
     * @return array
     * @throws \Espo\Core\Exceptions\Error
     */
    public function runUpdate(string $createdById = null): array
    {
        // prepare creator user id
        if (is_null($createdById)) {
            $createdById = $this->getUser()->get('id');
        }

        // triggered before action
        $this->triggered('Composer', 'beforeComposerUpdate', []);

        // call composer
        $composer = (new ComposerUtil())->run('update');

        // rebuild
        $this->rebuild();

        if ($composer['status'] == 0) {
            // loggout all users
            $this->logoutAll();

            // update module file for load order
            $this->updateModulesLoadOrder();
        }

        // triggered after action
        $eventData = $this
            ->triggered('Composer', 'afterComposerUpdate', ['composer' => $composer, 'createdById' => $createdById]);

        return $eventData['composer'];
    }

    /**
     * Cancel changes
     */
    public function cancelChanges(): void
    {
        if (empty($this->isSystemUpdating())) {
            if (file_exists($this->moduleStableComposer)) {
                if (file_exists($this->moduleComposer)) {
                    unlink($this->moduleComposer);
                }

                // copy file
                copy($this->moduleStableComposer, $this->moduleComposer);
            }
        }
    }

    /**
     * Update composer
     *
     * @param string $package
     * @param string $version
     */
    public function update(string $package, string $version): void
    {
        // get composer.json data
        $data = $this->getModuleComposerJson();

        // prepare data
        $data['require'] = array_merge($data['require'], [$package => $version]);

        // set composer.json data
        $this->setModuleComposerJson($data);
    }

    /**
     * Delete composer
     *
     * @param string $package
     */
    public function delete(string $package): void
    {
        // get composer.json data
        $data = $this->getModuleComposerJson();

        if (isset($data['require'][$package])) {
            unset($data['require'][$package]);
        }

        // set composer.json data
        $this->setModuleComposerJson($data);
    }

    /**
     * Get modules composer.json
     *
     * @return array
     */
    public function getModuleComposerJson(): array
    {
        if (file_exists($this->moduleComposer)) {
            $result = Json::decode(file_get_contents($this->moduleComposer), true);
        } else {
            $result = ['require' => []];

            $this->setModuleComposerJson($result);
        }

        return $result;
    }

    /**
     * Get modules stable-composer.json
     *
     * @return array
     */
    public function getModuleStableComposerJson(): array
    {
        // prepare result
        $result = [];

        if (file_exists($this->moduleStableComposer)) {
            $result = Json::decode(file_get_contents($this->moduleStableComposer), true);
        }

        return $result;
    }

    /**
     * Set modules composer.json
     *
     * @param array $data
     *
     * @return void
     */
    public function setModuleComposerJson(array $data): void
    {
        $this->filePutContents($this->moduleComposer, Json::encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Save stable-composer.json file
     */
    public function saveComposerJson(): void
    {
        if (file_exists($this->moduleComposer)) {
            // delete old file
            if (file_exists($this->moduleStableComposer)) {
                unlink($this->moduleStableComposer);
            }

            // copy file
            copy($this->moduleComposer, $this->moduleStableComposer);
        }
    }

    /**
     * Storing composer.lock
     */
    public function storeComposerLock(): void
    {
        if (file_exists($this->oldComposerLock)) {
            unlink($this->oldComposerLock);
        }
        if (file_exists($this->composerLock)) {
            copy($this->composerLock, $this->oldComposerLock);
        }
    }

    /**
     * Get composer.lock diff
     *
     * @return array
     */
    public function getComposerLockDiff(): array
    {
        // prepare result
        $result = [
            'install' => [],
            'update'  => [],
            'delete'  => [],
        ];

        if (file_exists($this->oldComposerLock) && file_exists($this->composerLock)) {
            // prepare data
            $oldData = $this->getComposerLockTreoPackages($this->oldComposerLock);
            $newData = $this->getComposerLockTreoPackages($this->composerLock);

            foreach ($oldData as $package) {
                if (!isset($newData[$package['name']])) {
                    $result['delete'][] = [
                        'id'      => $package['extra']['treoId'],
                        'package' => $package
                    ];
                } elseif ($package['version'] != $newData[$package['name']]['version']) {
                    $result['update'][] = [
                        'id'      => $package['extra']['treoId'],
                        'package' => $newData[$package['name']],
                        'from'    => $package['version']
                    ];
                }
            }
            foreach ($newData as $package) {
                if (!isset($oldData[$package['name']])) {
                    $result['install'][] = [
                        'id'      => $package['extra']['treoId'],
                        'package' => $package
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Get composer diff
     *
     * @return array
     */
    public function getComposerDiff(): array
    {
        // prepare result
        $result = [
            'install' => [],
            'update'  => [],
            'delete'  => [],
        ];

        if (!file_exists($this->moduleStableComposer)) {
            // prepare data
            $data = Json::encode(['require' => []], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            $this->filePutContents($this->moduleStableComposer, $data);
        }

        // prepare data
        $composerData = $this->getModuleComposerJson();
        $composerStableData = Json::decode(file_get_contents($this->moduleStableComposer), true);

        foreach ($composerData['require'] as $package => $version) {
            if (!isset($composerStableData['require'][$package])) {
                $result['install'][] = [
                    'id'      => $this->getModuleId($package),
                    'package' => $package
                ];
            } elseif ($version != $composerStableData['require'][$package]) {
                // prepare data
                $id = $this->getModuleId($package);
                $from = $this->getModule($id)['version'];

                $result['update'][] = [
                    'id'      => $id,
                    'package' => $package,
                    'from'    => $from
                ];
            }
        }

        foreach ($composerStableData['require'] as $package => $version) {
            if (!isset($composerData['require'][$package])) {
                $result['delete'][] = [
                    'id'      => $this->getModuleId($package),
                    'package' => $package
                ];
            }
        }

        return $result;
    }

    /**
     * Update minimum stability
     *
     * @return bool
     */
    public function updateMinimumStability(): bool
    {
        // prepare result
        $result = false;

        // prepare path
        $path = 'composer.json';

        if (file_exists($path)) {
            // prepare data
            $data = Json::decode(file_get_contents($path), true);
            $data['minimum-stability'] = (!empty($this->getConfig()->get('developMode'))) ? 'rc' : 'stable';

            $this->filePutContents($path, Json::encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            // prepare result
            $result = true;
        }

        return $result;
    }

    /**
     * Get module ID
     *
     * @param string $packageId
     *
     * @return string
     */
    protected function getModuleId(string $packageId): string
    {
        // prepare result
        $result = $packageId;

        // get packages
        $packages = $this
            ->getContainer()
            ->get('serviceFactory')
            ->create('Packagist')
            ->getPackages();

        foreach ($packages as $package) {
            if ($package['packageId'] == $packageId) {
                $result = $package['treoId'];
            }
        }

        return $result;
    }

    /**
     * Get prepared composer.lock treo packages
     *
     * @param string $path
     *
     * @return array
     */
    protected function getComposerLockTreoPackages(string $path): array
    {
        // prepare result
        $result = [];

        if (file_exists($path)) {
            $data = Json::decode(file_get_contents($path), true);
            if (!empty($packages = $data['packages'])) {
                foreach ($packages as $package) {
                    if (!empty($package['extra']['treoId'])) {
                        $result[$package['name']] = $package;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Insert job to DB
     */
    protected function insertJob(): void
    {
        $jobEntity = $this->getEntityManager()->getEntity('Job');
        $jobEntity->set(
            [
                'name'        => 'run-treo-update',
                'status'      => CronManager::PENDING,
                'executeTime' => (new \DateTime())->format('Y-m-d H:i:s'),
                'serviceName' => 'Composer',
                'method'      => 'runUpdateJob',
                'data'        => ['createdById' => $this->getUser()->get('id')]
            ]
        );
        $this->getEntityManager()->saveEntity($jobEntity);
    }

    /**
     * Logout all users
     */
    protected function logoutAll(): void
    {
        $this->executeSqlQuery("UPDATE auth_token SET deleted = 1");
    }

    /**
     * Update module(s) load order
     *
     * @return bool
     */
    protected function updateModulesLoadOrder(): bool
    {
        return $this
            ->getContainer()
            ->get('serviceFactory')
            ->create('ModuleManager')
            ->updateLoadOrder();
    }

    /**
     * Get module data
     *
     * @param string $id
     *
     * @return array
     */
    protected function getModule(string $id): array
    {
        return $this->getContainer()->get('metadata')->getModule($id);
    }

    /**
     * @param      $filename
     * @param      $data
     * @param int  $flags
     * @param null $context
     *
     * @return bool|int
     */
    protected function filePutContents($filename, $data, $flags = 0, $context = null)
    {
        return file_put_contents($filename, $data, $flags, $context);
    }
}
