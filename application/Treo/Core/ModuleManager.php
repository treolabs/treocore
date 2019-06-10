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

namespace Treo\Core;

/**
 * Class ModuleManager
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ModuleManager
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var array|null
     */
    private $modules = null;

    /**
     * Prepare version
     *
     * @param string $version
     *
     * @return string
     */
    public static function prepareVersion(string $version): string
    {
        return str_replace('v', '', $version);
    }

    /**
     * ModuleManager constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Get modules
     *
     * @return array
     */
    public function getModules(): array
    {
        if (is_null($this->modules)) {
            $this->modules = [];

            // parse data
            $data = [];
            foreach (['data/modules.json', 'data/cache/modules.json'] as $path) {
                if (file_exists($path)) {
                    $data = array_merge($data, json_decode(file_get_contents($path), true));
                }
            }

            // load modules
            if (!empty($data)) {
                foreach ($data as $module) {
                    // prepare class name
                    $className = "\\$module\\Module";
                    if (property_exists($className, 'isTreoModule')) {
                        // prepare app path
                        $appPath = dirname((new \ReflectionClass($className))->getFileName()) . '/';

                        $this->modules[$module] = new $className($module, $appPath, $this->container);
                    }
                }
            }
        }

        return $this->modules;
    }

    /**
     * Get module
     *
     * @param string $id
     *
     * @return AbstractModule|null
     */
    public function getModule(string $id): ?AbstractModule
    {
        foreach ($this->getModules() as $name => $module) {
            if ($name == $id) {
                return $module;
            }
        }

        return null;
    }
}
