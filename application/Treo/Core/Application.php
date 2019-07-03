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
use Espo\Core\Utils\Json;
use Espo\Entities\Portal;
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
    const CONFIG_PATH = 'data/portals.json';

    /**
     * @var null|array
     */
    protected static $urls = null;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var Portal|null
     */
    protected $portal = null;

    /**
     * Get portals url config file data
     *
     * @return array
     */
    public static function getPortalUrlFileData(): array
    {
        if (is_null(self::$urls)) {
            // prepare result
            self::$urls = [];

            if (file_exists(self::CONFIG_PATH)) {
                $json = file_get_contents(self::CONFIG_PATH);
                if (!empty($json)) {
                    self::$urls = Json::decode($json, true);
                }
            }
        }

        return self::$urls;
    }

    /**
     * Set data to portal url config file
     *
     * @param array $data
     */
    public static function savePortalUrlFile(array $data): void
    {
        file_put_contents(self::CONFIG_PATH, Json::encode($data));
    }

    /**
     * Application constructor.
     */
    public function __construct()
    {
        // set timezone
        date_default_timezone_set('UTC');

        // set container
        $this->container = new Container();

        // set log
        $GLOBALS['log'] = $this->getContainer()->get('log');
    }

    /**
     * Run App
     */
    public function run()
    {
        // prepare uri
        $uri = (!empty($_SERVER['REDIRECT_URL'])) ? $_SERVER['REDIRECT_URL'] : '';

        // for api
        if (preg_match('/^\/api\/v1\/(.*)$/', $uri)) {
            $this->runApi($uri);
        }

        // for client
        $this->runClient($uri);
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
     * Run API
     *
     * @param string $uri
     */
    protected function runApi(string $uri)
    {
        // for installer
        if (!$this->isInstalled()) {
            $this->runInstallerApi();
        }

        // prepare base route
        $baseRoute = '/api/v1';

        // for portal api
        if (preg_match('/^\/api\/v1\/portal-access\/(.*)\/.*$/', $uri)) {
            // parse uri
            $matches = explode('/', str_replace('/api/v1/portal-access/', '', $uri));

            // set portal container
            $this->container = new \Treo\Core\Portal\Container();

            // find portal
            $portal = $this
                ->getContainer()
                ->get('entityManager')
                ->getEntity('Portal', $matches[0]);

            // set portal
            $this->getContainer()->setPortal($portal);

            // prepare base route
            $baseRoute = '/api/v1/portal-access';
        }

        $this->routeHooks();
        $this->initRoutes($baseRoute);
        $this->getSlim()->run();
        exit;
    }

    /**
     * Run client
     *
     * @param string $uri
     */
    protected function runClient(string $uri)
    {
        // for installer
        if (!$this->isInstalled()) {
            $this->runInstallerClient();
        }

        // for entryPoint
        if (!empty($_GET['entryPoint'])) {
            $this->runEntryPoint($_GET['entryPoint']);
            exit;
        }

        // prepare client vars
        $vars = [
            'classReplaceMap' => json_encode($this->getMetadata()->get(['app', 'clientClassReplaceMap'], [])),
            'year'            => date('Y')
        ];

        if (!empty($portalId = $this->getPortalIdForClient())) {
            // set portal container
            $this->container = new \Treo\Core\Portal\Container();

            // find portal
            $portal = $this
                ->getContainer()
                ->get('entityManager')
                ->getEntity('Portal', $portalId);

            if ($portal && $portal->get('isActive')) {
                // set portal
                $this->getContainer()->setPortal($portal);

                // prepare client vars
                $vars['portalId'] = $portalId;

                // load client
                $this
                    ->getContainer()
                    ->get('clientManager')
                    ->display(null, 'client/html/portal.html', $vars);
                exit;
            }

            // show 404
            header("HTTP/1.0 404 Not Found");
            exit;
        }

        if (!empty($uri) && $uri != '/') {
            // print module client file
            if (preg_match_all('/^\/client\/(.*)$/', $uri, $matches)) {
                $this->printModuleClientFile($matches[1][0]);
            }

            // if images path than call showImage
            if (preg_match_all('/^\/images\/(.*)\.(jpg|png|gif)$/', $uri, $matches)) {
                $this->runEntryPoint('TreoImage', ['id' => $matches[1][0], 'mimeType' => $matches[2][0]]);
            }

            // show 404
            header("HTTP/1.0 404 Not Found");
            exit;
        }

        $this
            ->getContainer()
            ->get('clientManager')
            ->display(null, 'client/html/main.html', $vars);
        exit;
    }

    /**
     * Run entry point
     *
     * @param string $entryPoint
     * @param array  $data
     * @param bool   $final
     */
    protected function runEntryPoint(string $entryPoint, $data = [], $final = false)
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
     * Print modules client files
     *
     * @param string $file
     */
    protected function printModuleClientFile(string $file)
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
        $routeList = $routes->getAll();

        if (!empty($this->getContainer()->get('portal'))) {
            foreach ($routeList as $i => $route) {
                if (isset($route['route'])) {
                    if ($route['route']{0} !== '/') {
                        $route['route'] = '/' . $route['route'];
                    }
                    $route['route'] = '/:portalId' . $route['route'];
                }
                $routeList[$i] = $route;
            }
        }

        return $routeList;
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
            'message'         => $result['message'],
            'classReplaceMap' => json_encode($this->getMetadata()->get(['app', 'clientClassReplaceMap'], [])),
            'year'            => date('Y')
        ];

        $this->getContainer()->get('clientManager')->display(null, 'client/html/installation.html', $vars);
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
     * Route hooks
     */
    protected function routeHooks()
    {
        $container = $this->getContainer();
        $slim = $this->getSlim();

        try {
            $auth = new Auth($container);
        } catch (\Exception $e) {
            $container->get('output')->processError($e->getMessage(), $e->getCode(), false, $e);
        }

        $apiAuth = new ApiAuth($auth);

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
     *
     * @param string $baseRoute
     */
    protected function initRoutes(string $baseRoute)
    {
        $crudList = array_keys($this->getConfig()->get('crud'));

        foreach ($this->getRouteList() as $route) {
            $method = strtolower($route['method']);
            if (!in_array($method, $crudList) && $method !== 'options') {
                $message = "Route: Method [$method] does not exist. Please check your route [" . $route['route'] . "]";

                $GLOBALS['log']->error($message);
                continue;
            }

            $currentRoute = $this->getSlim()->$method($baseRoute . $route['route'], function () use ($route) {
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
        // create data dir
        $dir = 'data';
        if (!file_exists($dir)) {
            mkdir($dir, 0777);
        }

        // prepare config path
        $path = $dir . '/config.php';

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

    /**
     * @return string
     */
    private function getPortalIdForClient(): string
    {
        // prepare result
        $result = '';

        // prepare protocol
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";

        // prepare url
        $url = $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        if (in_array($url, self::getPortalUrlFileData())) {
            $result = array_search($url, self::getPortalUrlFileData());
        }

        return $result;
    }
}
