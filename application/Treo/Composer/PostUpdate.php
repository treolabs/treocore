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
class PostUpdate extends AbstractComposer
{
    /**
     * Run
     */
    public static function run(): void
    {
        // relocate files
        self::relocateFiles();

        // rebuild
        self::rebuild();

        // loggout all users
        self::logoutAll();

        // update module file for load order
        self::updateModulesLoadOrder();

        // save stable-composer.json file
        self::filePutContents('data/stable-composer.json', file_get_contents('data/composer.json'));

        // run migrations
        self::runMigrations();

        // delete modules
        self::deleteModules();

        // drop cache
        self::clearCache();
    }

    /**
     * Logout all
     */
    protected static function logoutAll(): void
    {
        $sth = self::app()
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
    protected static function updateModulesLoadOrder(): bool
    {
        return self::app()
            ->getContainer()
            ->get('serviceFactory')
            ->create('ModuleManager')
            ->updateLoadOrder();
    }

    /**
     * Get composer.lock diff
     *
     * @return array
     */
    protected static function getComposerLockDiff(): array
    {
        // prepare result
        $result = [
            'install' => [],
            'update'  => [],
            'delete'  => [],
        ];

        // prepare data
        $oldData = self::getComposerLockTreoPackages("data/old-composer.lock");
        $newData = self::getComposerLockTreoPackages("composer.lock");

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
    protected static function getComposerLockTreoPackages(string $path): array
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
     * Run migrations
     */
    protected static function runMigrations(): void
    {
        if (!empty($composerDiff = self::getComposerLockDiff()) && !empty($composerDiff['update'])) {
            foreach ($composerDiff['update'] as $row) {
                // get package
                $package = self::app()
                    ->getContainer()
                    ->get('metadata')
                    ->getModule($row['id']);

                // prepare data
                $from = Metadata::prepareVersion($row['from']);
                $to = Metadata::prepareVersion($package['version']);

                // run migration
                self::app()->getContainer()->get('migration')->run($row['id'], $from, $to);
            }
        }
    }

    /**
     * Delete modules
     */
    protected static function deleteModules(): void
    {
        if (!empty($composerDiff = self::getComposerLockDiff()) && !empty($composerDiff['delete'])) {
            // create service
            $service = self::app()->getContainer()->get('serviceFactory')->create('ModuleManager');
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
    protected static function clearCache(): void
    {
        self::app()->getContainer()->get('dataManager')->clearCache();
    }
}
