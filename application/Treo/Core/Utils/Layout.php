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

namespace Treo\Core\Utils;

use Espo\Core\Container;
use Espo\Core\Utils\Layout as EspoLayout;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Util;
use Espo\Core\Utils\Json;
use Espo\Entities\User;
use Espo\Modules\TreoCore\Layouts\AbstractLayout;

/**
 * Class of Layout
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Layout extends EspoLayout
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * Construct
     */
    public function __construct()
    {
        // blocked parent construct
    }

    /**
     * Get Layout context
     *
     * @param string $scope
     * @param string $name
     *
     * @return json
     */
    public function get($scope, $name)
    {
        // prepare params
        $data = [];
        $scope = $this->sanitizeInput($scope);
        $name = $this->sanitizeInput($name);

        // cache
        if (isset($this->changedData[$scope][$name])) {
            return Json::encode($this->changedData[$scope][$name]);
        }

        // from custom data
        $fileFullPath = Util::concatPath($this->getLayoutPath($scope, true), $name . '.json');
        if (file_exists($fileFullPath)) {
            $fileData = $this->getFileManager()->getContents($fileFullPath);

            // prepare data
            $data = array_merge_recursive($data, Json::decode($fileData, true));
        }

        // from modules data
        if (empty($data)) {
            foreach ($this->getMetadata()->getModuleList() as $module) {
                // prepare file path
                $filePath = Util::concatPath(str_replace('{*}', $module, $this->paths['modulePath']), $scope);
                $fileFullPath = Util::concatPath($filePath, $name . '.json');
                if (file_exists($fileFullPath)) {
                    // get file data
                    $fileData = $this->getFileManager()->getContents($fileFullPath);

                    // prepare data
                    $data = array_merge_recursive($data, Json::decode($fileData, true));
                }
            }
        }

        // from core data
        if (empty($data)) {
            // prepare file path
            $filePath = Util::concatPath($this->paths['corePath'], $scope);
            $fileFullPath = Util::concatPath($filePath, $name . '.json');
            if (file_exists($fileFullPath)) {
                // get file data
                $fileData = $this->getFileManager()->getContents($fileFullPath);

                // prepare data
                $data = Json::decode($fileData, true);
            }
        }

        // default
        if (empty($data)) {
            // prepare file path
            $fileFullPath = Util::concatPath(
                Util::concatPath($this->params['defaultsPath'], 'layouts'),
                $name . '.json'
            );

            if (file_exists($fileFullPath)) {
                // get file data
                $fileData = $this->getFileManager()->getContents($fileFullPath);

                // prepare data
                $data = Json::decode($fileData, true);
            }
        }

        // remove fields from layout if this fields not exist in metadata
        $data = $this->disableNotExistingFields($scope, $name, $data);

        // modify data
        foreach ($this->getMetadata()->getModuleList() as $module) {
            $className = sprintf('Espo\Modules\%s\Layouts\%s', $module, $scope);
            if (class_exists($className)) {
                // create class
                $layout = new $className();

                // set container
                if ($layout instanceof AbstractLayout) {
                    $layout->setContainer($this->getContainer());
                }

                // call method
                $method = 'layout' . ucfirst($name);
                if (method_exists($layout, $method)) {
                    $data = $layout->{$method}($data);
                }
            }
        }

        return Json::encode($data);
    }

    /**
     * Disable fields from layout if this fields not exist in metadata
     *
     * @param string $scope
     * @param string $name
     * @param array  $data
     *
     * @return array
     */
    protected function disableNotExistingFields($scope, $name, $data): array
    {
        // get entityDefs
        $entityDefs = $this->getMetadata()->get('entityDefs')[$scope] ?? [];

        // check if entityDefs exists
        if (!empty($entityDefs)) {
            // get fields for entity
            $fields = array_keys($entityDefs['fields']);

            // remove fields from layout if this fields not exist in metadata
            switch ($name) {
                case 'filters':
                case 'massUpdate':
                    $data = array_values(array_intersect($data, $fields));

                    break;
                case 'detail':
                case 'detailSmall':
                    foreach ($data[0]['rows'] as $key => $row) {
                        foreach ($row as $fieldKey => $fieldData) {
                            if (isset($fieldData['name']) && !in_array($fieldData['name'], $fields)) {
                                $data[0]['rows'][$key][$fieldKey] = false;
                            }
                        }
                    }

                    break;
                case 'list':
                case 'listSmall':
                    foreach ($data as $key => $row) {
                        if (isset($row['name']) && !in_array($row['name'], $fields)) {
                            unset($data[$key]);
                        }
                    }

                    break;
            }
        }

        return $data;
    }

    /**
     * Set container
     *
     * @param Container $container
     *
     * @return Layout
     */
    public function setContainer(Container $container): Layout
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Get container
     *
     * @return Container
     */
    protected function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Get file manager
     *
     * @return FileManager
     */
    protected function getFileManager(): FileManager
    {
        return $this->getContainer()->get('fileManager');
    }

    /**
     * Get metadata
     *
     * @return Metadata
     */
    protected function getMetadata(): Metadata
    {
        return $this->getContainer()->get('metadata');
    }

    /**
     * Get user
     *
     * @return User
     */
    protected function getUser(): User
    {
        return $this->getContainer()->get('user');
    }
}
