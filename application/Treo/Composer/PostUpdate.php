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
     * Update modules load order
     */
    public static function updateLoadOrder(): void
    {
        // prepare path
        $path = self::MODULE_ORDER;

        // delete old
        if (file_exists($path)) {
            unlink($path);
        }

        // prepare modules dir path
        $modulesPath = "application/Espo/Modules";

        // prepare data
        $data = [];
        if (file_exists($modulesPath) && is_dir($modulesPath) && !empty($modules = scandir($modulesPath))) {
            foreach ($modules as $module) {
                if (!empty($order = self::createModuleLoadOrder($module))) {
                    $data[$module] = [
                        'order' => $order
                    ];
                }
            }
        }

        if (!empty($data)) {
            // create dir
            if (!file_exists('custom/Espo/Custom/Resources')) {
                mkdir('custom/Espo/Custom/Resources', 0777, true);
            }

            file_put_contents($path, json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        }
    }


    /**
     * Get module requireds
     *
     * @param string $moduleId
     *
     * @return array
     */
    public static function getModuleRequireds(string $moduleId): array
    {
        // prepare result
        $result = [];

        if (!file_exists("composer.lock")) {
            return $result;
        }

        foreach (json_decode(file_get_contents("composer.lock"), true) as $package) {
            if (!empty($package['extra']['treoId'])
                && $moduleId == $package['extra']['treoId']
                && !empty($package['require'])
                && is_array($package['require'])) {
                // get treo modules
                $treoModule = Mover::getModules();

                foreach ($package['require'] as $key => $version) {
                    if (preg_match_all("/^(" . Mover::TREODIR . "\/)(.*)$/", $key, $matches)) {
                        if (!empty($matches[2][0])) {
                            $result[] = array_flip($treoModule)[$matches[2][0]];
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Run
     */
    public function run(): void
    {
        if ($this->isInstalled()) {
            // delete modules
            $this->deleteModules();

            // rebuild
            $this->rebuild();

            // loggout all users
            $this->logoutAll();

            // save stable-composer.json file
            $this->saveStableComposerJson();

            // run migrations
            $this->runMigrations();

            // drop cache
            $this->clearCache();

            // init events
            $this->initEvents();
        }
    }

    /**
     * Delete modules
     */
    protected function deleteModules(): void
    {
        if (!empty($composerDiff = $this->getComposerLockDiff()) && !empty($composerDiff['delete'])) {
            foreach ($composerDiff['delete'] as $row) {
                Mover::delete([$row['id'] => $row['package']]);
            }
            self::updateLoadOrder();
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

    /**
     * Init events
     */
    protected function initEvents(): void
    {
        // get diff
        $composerDiff = $this->getComposerLockDiff();

        if (!empty($composerDiff['install'])) {
            $this->triggered('Composer', 'afterInstallModule', $composerDiff['install']);
        }
        if (!empty($composerDiff['update'])) {
            $this->triggered('Composer', 'afterUpdateModule', $composerDiff['update']);
        }
        if (!empty($composerDiff['delete'])) {
            $this->triggered('Composer', 'afterDeleteModule', $composerDiff['delete']);
        }
    }

    /**
     * Triggered event
     *
     * @param string $target
     * @param string $action
     * @param array  $data
     *
     * @return bool
     */
    protected function triggered(string $target, string $action, array $data = []): bool
    {
        // prepare load order file path
        $path = self::MODULE_ORDER;
        if (!file_exists($path)) {
            return false;
        }

        // prepare cli php path
        $phpPath = 'data/cli-php.txt';
        if (!file_exists($phpPath)) {
            return false;
        }

        // prepare php
        $php = trim(file_get_contents($phpPath));

        // prepare ids
        $ids = array_column($data, 'id');

        // sorting
        foreach (json_decode(file_get_contents($path), true) as $id => $row) {
            if (in_array($id, $ids)) {
                // prepare command
                $command = "$php console.php events --call $target $action " . json_encode(['id' => $id]);

                // call event in separate process
                exec(str_replace('"', '\"', $command));
            }
        }

        return true;
    }

    protected static function createModuleLoadOrder(string $moduleId): int
    {
        // prepare path
        $path = "application/Espo/Modules/$moduleId/Resources/module.json";

        if (!file_exists($path)) {
            return 0;
        }

        // get default order
        $order = (int)json_decode(file_get_contents($path))->order;

        if (empty($order)) {
            $order = 10;
        }

        if (!empty($requireds = self::getModuleRequireds($moduleId))) {
            foreach ($requireds as $require) {
                $requireMax = self::createModuleLoadOrder($require);
                if ($requireMax > $order) {
                    $order = $requireMax;
                }
            }
            $order = $order + 10;
        }

        return $order;
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
}
