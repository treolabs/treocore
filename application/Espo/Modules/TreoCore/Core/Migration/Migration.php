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

namespace Espo\Modules\TreoCore\Core\Migration;

use Espo\Modules\TreoCore\Traits\ContainerTrait;

/**
 * Migration
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Migration
{
    use ContainerTrait;

    /**
     * Path to migration classes
     *
     * @var string
     */
    protected $path = 'application/Espo/Modules/%s/Migration/';

    /**
     * Namespace for migration classes
     *
     * @var string
     */
    protected $namespace = 'Espo\Modules\%s\Migration\%s';

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

        if ($from == $to) {
            return;
        }

        // prepare increment
        if ($from < $to) {
            $inc = 1;
            $method = 'up';
        } else {
            $inc = -1;
            $method = 'down';
        }

        // prepare current
        $current = $from + 1;

        while ($current != $to + 1) {
            if (in_array($current, $migrations)) {
                // prepare class name
                $className = sprintf($this->namespace, $module, "V{$current}");

                $class = new $className();
                if ($class instanceof AbstractMigration) {
                    $class->setContainer($this->getContainer());
                    $class->{$method}();
                }
            }

            // change current
            $current = $current + $inc;
        }
    }

    /**
     * Prepare version
     *
     * @param string $version
     *
     * @return int
     */
    protected function prepareVersion(string $version): int
    {
        return (int)str_replace(['v', '.', 'php', 'V'], ['', '', '', ''], $version);
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

        if (!empty($files = scandir(sprintf($this->path, $module)))) {
            foreach ($files as $file) {
                if (!in_array($file, ['.', '..'])) {
                    $result[] = $this->prepareVersion($file);
                }
            }
        }

        return $result;
    }
}
