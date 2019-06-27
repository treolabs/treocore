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

use Espo\Core\Utils\Api\Auth as ApiAuth;
use Treo\Services\Installer;
use Treo\Core\Utils\Auth;
use Treo\Core\Utils\Route;
use Treo\Core\Utils\Metadata;
use Treo\Core\Utils\Config;

/**
 * Class Application
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Application
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * Is PHP version valid ?
     */
    public static function isPhpVersionValid()
    {
        // prepare data
        $validPhpVersion = '7.1';

        // prepare PHP version
        $versionData = explode(".", phpversion());
        $phpVersion = $versionData[0] . "." . $versionData[1];

        // validate PHP version
        if (version_compare($phpVersion, $validPhpVersion, '<')) {
            echo "Invalid PHP version. PHP 7.1 or above is required!";
            die();
        }
    }

    /**
     * Application constructor.
     */
    public function __construct()
    {
        date_default_timezone_set('UTC');

        $this->initContainer();

        $GLOBALS['log'] = $this->getContainer()->get('log');
    }

    /**
     * Get container
     *
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Get slim
     *
     * @return mixed
     */
    public function getSlim()
    {
        return $this->container->get('slim');
    }

    /**
     * Get metadata
     *
     * @return Metadata
     */
    public function getMetadata(): Metadata
    {
        return $this->container->get('metadata');
    }

    /**
     * Is installed
     *
     * @return bool
     */
    public function isInstalled(): bool
    {
        // copy config if it needs
        $this->copyDefaultConfig();

        return file_exists($this->getConfig()->getConfigPath()) && $this->getConfig()->get('isInstalled');
    }

    /**
     * Run console
     *
     * @param array $argv
     */
    public function runConsole(array $argv)
    {
        // unset file path
        if (isset($argv[0])) {
            unset($argv[0]);
        }

        $this
            ->getContainer()
            ->get('consoleManager')
            ->run(implode(' ', $argv));
    }

    /**
     * Run api
     */
    public function run()
    {
        // for installer
        if (!$this->isInstalled()) {
            $this->runInstallerApi();
        }

        $this->routeHooks();
        $this->initRoutes();
        $this->getSlim()->run();
    }

    /**
     * Run client
     */
    public function runClient()
    {
        // for installer
        if (!$this->isInstalled()) {
            $this->runInstallerClient();
        }

        $this->getContainer()->get('clientManager')->display();
        exit;
    }

    /**
     * Run entry point
     *
     * @param string $entryPoint
     * @param array  $data
     * @param bool   $final
     */
    public function runEntryPoint(string $entryPoint, $data = [], $final = false)
    {
        if (empty($entryPoint)) {
            throw new \Error();
        }

        $slim = $this->getSlim();
        $container = $this->getContainer();

        $slim->any('.*', function () {
        });

        $entryPointManager = new \Espo\Core\EntryPointManager($container);

        try {
            $authRequired = $entryPointManager->checkAuthRequired($entryPoint);
            $authNotStrict = $entryPointManager->checkNotStrictAuth($entryPoint);
            if ($authRequired && !$authNotStrict) {
                if (!$final && $portalId = $this->detectedPortalId()) {
                    $app = new \Espo\Core\Portal\Application($portalId);
                    $app->setBasePath($this->getBasePath());
                    $app->runEntryPoint($entryPoint, $data, true);
                    exit;
                }
            }
            $auth = new \Espo\Core\Utils\Auth($this->container, $authNotStrict);
            $apiAuth = new \Espo\Core\Utils\Api\Auth($auth, $authRequired, true);
            $slim->add($apiAuth);

            $slim->hook('slim.before.dispatch', function () use ($entryPoint, $entryPointManager, $container, $data) {
                $entryPointManager->run($entryPoint, $data);
            });

            $slim->run();
        } catch (\Exception $e) {
            $container->get('output')->processError($e->getMessage(), $e->getCode(), true, $e);
        }
    }

    /**
     * Set base path
     *
     * @param string $basePath
     *
     * @return Application
     */
    public function setBasePath(string $basePath): Application
    {
        $this->getContainer()->get('clientManager')->setBasePath($basePath);

        return $this;
    }

    /**
     * Get base path
     *
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->getContainer()->get('clientManager')->getBasePath();
    }

    /**
     * Detect portal id
     *
     * @return mixed
     */
    public function detectedPortalId()
    {
        if (!empty($_GET['portalId'])) {
            return $_GET['portalId'];
        }
        if (!empty($_COOKIE['auth-token'])) {
            $token = $this
                ->getContainer()
                ->get('entityManager')
                ->getRepository('AuthToken')->where(['token' => $_COOKIE['auth-token']])->findOne();

            if ($token && $token->get('portalId')) {
                return $token->get('portalId');
            }
        }

        return null;
    }

    /**
     * Setup system user
     */
    public function setupSystemUser(): void
    {
        $user = $this->getContainer()->get('entityManager')->getEntity('User', 'system');
        $user->set('isAdmin', true);
        $this->getContainer()->setUser($user);
        $this->getContainer()->get('entityManager')->setUser($user);
    }

    /**
     * Print modules client files
     *
     * @param string $file
     */
    public function printModuleClientFile(string $file)
    {
        foreach (array_reverse($this->getContainer()->get('moduleManager')->getModules()) as $module) {
            $path = $module->getClientPath() . $file;
            if (file_exists($path)) {
                $parts = explode(".", $path);

                switch (array_pop($parts)) {
                    case 'css':
                        header('Content-Type: text/css');
                        break;
                    case 'js':
                        header('Content-Type: application/javascript');
                        break;
                    case 'json':
                        header('Content-Type: application/json');
                        break;
                    case 'png':
                        header('Content-Type: image/png');
                        break;
                    case 'jpeg':
                        header('Content-Type: image/jpeg');
                        break;
                    case 'jpg':
                        header('Content-Type: image/jpg');
                        break;
                    case 'gif':
                        header('Content-Type: image/gif');
                        break;
                    case 'ico':
                        header('Content-Type: image/vnd.microsoft.icon');
                        break;
                    case 'svg':
                        header('Content-type: image/svg+xml');
                        break;
                }
                echo file_get_contents($path);
                exit;
            }
        }

        // show 404
        header("HTTP/1.0 404 Not Found");
        exit;
    }

    /**
     * Init container
     */
    protected function initContainer(): void
    {
        $this->container = new Container();
    }

    /**
     * Create auth
     *
     * @return Auth
     */
    protected function createAuth()
    {
        return new Auth($this->getContainer());
    }

    /**
     * Get route list
     *
     * @return mixed
     */
    protected function getRouteList()
    {
        $routes = new Route(
            $this->getConfig(),
            $this->getMetadata(),
            $this->getContainer()->get('fileManager'),
            $this->getContainer()->get('moduleManager')
        );

        return $routes->getAll();
    }

    /**
     * Run API for installer
     */
    protected function runInstallerApi()
    {
        // prepare request
        $request = $this->getSlim()->request();

        // prepare action
        $action = str_replace("/Installer/", "", $request->getPathInfo());

        // call controller
        $result = $this
            ->getContainer()
            ->get('controllerManager')
            ->process('Installer', $action, [], $request->getBody(), $request);

        header('Content-Type: application/json');
        echo $result;
        exit;
    }

    /**
     * Run client for installer
     */
    protected function runInstallerClient()
    {
        $result = ['status' => false, 'message' => ''];

        // check permissions and generate config
        try {
            /** @var Installer $installer */
            $installer = $this->getContainer()->get('serviceFactory')->create('Installer');
            $result['status'] = $installer->checkPermissions();
            $result['status'] = $installer->generateConfig() && $result['status'];
        } catch (\Exception $e) {
            $result['status'] = 'false';
            $result['message'] = $e->getMessage();
        }

        // prepare vars
        $vars = [
            'applicationName' => 'TreoCore',
            'status'          => $result['status'],
            'message'         => $result['message']
        ];

        $this->getContainer()->get('clientManager')->display(null, 'html/installation.html', $vars);
        exit;
    }

    /**
     * Get config
     *
     * @return Config
     */
    protected function getConfig(): Config
    {
        return $this->getContainer()->get('config');
    }

    /**
     * @param $auth
     *
     * @return ApiAuth
     */
    protected function createApiAuth($auth): ApiAuth
    {
        return new ApiAuth($auth);
    }

    /**
     * Route hooks
     */
    protected function routeHooks()
    {
        $container = $this->getContainer();
        $slim = $this->getSlim();

        try {
            $auth = $this->createAuth();
        } catch (\Exception $e) {
            $container->get('output')->processError($e->getMessage(), $e->getCode(), false, $e);
        }

        $apiAuth = $this->createApiAuth($auth);

        $this->getSlim()->add($apiAuth);
        $this->getSlim()->hook('slim.before.dispatch', function () use ($slim, $container) {
            $route = $slim->router()->getCurrentRoute();
            $conditions = $route->getConditions();

            if (isset($conditions['useController']) && $conditions['useController'] == false) {
                return;
            }

            $routeOptions = call_user_func($route->getCallable());
            $routeKeys = is_array($routeOptions) ? array_keys($routeOptions) : array();

            if (!in_array('controller', $routeKeys, true)) {
                return $container->get('output')->render($routeOptions);
            }

            $params = $route->getParams();
            $data = $slim->request()->getBody();

            foreach ($routeOptions as $key => $value) {
                if (strstr($value, ':')) {
                    $paramName = str_replace(':', '', $value);
                    $value = $params[$paramName];
                }
                $controllerParams[$key] = $value;
            }

            $params = array_merge($params, $controllerParams);

            $controllerName = ucfirst($controllerParams['controller']);

            if (!empty($controllerParams['action'])) {
                $actionName = $controllerParams['action'];
            } else {
                $httpMethod = strtolower($slim->request()->getMethod());
                $crudList = $container->get('config')->get('crud');
                $actionName = $crudList[$httpMethod];
            }

            try {
                $controllerManager = $this->getContainer()->get('controllerManager');
                $result = $controllerManager
                    ->process($controllerName, $actionName, $params, $data, $slim->request(), $slim->response());
                $container->get('output')->render($result);
            } catch (\Exception $e) {
                $container->get('output')->processError($e->getMessage(), $e->getCode(), false, $e);
            }
        });

        $this->getSlim()->hook('slim.after.router', function () use (&$slim) {
            $slim->contentType('application/json');

            $res = $slim->response();
            $res->header('Expires', '0');
            $res->header('Last-Modified', gmdate("D, d M Y H:i:s") . " GMT");
            $res->header('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
            $res->header('Pragma', 'no-cache');
        });
    }

    /**
     * Init routes
     */
    protected function initRoutes()
    {
        $crudList = array_keys($this->getConfig()->get('crud'));

        foreach ($this->getRouteList() as $route) {
            $method = strtolower($route['method']);
            if (!in_array($method, $crudList) && $method !== 'options') {
                $message = "Route: Method [$method] does not exist. Please check your route [" . $route['route'] . "]";

                $GLOBALS['log']->error($message);
                continue;
            }

            $currentRoute = $this->getSlim()->$method($route['route'], function () use ($route) {
                return $route['params'];
            });

            if (isset($route['conditions'])) {
                $currentRoute->conditions($route['conditions']);
            }
        }
    }

    /**
     * Copy default config
     */
    private function copyDefaultConfig(): void
    {
        // prepare config path
        $path = 'data/config.php';

        if (!file_exists($path)) {
            // get default data
            $data = include 'application/Treo/Configs/defaultConfig.php';

            // prepare salt
            $data['passwordSalt'] = mb_substr(md5((string)time()), 0, 9);

            // get content
            $content = "<?php\nreturn " . $this->getContainer()->get('fileManager')->varExport($data) . ";\n?>";

            // create config
            file_put_contents($path, $content);
        }
    }
}
