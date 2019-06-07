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
     * Delete directory
     *
     * @param string $dirname
     */
    protected static function deleteDir(string $dirname): void
    {
        if (file_exists($dirname)) {
            exec("rm $dirname -r");
        }
    }

    /**
     * Update EspoCRM core
     */
    public static function updateEspo()
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
}
