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

namespace Espo\Modules\TreoCore\Core\Utils;

use Espo\Core\Utils\Util;

/**
 * ModuleMover util
 *
 * @author r.ratsun@zinitsolutions.com
 */
class ModuleMover
{
    /**
     * @var string
     */
    const TREODIR = 'treo-module';

    /**
     * @var array
     */
    const SKIP = ['.', '..'];

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
}