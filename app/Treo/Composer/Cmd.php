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

use Composer\Installer\PackageEvent;
use Treo\Core\Application;
use Treo\Core\Container;

/**
 * Class Cmd
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Cmd
{
    /**
     * After update
     */
    public static function postUpdate(): void
    {
        // define path to core app
        if (!defined('CORE_PATH')) {
            define('CORE_PATH', dirname(dirname(__DIR__)));
        }

        (new PostUpdate())->setContainer(self::getContainer())->run();
    }

    /**
     * After package install
     *
     * @param PackageEvent $event
     *
     * @return void
     */
    public static function postPackageInstall(PackageEvent $event): void
    {
        try {
            $name = $event->getOperation()->getPackage()->getName();
        } catch (\Throwable $e) {
        }

        if (isset($name)) {
            self::createPackageActionFile($name, 'install', '1');
        }
    }

    /**
     * @param PackageEvent $event
     *
     * @return void
     */
    public static function postPackageUpdate(PackageEvent $event): void
    {
        // get composer update pretty line
        $prettyLine = (string)$event->getOperation();

        preg_match_all("/^Updating (.*) \((.*)\) to (.*) \((.*)\)$/", $prettyLine, $matches);
        if (count($matches) == 5) {
            self::createPackageActionFile($matches[1][0], 'update', $matches[2][0] . '_' . $matches[4][0]);
        }
    }

    /**
     * Before package uninstall
     *
     * @param PackageEvent $event
     *
     * @return void
     */
    public static function prePackageUninstall(PackageEvent $event): void
    {
        try {
            $name = $event->getOperation()->getPackage()->getName();
        } catch (\Throwable $e) {
        }

        if (isset($name)) {
            self::createPackageActionFile($name, 'delete', '1');
        }
    }

    /**
     * @param string $name
     * @param string $dir
     * @param string $content
     *
     * @return bool
     */
    protected static function createPackageActionFile(string $name, string $dir, string $content): bool
    {
        // prepare root path
        $rootPath = dirname(dirname(dirname(dirname(dirname(dirname(__DIR__))))));

        // find composer.json file
        $file = "$rootPath/vendor/$name/composer.json";
        if (!file_exists($file)) {
            return false;
        }

        // try to parse composer.json file
        try {
            $data = json_decode(file_get_contents($file), true);
        } catch (\Throwable $e) {
            return false;
        }

        // exit if is not treo package
        if (!isset($data['extra']['treoId'])) {
            return false;
        }

        // prepare dir path
        $dirPath = "$rootPath/data/composer-diff/$dir";

        // create dir if it needs
        if (!file_exists($dirPath)) {
            mkdir($dirPath, 0755, true);
        }

        // save
        file_put_contents("$dirPath/{$data['extra']['treoId']}.txt", $content);

        return true;
    }

    /**
     * @return Container
     */
    protected static function getContainer(): Container
    {
        require_once 'vendor/autoload.php';

        return (new Application())->getContainer();
    }
}
