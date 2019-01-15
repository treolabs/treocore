<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2019 TreoLabs GmbH
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

use Treo\Services\Installer;
use Treo\Core\Utils\Auth;

/**
 * Class Application
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Application extends \Espo\Core\Application
{
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
        if (!version_compare($phpVersion, $validPhpVersion, '==')) {
            echo "Invalid PHP version. PHP {$validPhpVersion} is required!";
            die();
        }
    }

    /**
     * @inheritdoc
     */
    public function isInstalled()
    {
        // prepare config path
        $path = 'data/config.php';

        if (!file_exists($path)) {
            // get default data
            $data = include 'application/Treo/Configs/defaultConfig.php';

            // prepare salt
            $data['passwordSalt'] = mb_substr(md5((string)time()), 0, 9);

            // create config
            $this->getContainer()->get('fileManager')->putPhpContents($path, $data, true);
        }

        return parent::isInstalled();
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
     * @inheritdoc
     */
    public function run($name = 'default')
    {
        // for installer
        if (!$this->isInstalled()) {
            $this->runInstallerApi();
        }

        parent::run($name);
    }

    /**
     * @inheritdoc
     */
    public function runClient()
    {
        // for installer
        if (!$this->isInstalled()) {
            $this->runInstallerClient();
        }

        $modules = $this->getConfig()->get('modules');
        $version = !empty($modules['TreoCore']['version']) ? 'v.' . $modules['TreoCore']['version'] : "";

        $this->getContainer()->get('clientManager')->display(
            null,
            'html/treo-main.html',
            [
                'classReplaceMap' => json_encode($this->getMetadata()->get(['app', 'clientClassReplaceMap'], [])),
                'year'            => date('Y'),
                'version'         => $version
            ]
        );
        exit;
    }

    /**
     * @inheritdoc
     */
    public function runClearCache()
    {
        // blocked parent method
    }

    /**
     * @inheritdoc
     */
    public function runRebuild()
    {
        // blocked parent method
    }

    /**
     * @inheritdoc
     */
    public function runCron()
    {
        // blocked parent method
    }

    /**
     * @inheritdoc
     */
    protected function initContainer()
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
     * @inheritdoc
     */
    protected function getRouteList()
    {
        $routes = new \Treo\Core\Utils\Route(
            $this->getConfig(),
            $this->getMetadata(),
            $this->getContainer()->get('fileManager')
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

        $modules = $this->getConfig()->get('modules');
        $version = !empty($modules['TreoCore']['version']) ? 'v.' . $modules['TreoCore']['version'] : "";

        $this->getContainer()->get('clientManager')->display(
            null,
            'html/treo-installation.html',
            [
                'year'    => date('Y'),
                'version' => $version,
                'status'  => $result['status'],
                'message' => $result['message']
            ]
        );
        exit;
    }
}
