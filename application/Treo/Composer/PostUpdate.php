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

namespace Treo\Composer;

use Treo\Core\Utils\Metadata;
use Treo\Core\Utils\Mover;

/**
 * Class PostUpdate
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class PostUpdate
{
    use \Treo\Traits\ContainerTrait;

    /**
     * Run
     */
    public function run(): void
    {
        if ($this->isInstalled()) {
            // rebuild
            $this->rebuild();

            // loggout all users
            $this->logoutAll();

            // update module file for load order
            $this->updateModulesLoadOrder();

            // save stable-composer.json file
            $this->saveStableComposerJson();

            // run migrations
            $this->runMigrations();

            // delete modules
            $this->deleteModules();

            // drop cache
            $this->clearCache();
        }
    }

    /**
     * Delete modules
     */
    protected function deleteModules(): void
    {
        if (!empty($composerDiff = $this->getComposerLockDiff()) && !empty($composerDiff['delete'])) {
            // create service
            $service = $this->getContainer()->get('serviceFactory')->create('ModuleManager');
            foreach ($composerDiff['delete'] as $row) {
                // clear module activation and sort order data
                $service->clearModuleData($row['id']);

                // delete dir
                Mover::delete([$row['id'] => $row['package']]);
            }
        }
    }

    /**
     * Drop cache
     */
    protected function clearCache(): void
    {
        $this->getContainer()->get('dataManager')->clearCache();
    }

    /**
     * Rebuild
     */
    protected function rebuild(): void
    {
        $this
            ->getContainer()
            ->get('dataManager')
            ->rebuild();
    }

    /**
     * Logout all
     */
    protected function logoutAll(): void
    {
        $sth = $this
            ->getContainer()
            ->get('entityManager')
            ->getPDO()->prepare("UPDATE auth_token SET deleted = 1");

        $sth->execute();
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
     * Save stable-composer.json file
     */
    protected function saveStableComposerJson(): void
    {
        file_put_contents('data/stable-composer.json', file_get_contents('data/composer.json'));
    }

    /**
     * Run migrations
     */
    protected function runMigrations(): void
    {
        if (!empty($composerDiff = $this->getComposerLockDiff()) && !empty($composerDiff['update'])) {
            foreach ($composerDiff['update'] as $row) {
                // get package
                $package = $this
                    ->getContainer()
                    ->get('metadata')
                    ->getModule($row['id']);

                // prepare data
                $from = Metadata::prepareVersion($row['from']);
                $to = Metadata::prepareVersion($package['version']);

                // run migration
                $this->getContainer()->get('migration')->run($row['id'], $from, $to);
            }
        }
    }

    /**
     * Get composer.lock diff
     *
     * @return array
     */
    protected function getComposerLockDiff(): array
    {
        // prepare result
        $result = [
            'install' => [],
            'update'  => [],
            'delete'  => [],
        ];

        // prepare data
        $oldData = $this->getComposerLockTreoPackages("data/old-composer.lock");
        $newData = $this->getComposerLockTreoPackages("composer.lock");

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
            $data = json_decode(file_get_contents($path), true);
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
     * @return bool
     */
    protected function isInstalled(): bool
    {
        return !empty($this->getContainer()->get('config')->get('isInstalled'));
    }
}
