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

use Treo\Listeners\AbstractListener;

/**
 * EventManager class
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class EventManager
{
    use \Treo\Traits\ContainerTrait;

    /**
     * @var array
     */
    private $listeners = [];

    /**
     * Dispatch an event
     *
     * @param string $target
     * @param string $action
     * @param array  $data
     *
     * @return array
     */
    public function dispatch(string $target, string $action, array $data = []): array
    {
        foreach ($this->getClassNames($target) as $className) {
            // create listener
            $listener = $this->getListener($className);

            if (method_exists($listener, $action)) {
                // call
                $result = $listener->{$action}($data);

                // check if exists result and update data
                $data = isset($result) ? $result : $data;
            }
        }

        return $data;
    }

    /**
     * Triggered an event
     *
     * @param string $target
     * @param string $action
     * @param array  $data
     *
     * @return array
     * @deprecated
     */
    public function triggered(string $target, string $action, array $data = []): array
    {
        return $this->dispatch($target, $action, $data);
    }

    /**
     * @param string $className
     *
     * @return AbstractListener
     */
    protected function getListener(string $className): AbstractListener
    {
        if (!isset($this->listeners[$className])) {
            $this->listeners[$className] = (new $className())->setContainer($this->getContainer());
        }

        return $this->listeners[$className];
    }

    /**
     * @param string $target
     *
     * @return array
     */
    protected function getClassNames(string $target): array
    {
        // prepare path
        $path = "data/cache/listeners.json";

        if (!file_exists($path)) {
            $dirs = ["Treo/Listeners"];
            foreach ($this->getContainer()->get('metadata')->getModuleList() as $module) {
                $dirs[] = "Espo/Modules/$module/Listeners";
            }

            $listeners = [];
            foreach ($dirs as $dir) {
                $dirPath = CORE_PATH . "/application/" . $dir;
                if (file_exists($dirPath) && is_dir($dirPath)) {
                    foreach (scandir($dirPath) as $file) {
                        if (!in_array($file, ['.', '..'])) {
                            // prepare name
                            $name = str_replace(".php", "", $file);
                            // prepare class name
                            $className = "\\" . str_replace("/", "\\", $dir) . "\\" . $name;
                            if (class_exists($className)) {
                                $listeners[$name][] = $className;
                            }
                        }
                    }
                }
            }

            // create dir if it needs
            if (!file_exists("data/cache")) {
                mkdir("data/cache", 0777, true);
            }

            file_put_contents($path, json_encode($listeners, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        // get data
        $data = json_decode(file_get_contents($path), true);

        return (isset($data[$target])) ? $data[$target] : [];
    }
}
