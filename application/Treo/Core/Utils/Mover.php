<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2019 TreoLabs GmbH
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

namespace Treo\Core\Utils;

/**
 * Mover util
 *
 * @author r.ratsun@zinitsolutions.com
 */
class Mover
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
    public static function getModules(): array
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
     * Update
     */
    public static function update(): void
    {
        // update espo core
        self::updateEspo();

        foreach (self::getModules() as $moduleId => $key) {
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
    public static function delete(array $modules): void
    {
        foreach ($modules as $moduleId => $key) {
            // delete dir from frontend
            self::deleteDir('client/modules/' . self::fromCamelCase($moduleId, '-') . '/');

            // delete dir from backend
            self::deleteDir("application/Espo/Modules/{$moduleId}/");
        }
    }

    /**
     * Delete directory
     *
     * @param string $dirname
     *
     * @return bool
     */
    public static function deleteDir(string $dirname): bool
    {
        if (!file_exists($dirname)) {
            return false;
        }
        if (is_dir($dirname)) {
            $dir_handle = opendir($dirname);
        }
        if (empty($dir_handle)) {
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
     * Update EspoCRM core
     */
    protected static function updateEspo()
    {
        // get espo version
        $espoVersion = json_decode(file_get_contents('composer.json'))->require->{"espocrm/espocrm"};

        $versionFile = 'data/espo-version.json';
        if (file_exists($versionFile)) {
            $version = json_decode(file_get_contents($versionFile))->version;
            if ($version == $espoVersion) {
                return null;
            }
        }

        // delete backend
        if (file_exists('application/Espo')) {
            foreach (scandir('application/Espo') as $dir) {
                if (!in_array($dir, ['.', '..', 'Modules'])) {
                    self::deleteDir('application/Espo/' . $dir);
                }
            }
            self::deleteDir('application/Espo/Modules/Crm');
        }

        // delete frontend
        if (file_exists('client')) {
            foreach (scandir('client') as $dir) {
                if (!in_array($dir, ['.', '..', 'modules'])) {
                    self::deleteDir('client/' . $dir);
                }
            }
            self::deleteDir('client/modules/crm');
        }

        // copy
        self::copyDir('vendor/espocrm/espocrm/application/Espo', 'application/Espo');
        self::copyDir('vendor/espocrm/espocrm/client', 'client');

        // set version
        file_put_contents($versionFile, json_encode(['version' => $espoVersion]));
    }

    /**
     * Update frontend
     *
     * @param string $moduleId
     */
    protected static function updateFrontend(string $moduleId): void
    {
        if (array_key_exists($moduleId, self::getModules())) {
            // prepare params
            $moduleKey = self::getModules()[$moduleId];
            $module = self::fromCamelCase($moduleId, '-');
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
        if (array_key_exists($moduleId, self::getModules())) {
            // prepare params
            $moduleKey = self::getModules()[$moduleId];
            $source = "vendor/" . self::TREODIR . "/{$moduleKey}/application/Espo/Modules/{$moduleId}/";
            $dest = "application/Espo/Modules/{$moduleId}/";

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
     * @param        $name
     * @param string $symbol
     *
     * @return null|string|string[]
     */
    public static function fromCamelCase($name, $symbol = '_')
    {
        if (is_array($name)) {
            foreach ($name as &$value) {
                $value = static::fromCamelCase($value, $symbol);
            }

            return $name;
        }

        $name[0] = strtolower($name[0]);
        return preg_replace_callback('/([A-Z])/', function ($matches) use ($symbol) {
            return $symbol . strtolower($matches[1]);
        }, $name);
    }
}
