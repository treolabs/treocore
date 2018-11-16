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

namespace Treo\Core;

use Treo\Listeners\AbstractCommonListener;
use Treo\Listeners\AbstractListener;

/**
 * EventManager class
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class EventManager
{
    use \Treo\Traits\ContainerTrait;

    /**
     * @var array
     */
    private $listeners = [];

    /**
     * Triggered an event
     *
     * @param string $target
     * @param string $action
     * @param array  $data
     *
     * @return array
     */
    public function triggered(string $target, string $action, array $data = []): array
    {
        // triggered common
        $data = $this->triggeredCommon($target, $action, $data);

        // prepare classes
        $classes = ["Treo\\Listeners\\$target"];
        foreach ($this->getModuleList() as $module) {
            $classes[] = "Espo\\Modules\\$module\\Listeners\\$target";
        }

        foreach ($classes as $className) {
            if (class_exists($className)) {
                if (!empty($listener = $this->getListener($className)) && method_exists($listener, $action)) {
                    // call
                    $result = $listener->{$action}($data);

                    // check if exists result and update data
                    $data = isset($result) ? $result : $data;
                }
            }
        }

        return $data;
    }

    /**
     * @param string $target
     * @param string $action
     * @param array  $data
     *
     * @return array
     */
    protected function triggeredCommon(string $target, string $action, array $data): array
    {
        // prepare classes
        $classes = ["Treo\\Listeners\\CommonListener"];
        foreach ($this->getModuleList() as $module) {
            $classes[] = "Espo\\Modules\\$module\\Listeners\\CommonListener";
        }

        foreach ($classes as $className) {
            if (class_exists($className)) {
                if (!empty($listener = $this->getListener($className)) && $listener instanceof AbstractCommonListener) {
                    // call
                    $result = $listener->commonAction($target, $action, $data);

                    // check if exists result and update data
                    $data = isset($result) ? $result : $data;
                }
            }
        }

        return $data;
    }

    /**
     * @param string $className
     *
     * @return null|AbstractListener
     */
    protected function getListener(string $className): ?AbstractListener
    {
        if (!isset($this->listeners[$className])) {
            $this->listeners[$className] = null;
            $listener = new $className();
            if ($listener instanceof AbstractListener) {
                $listener->setContainer($this->getContainer());
                $this->listeners[$className] = $listener;
            }
        }

        return $this->listeners[$className];
    }

    /**
     * @return array
     */
    protected function getModuleList(): array
    {
        return $this->getContainer()->get('metadata')->getModuleList();
    }
}
