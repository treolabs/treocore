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

namespace Treo\Core\Migration;

/**
 * Migration
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Migration
{
    use \Treo\Traits\ContainerTrait;

    const CORE_NAME = 'TreoCore';

    /**
     * Migrate action
     *
     * @param string $module
     * @param string $from
     * @param string $to
     */
    public function run(string $module, string $from, string $to): void
    {
        // get module migration versions
        $migrations = $this->getModuleMigrationVersions($module);

        if (empty($migrations)) {
            return;
        }

        // prepare versions
        $from = $this->prepareVersion($from);
        $to = $this->prepareVersion($to);

        // prepare data
        $data = $migrations;
        $data[] = $from;
        $data[] = $to;
        $data = array_unique($data);

        // sort
        natsort($data);

        $data = array_values($data);

        // prepare keys
        $keyFrom = array_search($from, $data);
        $keyTo = array_search($to, $data);

        if ($keyFrom == $keyTo) {
            return;
        }

        // prepare increment
        if ($keyFrom < $keyTo) {
            $method = 'up';
        } else {
            $method = 'down';

            $data = array_reverse($data);
        }

        $isAllowed = false;
        foreach ($data as $className) {
            if ($from == $className) {
                $isAllowed = true;
            }

            if ($from != $className && $isAllowed && in_array($className, $migrations)) {
                // prepare class name
                if ($module == self::CORE_NAME) {
                    $className = sprintf('Treo\Migration\%s', $className);
                } else {
                    $className = sprintf('Espo\Modules\%s\Migration\%s', $module, $className);
                }

                $class = new $className();
                if ($class instanceof AbstractMigration) {
                    $class->setContainer($this->getContainer());
                    $class->{$method}();
                }
            }

            if ($to == $className) {
                $isAllowed = false;
            }
        }
    }

    /**
     * Prepare version
     *
     * @param string $version
     *
     * @return int
     */
    protected function prepareVersion(string $version)
    {
        // prepare version
        $version = str_replace('v', '', $version);

        if (preg_match_all('/^(.*)\.(.*)\.(.*)$/', $version, $matches)) {
            // prepare data
            $major = (int)$matches[1][0];
            $version = (int)$matches[2][0];
            $patch = (int)$matches[3][0];

            return "V{$major}Dot{$version}Dot{$patch}";
        }
    }

    /**
     * Get module migration versions
     *
     * @param string $module
     *
     * @return array
     */
    protected function getModuleMigrationVersions(string $module): array
    {
        // prepare result
        $result = [];

        // prepare path
        if ($module == self::CORE_NAME) {
            $path = 'application/Treo/Migration/';
        } else {
            $path = sprintf('application/Espo/Modules/%s/Migration/', $module);
        }

        if (file_exists($path) && is_dir($path)) {
            foreach (scandir($path) as $file) {
                // prepare file name
                $file = str_replace('.php', '', $file);
                if (preg_match('/^V(.*)Dot(.*)Dot(.*)$/', $file)) {
                    $result[] = $file;
                }
            }
        }

        return $result;
    }
}
