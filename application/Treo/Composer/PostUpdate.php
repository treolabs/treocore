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

namespace Treo\Composer;

use Treo\Core\ModuleManager;

/**
 * Class PostUpdate
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class PostUpdate
{
    use \Treo\Traits\ContainerTrait;

    const MODULE_ORDER = 'custom/Espo/Custom/Resources/module.json';

    /**
     * Copy default config
     */
    public static function copyDefaultConfig(): void
    {
        // prepare config path
        $path = 'data/config.php';

        if (!file_exists($path)) {
            // get default data
            $data = include 'application/Treo/Configs/defaultConfig.php';

            // prepare salt
            $data['passwordSalt'] = mb_substr(md5((string)time()), 0, 9);

            // create config
            file_put_contents($path, "<?php\nreturn " . self::varExport($data) . ";\n?>");
        }
    }

    /**
     * Save stable-composer.json file
     */
    public static function saveStableComposerJson(): void
    {
        if (file_exists('data/composer.json')) {
            file_put_contents('data/stable-composer.json', file_get_contents('data/composer.json'));
        }
    }

    /**
     * Update modules list
     */
    public static function updateModulesList(): void
    {
        file_put_contents('data/modules.json', json_encode(self::getModules()));
    }

    /**
     * Copy module event class
     */
    public static function copyModuleEvent(): void
    {
        foreach (self::getModules() as $module) {
            // prepare class name
            $className = "\\" . $module . "\\Event";

            if (class_exists($className)) {
                // get src
                $src = (new \ReflectionClass($className))->getFileName();

                // prepare dest
                $dest = "data/module-manager-events/{$module}";

                // create dir
                if (!file_exists($dest)) {
                    mkdir($dest, 0777, true);
                }

                // prepare dest
                $dest .= "/Event.php";

                // delete old
                if (file_exists($dest)) {
                    unlink($dest);
                }

                // copy
                copy($src, $dest);
            }
        }
    }

    /**
     * Run
     */
    public function run(): void
    {
        if ($this->isInstalled()) {
            // logout all users
            $this->logoutAll();

            // rebuild
            $this->rebuild();

            // init events
            $this->initEvents();

            // run migrations
            $this->runMigrations();

            // drop cache
            $this->clearCache();
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
                $from = ModuleManager::prepareVersion($row['from']);
                $to = ModuleManager::prepareVersion($package['version']);

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
     * @return bool
     */
    protected function isInstalled(): bool
    {
        return !empty($this->getContainer()->get('config')->get('isInstalled'));
    }

    /**
     * Init events
     */
    protected function initEvents(): void
    {
        // get diff
        $composerDiff = $this->getComposerLockDiff();

        // call afterInstall event
        if (!empty($composerDiff['install'])) {
            foreach ($composerDiff['install'] as $row) {
                $this->callEvent($row['id'], 'afterInstall');
            }
        }

        // call afterDelete event
        if (!empty($composerDiff['delete'])) {
            foreach ($composerDiff['delete'] as $row) {
                $this->callEvent($row['id'], 'afterDelete');
            }
        }
    }

    /**
     * @param string $module
     * @param string $action
     */
    protected function callEvent(string $module, string $action): void
    {
        // prepare class name
        $className = '\\%s\\Event';

        $class = sprintf($className, $module);
        if (class_exists($class)) {
            $class = new $class();
            if ($class instanceof AbstractEvent) {
                $class->setContainer($this->getContainer())->{$action}();
            }
        }
    }

    /**
     * @param     $variable
     * @param int $level
     *
     * @return mixed|string
     */
    protected static function varExport($variable, $level = 0)
    {
        $tab = '';
        $tabElement = '    ';
        for ($i = 0; $i <= $level; $i++) {
            $tab .= $tabElement;
        }
        $prevTab = substr($tab, 0, strlen($tab) - strlen($tabElement));

        if ($variable instanceof \StdClass) {
            $result = "(object) " . self::varExport(get_object_vars($variable), $level);
        } else {
            if (is_array($variable)) {
                $array = array();
                foreach ($variable as $key => $value) {
                    $array[] = var_export($key, true) . " => " . self::varExport($value, $level + 1);
                }
                $result = "[\n" . $tab . implode(",\n" . $tab, $array) . "\n" . $prevTab . "]";
            } else {
                $result = var_export($variable, true);
            }
        }

        return $result;
    }

    /**
     * Get installed modules
     *
     * @return array
     */
    private static function getModules(): array
    {
        $modules = [];
        foreach (self::getComposerLockTreoPackages("composer.lock") as $row) {
            // prepare module name
            $moduleName = $row['extra']['treoId'];

            // prepare class name
            $className = "\\$moduleName\\Module";

            $modules[$moduleName] = $className::getLoadOrder();
        }
        asort($modules);

        return array_keys($modules);
    }
}
