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

namespace Treo\Core\Utils;

/**
 * Class Mover
 *
 * @author r.ratsun@treolabs.com
 */
class Mover
{
    /**
     * @var string
     */
    const TREODIR = 'treo-module';

    /**
     * Get treo modules
     *
     * @return array
     */
    public static function getModules(): array
    {
        // prepare result
        $result = [];

        // prepare path
        $path = "vendor/" . self::TREODIR . "/";

        if (file_exists($path) && is_dir($path)) {
            foreach (scandir($path) as $row) {
                $composerFile = "{$path}/{$row}/composer.json";
                if (file_exists($composerFile)) {
                    $composerData = json_decode(file_get_contents($composerFile), true);
                    if (isset($composerData['extra']['treoId'])) {
                        $result[$composerData['extra']['treoId']] = $row;
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

        // prepare path
        $path = "vendor/" . self::TREODIR;

        foreach (self::getModules() as $id => $key) {
            // prepare full path
            $fullPath = "$path/$key";

            // relocate client
            if (file_exists("$fullPath/client")) {
                self::deleteDir("client/modules/" . self::fromCamelCase($id, '-'));
                self::copyDir("$path/$key/client/modules/", "client/");
            }

            // copy event
            self::copyEvent("$fullPath/Event.php", $id);

            // copy migrations
            self::copyMigrations("$fullPath/migrations", "data/migrations/{$id}");

            // relocate api
            if (file_exists("$fullPath/application")) {
                self::deleteDir("application/Espo/Modules/{$id}");
                self::copyDir("$fullPath/application/Espo/", "application/");
            }

            // delete vendor data
            foreach (scandir("$fullPath/") as $file) {
                if (!in_array($file, ['.', '..', 'composer.json'])) {
                    self::deleteDir("$fullPath/$file");
                }
            }
        }
    }

    /**
     * Delete module
     *
     * @param array $modules
     */
    public static function delete(array $modules): void
    {
        foreach ($modules as $id => $key) {
            // delete dir from frontend
            self::deleteDir('client/modules/' . self::fromCamelCase($id, '-') . '/');

            // delete dir from backend
            self::deleteDir("application/Espo/Modules/{$id}/");
        }
    }

    /**
     * Delete directory
     *
     * @param string $dirname
     */
    public static function deleteDir(string $dirname): void
    {
        if (file_exists($dirname)) {
            exec("rm $dirname -r");
        }
    }

    /**
     * Update EspoCRM core
     */
    protected static function updateEspo()
    {
        // prepare path
        $path = "vendor/treolabs/espocore";

        if (!file_exists("$path/application")) {
            return null;
        }

        // delete backend
        if (file_exists('application/Espo')) {
            foreach (scandir('application/Espo') as $dir) {
                if (!in_array($dir, ['.', '..', 'Modules'])) {
                    self::deleteDir('application/Espo/' . $dir);
                }
            }
        }

        // delete frontend
        if (file_exists('client')) {
            foreach (scandir('client') as $dir) {
                if (!in_array($dir, ['.', '..', 'modules'])) {
                    self::deleteDir('client/' . $dir);
                }
            }
        }

        // copy app
        self::copyDir("$path/application/Espo/", 'application/');

        // copy client
        self::copyDir("$path/client/", CORE_PATH . "/");

        // delete vendor data
        foreach (scandir("$path/") as $file) {
            if (!in_array($file, ['.', '..', 'composer.json'])) {
                self::deleteDir("$path/$file");
            }
        }
    }

    /**
     * Recursively copy files from one directory to another
     *
     * @param string $src
     * @param string $dest
     */
    protected static function copyDir(string $src, string $dest): void
    {
        if (file_exists($src)) {
            exec("cp {$src} {$dest} -r");
        }
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

    /**
     * Copy package event file
     *
     * @param string $src
     * @param string $module
     */
    protected static function copyEvent(string $src, string $module)
    {
        if (file_exists($src)) {
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

    /**
     * Copy package migration files
     *
     * @param string $src
     * @param string $dest
     */
    protected static function copyMigrations(string $src, string $dest)
    {
        if (file_exists($src) && is_dir($src)) {
            // create dir
            if (!file_exists($dest)) {
                mkdir($dest);
            }

            foreach (scandir($src) as $file) {
                // skip
                if (in_array($file, ['.', '..'])) {
                    continue 1;
                }

                // delete old
                if (file_exists("$dest/$file")) {
                    unlink("$dest/$file");
                }

                // copy
                copy("$src/$file", "$dest/$file");
            }
        }
    }
}
