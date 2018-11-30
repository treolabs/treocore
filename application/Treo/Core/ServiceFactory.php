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

namespace Treo\Core;

use Espo\Core\Utils\Util;
use Espo\Core\Exceptions\Error;

/**
 * ServiceFactory class
 *
 * @author r.ratsun@zinitsolutions.com
 */
class ServiceFactory extends \Espo\Core\ServiceFactory
{
    /**
     * @var array
     */
    protected $services = [];

    /**
     * @inheritdoc
     */
    public function __construct(...$args)
    {
        // call parent
        parent::__construct(...$args);

        // load all services
        $this->load();
    }

    /**
     * @inheritdoc
     */
    public function checkExists($name)
    {
        return !empty($this->services[Util::normilizeClassName($name)]);
    }

    /**
     * @inheritdoc
     */
    public function create($name)
    {
        // prepare name
        $name = Util::normilizeClassName($name);

        if (!isset($this->services[$name])) {
            throw new Error("Service '{$name}' was not found.");
        }

        return $this->createByClassName($this->services[$name]);
    }

    /**
     * Create by classname
     *
     * @param $className
     *
     * @return mixed
     */
    protected function createByClassName($className)
    {
        if (class_exists($className)) {
            // create service
            $service = (new $className())->setContainer($this->getContainer());

            // for old services
            if (method_exists($service, 'setInjections')) {
                $service->setInjections();
            }

            return $service;
        }

        throw new Error("Class '$className' does not exist.");
    }

    /**
     * Load all services
     */
    protected function load(): void
    {
        // load Espo
        if (!empty($data = $this->getDirServices('application/Espo/Services'))) {
            foreach ($data as $name) {
                $this->services[$name] = "\\Espo\\Services\\$name";
            }
        }

        // load Treo
        if (!empty($data = $this->getDirServices('application/Treo/Services'))) {
            foreach ($data as $name) {
                $this->services[$name] = "\\Treo\\Services\\$name";
            }
        }

        // load Modules
        foreach ($this->getContainer()->get('metadata')->getModuleList() as $module) {
            if (!empty($data = $this->getDirServices("application/Espo/Modules/$module/Services"))) {
                foreach ($data as $name) {
                    $this->services[$name] = "\\Espo\\Modules\\$module\\Services\\$name";
                }
            }
        }

        // load Custom
        if (!empty($data = $this->getDirServices('custom/Espo/Custom/Services'))) {
            foreach ($data as $name) {
                $this->services[$name] = "\\Espo\\Custom\\Services\\$name";
            }
        }
    }

    /**
     * Get services from DIR
     *
     * @return array
     */
    protected function getDirServices(string $path): array
    {
        // prepare result
        $result = [];

        if (is_dir($path)) {
            foreach (scandir($path) as $item) {
                if (preg_match_all('/^(.*)\.php$/', $item, $matches)) {
                    $result[] = $matches[1][0];
                }
            }
        }

        return $result;
    }
}
