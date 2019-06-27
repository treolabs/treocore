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

namespace Treo\Core\Portal;

use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Utils\Json;
use Treo\Core\Application as Base;
use Treo\Core\Utils\Route;

/**
 * Class Application
 *
 * @author r.ratsun@treolabs.com
 */
class Application extends Base
{
    const CONFIG_PATH = 'data/portals.json';

    /**
     * @var object
     */
    protected $portal;

    /**
     * @var null|array
     */
    protected static $urls = null;

    /**
     * Is calling portal id
     *
     * @return string
     */
    public static function getCallingPortalId(): string
    {
        // prepare result
        $result = '';

        // prepare protocol
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";

        // prepare url
        $url = $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        if (in_array($url, self::getUrlFileData())) {
            $result = array_search($url, self::getUrlFileData());
        }

        return $result;
    }

    /**
     * Get url config file data
     *
     * @return array
     */
    public static function getUrlFileData(): array
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
     * Set data to url config file
     *
     * @param array $data
     */
    public static function saveUrlFile(array $data): void
    {
        $file = fopen(self::CONFIG_PATH, "w");
        fwrite($file, Json::encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        fclose($file);
    }

    /**
     * Application constructor.
     *
     * @param string $portalId
     *
     * @throws Error
     * @throws Forbidden
     * @throws NotFound
     */
    public function __construct(string $portalId)
    {
        date_default_timezone_set('UTC');

        $this->initContainer();

        if (empty($portalId)) {
            throw new Error("Portal id was not passed to ApplicationPortal.");
        }

        $GLOBALS['log'] = $this->getContainer()->get('log');

        $portal = $this->getContainer()->get('entityManager')->getEntity('Portal', $portalId);

        if (!$portal) {
            $portal = $this
                ->getContainer()
                ->get('entityManager')
                ->getRepository('Portal')
                ->where(['customId' => $portalId])
                ->findOne();
        }

        if (!$portal) {
            throw new NotFound();
        }
        if (!$portal->get('isActive')) {
            throw new Forbidden("Portal is not active.");
        }

        $this->portal = $portal;

        $this->getContainer()->setPortal($portal);
    }

    /**
     * @inheritDoc
     */
    public function runClient()
    {
        $this
            ->getContainer()
            ->get('clientManager')
            ->display(null, 'html/portal.html', ['portalId' => $this->getPortal()->id]);
        exit;
    }

    /**
     * @inheritDoc
     */
    protected function initContainer(): void
    {
        $this->container = new Container();
    }

    /**
     * @inheritdoc
     */
    protected function getRouteList()
    {
        $routes = new Route(
            $this->getContainer()->get('config'),
            $this->getMetadata(),
            $this->getContainer()->get('fileManager'),
            $this->getContainer()->get('moduleManager')
        );
        $routeList = $routes->getAll();

        foreach ($routeList as $i => $route) {
            if (isset($route['route'])) {
                if ($route['route']{0} !== '/') {
                    $route['route'] = '/' . $route['route'];
                }
                $route['route'] = '/:portalId' . $route['route'];
            }
            $routeList[$i] = $route;
        }

        return $routeList;
    }

    /**
     * Get portal
     *
     * @return mixed
     */
    protected function getPortal()
    {
        return $this->portal;
    }
}
