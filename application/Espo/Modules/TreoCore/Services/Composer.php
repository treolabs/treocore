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

namespace Espo\Modules\TreoCore\Services;

use Espo\Core\Services\Base;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Util;
use Composer\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Composer service
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Composer extends Base
{
    /**
     * @var array
     */
    const SKIP = ['.', '..'];

    /**
     * @var string
     */
    const TREODIR = 'treo-module';

    /**
     * @var string
     */
    protected $extractDir = CORE_PATH . "/vendor/composer/composer-extract";

    /**
     * @var string
     */
    protected $moduleOldComposer = 'data/old-composer.json';

    /**
     * @var string
     */
    protected $moduleComposer = 'data/composer.json';

    /**
     * Construct
     */
    public function __construct(...$args)
    {
        parent::__construct(...$args);

        /**
         * Extract composer
         */
        if (!file_exists($this->extractDir . "/vendor/autoload.php") == true) {
            (new \Phar(CORE_PATH . "/composer.phar"))->extractTo($this->extractDir);
        }
    }

    /**
     * Run composer UPDATE command
     *
     * @param array $data
     *
     * @return array
     */
    public function runUpdate(array $data = []): array
    {
        return $this->run('update');
    }

    /**
     * Run composer command
     *
     * @param string $command
     *
     * @return array
     */
    public function run(string $command): array
    {
        // set memory limit for composer actions
        ini_set('memory_limit', '2048M');

        putenv("COMPOSER_HOME=" . $this->extractDir);
        require_once $this->extractDir . "/vendor/autoload.php";

        $application = new Application();
        $application->setAutoExit(false);

        $input = new StringInput("{$command} --working-dir=" . CORE_PATH);
        $output = new BufferedOutput();

        // prepare response
        $status = $application->run($input, $output);
        $output = str_replace(
            'Espo\\Modules\\TreoCore\\Services\\Composer::updateTreoModules',
            '',
            $output->fetch()
        );

        return ['status' => $status, 'output' => $output];
    }

    /**
     * Update composer
     *
     * @param string $package
     * @param string $version
     *
     * @return array
     */
    public function update(string $package, string $version): array
    {
        // get composer.json data
        $data = $this->getModuleComposerJson();

        // prepare data
        $data['require'] = array_merge($data['require'], [$package => $version]);

        // set composer.json data
        $this->setModuleComposerJson($data);

        $result = $this->runUpdate();

        if ($result['status'] != 0) {
            // revert composer.json data
            $this->revertModuleComposerJson();
        }

        return $result;
    }

    /**
     * Delete composer
     *
     * @param string $package
     *
     * @return array
     */
    public function delete(string $package): array
    {
        // get composer.json data
        $data = $this->getModuleComposerJson();

        if (isset($data['require'][$package])) {
            unset($data['require'][$package]);
        }

        // set composer.json data
        $this->setModuleComposerJson($data);

        return $this->runUpdate();
    }

    /**
     * Get auth data
     *
     * @return array
     */
    public function getAuthData(): array
    {
        // prepare result
        $result = [];

        // prepare path
        $path = $this->extractDir . '/auth.json';
        if (file_exists($path)) {
            $result = Json::decode(file_get_contents($path), true);
        }

        return $result;
    }

    /**
     * Set composer user data
     *
     * @param array $authData
     *
     * @return bool
     */
    public function setAuthData(array $authData): bool
    {
        // prepare path
        $path = $this->extractDir . '/auth.json';

        // delete old
        if (file_exists($path)) {
            unlink($path);
        }

        // create file
        $fp = fopen($path, "w");
        fwrite($fp, Json::encode($authData));
        fclose($fp);

        return true;
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
     * Set modules composer.json
     *
     * @param array $data
     *
     * @return void
     */
    public function setModuleComposerJson(array $data): void
    {
        // delete old file
        if (file_exists($this->moduleOldComposer)) {
            unlink($this->moduleOldComposer);
        }

        // copy file
        if (file_exists($this->moduleComposer)) {
            copy($this->moduleComposer, $this->moduleOldComposer);
        }

        $file = fopen($this->moduleComposer, "w");
        fwrite($file, Json::encode($data));
        fclose($file);
    }

    /**
     * Revert composer.json data
     */
    public function revertModuleComposerJson(): void
    {
        if (file_exists($this->moduleOldComposer)) {
            // delete old file
            if (file_exists($this->moduleComposer)) {
                unlink($this->moduleComposer);
            }
            // copy file
            copy($this->moduleOldComposer, $this->moduleComposer);
        }
    }

    /**
     * Get treo modules
     *
     * @return array
     */
    public static function getTreoModules(): array
    {
        // prepare result
        $result = [];

        // prepare treo crm vendor dir path
        $path = "vendor/" . self::TREODIR . "/";

        if (file_exists($path) && is_dir($path)) {
            foreach (scandir($path) as $row) {
                if (!in_array($row, self::SKIP)) {
                    $modulePath = "{$path}/{$row}/application/Espo/Modules/";
                    if (file_exists($modulePath) && is_dir($modulePath)) {
                        foreach (scandir($modulePath) as $moduleId) {
                            if (!in_array($moduleId, self::SKIP)) {
                                $result[$moduleId] = $row;
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Update treo modules
     */
    public static function updateTreoModules(): void
    {
        foreach (self::getTreoModules() as $moduleId => $key) {
            // update frontend files
            self::updateFrontend($moduleId);

            // update backend files
            self::updateBackend($moduleId);
        }
    }

    /**
     * Delete treo module
     *
     * @param array $modules
     */
    public static function deleteTreoModule(array $modules): void
    {
        foreach ($modules as $moduleId => $key) {
            // delete dir from frontend
            self::deleteDir('client/modules/' . Util::fromCamelCase($moduleId, '-') . '/');

            // delete dir from backend
            self::deleteDir("application/Espo/Modules/{$moduleId}/");
        }
    }

    /**
     * Update backend
     *
     * @param string $moduleId
     */
    protected static function updateBackend(string $moduleId): void
    {
        if (array_key_exists($moduleId, self::getTreoModules())) {
            // prepare params
            $moduleKey = self::getTreoModules()[$moduleId];
            $source = "vendor/" . self::TREODIR . "/{$moduleKey}/application/Espo/Modules/{$moduleId}/";
            $dest = "application/Espo/Modules/{$moduleId}/";

            // delete dir
            self::deleteDir($dest);

            // copy dir
            self::copyDir($source, $dest);
        }
    }

    /**
     * Update frontend
     *
     * @param string $moduleId
     */
    protected static function updateFrontend(string $moduleId): void
    {
        if (array_key_exists($moduleId, self::getTreoModules())) {
            // prepare params
            $moduleKey = self::getTreoModules()[$moduleId];
            $module = Util::fromCamelCase($moduleId, '-');
            $source = "vendor/" . self::TREODIR . "/{$moduleKey}/client/modules/{$module}/";
            $dest = "client/modules/{$module}/";

            // delete dir
            self::deleteDir($dest);

            // copy dir
            self::copyDir($source, $dest);
        }
    }

    /**
     * Recursively copy files from one directory to another
     *
     * @param string $src
     * @param string $dest
     *
     * @return bool
     */
    protected static function copyDir(string $src, string $dest): bool
    {
        if (!is_dir($src)) {
            return false;
        }

        if (!is_dir($dest)) {
            if (!mkdir($dest)) {
                return false;
            }
        }

        $i = new \DirectoryIterator($src);
        foreach ($i as $f) {
            if ($f->isFile()) {
                copy($f->getRealPath(), "$dest/" . $f->getFilename());
            } elseif (!$f->isDot() && $f->isDir()) {
                self::copyDir($f->getRealPath(), "$dest/$f");
            }
        }

        return true;
    }

    /**
     * Recursively move files from one directory to another
     *
     * @param string $src
     * @param string $dest
     *
     * @return bool
     */
    protected static function moveDir(string $src, string $dest): bool
    {
        if (!is_dir($src)) {
            return false;
        }

        if (!is_dir($dest)) {
            if (!mkdir($dest)) {
                return false;
            }
        }

        $i = new \DirectoryIterator($src);
        foreach ($i as $f) {
            if ($f->isFile()) {
                rename($f->getRealPath(), "$dest/" . $f->getFilename());
            } elseif (!$f->isDot() && $f->isDir()) {
                self::moveDir($f->getRealPath(), "$dest/$f");
                unlink($f->getRealPath());
            }
        }
        unlink($src);

        return true;
    }

    /**
     * Delete directory
     *
     * @param string $dirname
     *
     * @return bool
     */
    protected static function deleteDir(string $dirname): bool
    {
        if (!file_exists($dirname)) {
            return false;
        }

        if (is_dir($dirname)) {
            $dir_handle = opendir($dirname);
        }

        if (!$dir_handle) {
            return false;
        }

        while ($file = readdir($dir_handle)) {
            if ($file != "." && $file != "..") {
                if (!is_dir($dirname . "/" . $file)) {
                    unlink($dirname . "/" . $file);
                } else {
                    self::deleteDir($dirname . '/' . $file);
                }
            }
        }
        closedir($dir_handle);
        rmdir($dirname);

        return true;
    }
}
