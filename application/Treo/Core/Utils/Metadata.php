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

use Espo\Core\Utils\Json;
use Espo\Core\Utils\Module;
use Espo\Core\Utils\Util;
use Treo\Core\Utils\File\Unifier;
use Treo\Metadata\AbstractMetadata;

/**
 * Metadata class
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Metadata extends \Espo\Core\Utils\Metadata
{

    /**
     * Traits
     */
    use \Treo\Traits\ContainerTrait;

    /**
     * @var Unifier
     */
    protected $unifier;

    /**
     * @var Unifier
     */
    protected $objUnifier;

    /**
     * @var object
     */
    protected $fileManager;

    /**
     * @var Module
     */
    protected $moduleConfig = null;

    /**
     * @var object
     */
    protected $metadataHelper;

    /**
     * @var array
     */
    protected $deletedData = [];

    /**
     * @var array
     */
    protected $changedData = [];

    /**
     * @var array|null
     */
    protected $composerLockData = null;

    /**
     * @var array
     */
    protected $paths
        = [
            'treoCorePath' => 'application/Treo/Resources/metadata',
            'corePath'     => 'application/Espo/Resources/metadata',
            'modulePath'   => 'application/Espo/Modules/{*}/Resources/metadata',
            'customPath'   => 'custom/Espo/Custom/Resources/metadata',
        ];

    /**
     * Prepare version
     *
     * @param string $version
     *
     * @return string
     */
    public static function prepareVersion(string $version): string
    {
        return str_replace('v', '', $version);
    }

    /**
     * Get module config data
     *
     * @param string $module
     *
     * @return mixed
     */
    public function getModuleConfigData(string $module)
    {
        return $this->getModuleConfig()->get($module);
    }

    /**
     * Get module data (from composer.lock)
     *
     * @param string $id
     *
     * @return array
     */
    public function getModule(string $id): array
    {
        // prepare result
        $result = [];

        if (is_null($this->composerLockData)) {
            // load composer lock
            $this->loadComposerLock();
        }

        if (!empty($packages = $this->composerLockData['packages'])) {
            foreach ($packages as $package) {
                if (!empty($package['extra']['treoId']) && $id == $package['extra']['treoId']) {
                    // prepare version
                    $package['version'] = self::prepareVersion($package['version']);

                    // prepare result
                    $result = $package;

                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Init metadata
     *
     * @param  boolean $reload
     *
     * @return void
     */
    public function init($reload = false)
    {
        // call parent init
        parent::init($reload);

        // modify metadata by modules
        $this->data = $this->modulesModification($this->data);
    }

    /**
     * Get all metadata for frontend
     *
     * @param bool $reload
     *
     * @return array
     */
    public function getAllForFrontend($reload = false): array
    {
        $data = parent::getAllForFrontend();

        $data = Json::decode(JSON::encode($data), true);

        return $this->modulesModification($data);
    }

    /**
     * Drop metadata cache
     */
    public function dropCache(): void
    {
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }

    /**
     * Get additional field lists
     *
     * @param string $scope
     * @param string $field
     *
     * @return array
     */
    public function getFieldList(string $scope, string $field): array
    {
        // prepare result
        $result = [];

        // get field data
        $fieldData = $this->get("entityDefs.$scope.fields.$field");

        if (!empty($fieldData)) {
            // prepare result
            $result[$field] = $fieldData;

            $additionalFields = $this
                ->getMetadataHelper()
                ->getAdditionalFieldList($field, $fieldData, $this->get("fields"));

            if (!empty($additionalFields)) {
                // prepare result
                $result = $result + $additionalFields;
            }
        }

        return $result;
    }

    /**
     * @param string $entityName
     * @param string $delim
     *
     * @return string
     */
    public function getRepositoryPath($entityName, $delim = '\\')
    {
        $path = implode($delim, ['Treo', 'Repositories', Util::normilizeClassName(ucfirst($entityName))]);
        if (!class_exists($path)) {
            $path = parent::getRepositoryPath($entityName, $delim);
        }

        return $path;
    }

    /**
     * Modify metadata by modules
     *
     * @param array $data
     *
     * @return array
     */
    protected function modulesModification(array $data): array
    {
        // prepare classes
        $classes = [
            'Treo\Metadata\Metadata'
        ];

        // parse modules
        foreach ($this->getModuleList() as $module) {
            $className = sprintf('Espo\Modules\%s\Metadata\Metadata', $module);
            if (class_exists($className)) {
                $classes[] = $className;
            }
        }

        foreach ($classes as $className) {
            $metadata = new $className();
            if ($metadata instanceof AbstractMetadata) {
                // set container
                $metadata->setContainer($this->getContainer());

                // modify data
                $data = $metadata->modify($data);
            }
        }

        return $data;
    }

    /**
     * Clear metadata variables when reload meta
     *
     * @return void
     */
    protected function clearVars()
    {
        parent::clearVars();

        $this->moduleConfig = null;
    }


    /**
     * Get module config
     *
     * @return Module
     */
    protected function getModuleConfig(): Module
    {
        if (!isset($this->moduleConfig)) {
            $this->moduleConfig = new Module($this->getFileManager(), $this->useCache);
        }

        return $this->moduleConfig;
    }


    /**
     * Load composer lock data
     */
    protected function loadComposerLock(): void
    {
        // prepare data
        $this->composerLockData = [];

        // prepare composerLock
        $composerLock = 'composer.lock';

        // prepare dir
        $vendorTreoDir = 'vendor/' . ModuleMover::TREODIR . '/';

        if (file_exists($vendorTreoDir) && is_dir($vendorTreoDir) && file_exists($composerLock)) {
            // prepare data
            $this->composerLockData = Json::decode(file_get_contents($composerLock), true);
        }
    }

    /**
     * @inheritdoc
     */
    protected function getUnifier()
    {
        if (!isset($this->unifier)) {
            $this->unifier = new Unifier($this->getFileManager(), $this, false);
        }

        return $this->unifier;
    }

    /**
     * @inheritdoc
     */
    protected function getObjUnifier()
    {
        if (!isset($this->objUnifier)) {
            $this->objUnifier = new Unifier($this->getFileManager(), $this, true);
        }

        return $this->objUnifier;
    }
}
