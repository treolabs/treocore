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

use Espo\Core\Utils\Util;
use Espo\Core\Utils\Json;
use Espo\Core\Exceptions\NotFound;
use Slim\Http\Request;
use StdClass;
use Treo\Traits\ContainerTrait;

/**
 * ControllerManager class
 *
 * @author r.ratsun@zinitsolutions.com
 */
class ControllerManager
{
    use ContainerTrait;

    /**
     * Precess
     *
     * @param string      $controllerName
     * @param string      $actionName
     * @param array       $params
     * @param mixed       $data
     * @param Request     $request
     * @param object|null $response
     *
     * @return string
     * @throws NotFound
     */
    public function process($controllerName, $actionName, $params, $data, $request, $response = null)
    {
        // normilizeClassName
        $className = Util::normilizeClassName($controllerName);

        // find controller classname
        $controllerClassName = "\\Espo\\Custom\\Controllers\\$className";
        if (!class_exists($controllerClassName)) {
            // get module name
            $moduleName = $this
                ->getContainer()
                ->get('metadata')
                ->getScopeModuleName($controllerName);

            if ($moduleName) {
                $controllerClassName = "\\Espo\\Modules\\$moduleName\\Controllers\\$className";
            }
        }
        if (!class_exists($controllerClassName)) {
            $controllerClassName = "\\Treo\\Controllers\\$className";
        }
        if (!class_exists($controllerClassName)) {
            $controllerClassName = "\\Espo\\Controllers\\$className";
        }
        if (!class_exists($controllerClassName)) {
            throw new NotFound("Controller '$controllerName' is not found");
        }

        if ($data && stristr($request->getContentType(), 'application/json')) {
            $data = json_decode($data);
        }

        $controller = new $controllerClassName($this->getContainer(), $request->getMethod());

        if ($actionName == 'index') {
            $actionName = $controllerClassName::$defaultAction;
        }

        $actionNameUcfirst = ucfirst($actionName);

        $beforeMethodName = 'before' . $actionNameUcfirst;
        $actionMethodName = 'action' . $actionNameUcfirst;
        $afterMethodName = 'after' . $actionNameUcfirst;

        $fullActionMethodName = strtolower($request->getMethod()) . ucfirst($actionMethodName);

        if (method_exists($controller, $fullActionMethodName)) {
            $primaryActionMethodName = $fullActionMethodName;
        } else {
            $primaryActionMethodName = $actionMethodName;
        }

        if (!method_exists($controller, $primaryActionMethodName)) {
            throw new NotFound(
                "Action '$actionName' (" . $request->getMethod() .
                ") does not exist in controller '$controllerName'"
            );
        }

        if (method_exists($controller, $beforeMethodName)) {
            $controller->$beforeMethodName($params, $data, $request, $response);
        }

        // triggered before action
        $event = $this->triggered(
            $controllerName,
            'before' . ucfirst($primaryActionMethodName),
            [
                'params'  => $params,
                'data'    => $data,
                'request' => $request,
            ]
        );

        // prepare input data
        $params = (isset($event['params'])) ? $event['params'] : $params;
        $data = (isset($event['data'])) ? $event['data'] : $data;
        $request = (isset($event['request'])) ? $event['request'] : $request;

        $result = $controller->$primaryActionMethodName($params, $data, $request, $response);

        // triggered after action
        $event = $this->triggered(
            $controllerName,
            'after' . ucfirst($primaryActionMethodName),
            [
                'params'  => $params,
                'data'    => $data,
                'request' => $request,
                'result'  => $result
            ]
        );

        // prepare result
        $result = (isset($event['result'])) ? $event['result'] : $result;

        if (method_exists($controller, $afterMethodName)) {
            $controller->$afterMethodName($params, $data, $request, $response);
        }

        if (is_array($result) || is_bool($result) || $result instanceof StdClass) {
            return Json::encode($result);
        }

        return $result;
    }

    /**
     * Triggered event
     *
     * @param string $target
     * @param string $action
     * @param array  $data
     *
     * @return array
     */
    protected function triggered(string $target, string $action, array $data = []): array
    {
        return $this
            ->getContainer()
            ->get('eventManager')
            ->triggered($target, $action, $data);
    }
}
