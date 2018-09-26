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

use Espo\Core\Exceptions\NotFound;
use Espo\Modules\TreoCore\Services\Installer;
use Treo\Core\Utils\Auth;

/**
 * Class Application
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Application extends \Espo\Core\Application
{

    /**
     * Init container
     */
    protected function initContainer()
    {
        $this->container = new Container();
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
     * Run client
     */
    public function runClient()
    {
        $modules = $this->getContainer()->get('config')->get('modules');
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
     * Show image
     *
     * @param string $id
     */
    public function showImage(string $id)
    {
        // get attachment
        $attachment = $this
            ->getContainer()
            ->get('entityManager')
            ->getEntity('Attachment', $id);

        // show 404
        if (!$attachment) {
            $this->show404();
        }

        // get file path
        $filePath = $this
            ->getContainer()
            ->get('entityManager')
            ->getRepository('Attachment')
            ->getFilePath($attachment);

        if (!file_exists($filePath)) {
            $this->show404();
        }

        // get file type
        $fileType = $attachment->get('type');

        if (!in_array($fileType, ['image/jpeg', 'image/png', 'image/gif'])) {
            $this->show404();
        }

        header('Content-Disposition:inline;filename="' . $id . '.jpg"');
        header('Content-Type: ' . $fileType);
        header('Pragma: public');
        header('Cache-Control: max-age=360000, must-revalidate');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    /**
     * Run client
     */
    public function runInstaller()
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

        $modules = $this->getContainer()->get('config')->get('modules');
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
    }

    /**
     * Clear cache
     */
    public function runClearCache()
    {
        // blocked parent method
    }

    /**
     * Rebuild
     */
    public function runRebuild()
    {
        // blocked parent method
    }

    /**
     * Run cron
     */
    public function runCron()
    {
        // blocked parent method
    }

    /**
     * Create auth
     *
     * @return \Espo\Core\Utils\Auth|Auth
     */
    protected function createAuth()
    {
        return new Auth($this->container);
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
     * Show 404
     */
    private function show404()
    {
        header("HTTP/1.0 404 Not Found");
        exit;
    }
}
