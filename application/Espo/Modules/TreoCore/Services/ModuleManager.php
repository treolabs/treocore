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

namespace Espo\Modules\TreoCore\Services;

use Espo\Core\DataManager;
use Espo\Core\Services\Base;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Exceptions;
use Slim\Http\Request;
use Espo\Modules\TreoCore\Core\Utils\Metadata;
use Espo\Modules\TreoCore\Services\Composer as TreoComposer;

/**
 * ModuleManager service
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class ModuleManager extends Base
{
    /**
     * @var string
     */
    protected $moduleJsonPath = 'custom/Espo/Custom/Resources/module.json';

    /**
     * @var array
     */
    protected $moduleRequireds = [];

    /**
     * Get list
     *
     * @return array
     */
    public function getList(): array
    {
        // prepare result
        $result = [
            'total' => 0,
            'list'  => []
        ];

        // prepare composer data
        $composerData = $this->getComposerService()->getModuleComposerJson();

        foreach ($this->getMetadata()->getAllModules() as $module) {
            if ($module != 'TreoCore') {
                // prepare item
                $item = [
                    "id"                => $module,
                    "name"              => $module,
                    "description"       => '',
                    "settingVersion"    => '-',
                    "currentVersion"    => '-',
                    "availableVersions" => '-',
                    "required"          => [],
                    "isActive"          => $this->getMetadata()->isModuleActive($module),
                    "isSystem"          => false,
                    "isComposer"        => false
                ];

                // get current module package
                $package = $this->getComposerModuleService()->getModulePackage($module);

                if (!empty($package)) {
                    // prepare item
                    $item['name'] = $this->translateModule($module, 'name');
                    $item['description'] = $this->translateModule($module, 'description');
                    if (!empty($settingVersion = $composerData['require'][$package['name']])) {
                        $item['settingVersion'] = $this->prepareModuleVersion($settingVersion);
                    }
                    $item['currentVersion'] = $this->prepareModuleVersion($package['version']);
                    $item['versions'] = $this->prepareModuleVersions($module);
                    if (!empty($item['versions'])) {
                        $versions = array_column($item['versions'], 'version');
                        foreach ($versions as $k => $version) {
                            if (strpos($version, '*') !== false) {
                                unset($versions[$k]);
                            }
                        }
                        $item['availableVersions'] = implode(", ", $versions);
                    }
                    $item['required'] = $this->getModuleRequireds($module);
                    $item['isSystem'] = !empty($this->getModuleConfigData("{$module}.isSystem"));
                    $item['isComposer'] = true;
                }

                // push
                $result['list'][] = $item;
            }
        }

        $result['total'] = count($result['list']);

        // sorting
        usort($result['list'], [$this, 'moduleListSort']);

        return $result;
    }

    /**
     * Get available modules for install
     *
     * @return array
     */
    public function getAvailableModulesList(): array
    {
        // prepare result
        $result = [
            'total' => 0,
            'list'  => []
        ];

        if (!empty($modules = $this->getComposerModuleService()->getModulePackages())) {
            // get current language
            $currentLang = $this->getLanguage()->getLanguage();

            foreach ($modules as $moduleId => $versions) {
                if (empty($this->getComposerModuleService()->getModulePackage($moduleId))) {
                    // prepare max
                    $max = $versions['max'];

                    // prepare name
                    $name = $moduleId;
                    if (!empty($max['extra']['name'][$currentLang])) {
                        $name = $max['extra']['name'][$currentLang];
                    } elseif ($max['extra']['name']['default']) {
                        $name = $max['extra']['name']['default'];
                    }

                    // prepare description
                    $description = '-';
                    if (!empty($max['extra']['description'][$currentLang])) {
                        $description = $max['extra']['description'][$currentLang];
                    } elseif ($max['extra']['description']['default']) {
                        $description = $max['extra']['description']['default'];
                    }

                    $result['list'][] = [
                        'id'             => $moduleId,
                        'settingVersion' => $this->prepareModuleVersion($max['version']),
                        'versions'       => $this->prepareModuleVersions($moduleId),
                        'name'           => $name,
                        'description'    => $description
                    ];
                }
            }

            // prepare total
            $result['total'] = count($result['list']);
        }

        return $result;
    }

    /**
     * Update module activation
     *
     * @param string $moduleId
     *
     * @return bool
     * @throws Exceptions\Error
     */
    public function updateActivation(string $moduleId): bool
    {
        // prepare result
        $result = false;

        if ($this->isModuleChangeable($moduleId)) {
            // get config data
            $config = $this->getModuleConfigData($moduleId);

            // write to file
            $result = $this->updateModuleFile($moduleId, empty($config['disabled']));

            // drop cache
            $this->getDataManager()->clearCache();

            if ($result) {
                // get package
                $package = $this->getComposerModuleService()->getModulePackage($moduleId);

                // prepare event data
                $eventData = [
                    'id'       => $moduleId,
                    'disabled' => empty($config['disabled']),
                    'package'  => $package
                ];

                // triggered event
                $this->triggeredEvent('updateModuleActivation', $eventData);

                // rebuild DB
                if (!empty($config['disabled'])) {
                    $this->getDataManager()->rebuild();
                }
            }
        }

        return $result;
    }

    /**
     * Install module
     *
     * @param string $id
     * @param string $version
     *
     * @return array
     * @throws Exceptions\Error
     */
    public function installModule(string $id, string $version = null): array
    {
        // prepare params
        $package = $this->getComposerModuleService()->getModulePackage($id);
        $packages = $this->getComposerModuleService()->getModulePackages($id);

        // validation
        if (empty($packages) || empty($packages)) {
            throw new Exceptions\Error($this->translateError('No such module'));
        }
        if (!empty($package)) {
            throw new Exceptions\Error($this->translateError('Such module is already installed'));
        }


        if (!empty($version)) {
            // prepare version
            $version = $this->prepareModuleVersion($version);

            // validation
            if (!isset($packages[$version])) {
                throw new Exceptions\Error($this->translateError('No such module version'));
            }
        } else {
            $version = 'max';
        }

        // run composer
        $result = $this
            ->getComposerService()
            ->update($packages[$version]['name'], $packages[$version]['version']);

        // prepare event data
        $eventData = [
            'id'       => $id,
            'composer' => $result,
            'version'  => $version,
            'package'  => $packages[$version],
        ];

        // triggered event
        $this->triggeredEvent('installModule', $eventData);


        return $result;
    }

    /**
     * Update module
     *
     * @param string $id
     * @param string $version
     *
     * @return array
     * @throws Exceptions\Error
     */
    public function updateModule(string $id, string $version): array
    {
        // prepare params
        $package = $this->getComposerModuleService()->getModulePackage($id);
        $packages = $this->getComposerModuleService()->getModulePackages($id);

        // prepare version
        $version = $this->prepareModuleVersion($version);

        // validation
        if (empty($packages)) {
            throw new Exceptions\Error($this->translateError('No such module'));
        }
        if (empty($package)) {
            throw new Exceptions\Error($this->translateError('Module was not installed'));
        }
        if ($this->prepareModuleVersion($package['version']) == $version) {
            throw new Exceptions\Error($this->translateError('Such module version already installed'));
        }
        if (!isset($version, $packages[$version])) {
            throw new Exceptions\Error($this->translateError('No such module version'));
        }

        // run composer
        $result = $this
            ->getComposerService()
            ->update($packages[$version]['name'], $packages[$version]['version']);


        if ($result['status'] === 0) {
            // run migration
            $this->getInjection('migration')->run($id, $package['version'], $version);
        }

        // prepare event data
        $eventData = [
            'id'          => $id,
            'composer'    => $result,
            'version'     => $version,
            'packageFrom' => $package,
            'packageTo'   => $packages[$version],
        ];

        // triggered event
        $this->triggeredEvent('updateModule', $eventData);

        return $result;
    }

    /**
     * Delete module(s)
     *
     * @param array $ids
     *
     * @return array
     * @throws Exceptions\Error
     */
    public function deleteModule(array $ids): array
    {
        // prepare result
        $result = [];

        // prepare modules
        foreach ($ids as $id) {
            if (!$this->isModuleSystem($id)) {
                // prepare params
                $package = $this->getComposerModuleService()->getModulePackage($id);

                // validation
                if (empty($package)) {
                    throw new Exceptions\Error($this->translateError('No such module'));
                }

                $modules[] = $package;
            }
        }

        if (!empty($modules)) {
            // prepare modules diff
            $beforeDelete = TreoComposer::getTreoModules();

            // get composer.json data
            $composerData = $this->getComposerService()->getModuleComposerJson();

            foreach ($modules as $package) {
                if (isset($composerData['require'][$package['name']])) {
                    unset($composerData['require'][$package['name']]);
                }
            }

            // set composer.json data
            $this->getComposerService()->setModuleComposerJson($composerData);

            // run composer
            $result = $this->getComposerService()->runUpdate();

            if ($result['status'] === 0) {
                // prepare modules diff
                $afterDelete = TreoComposer::getTreoModules();

                // delete treo dirs
                TreoComposer::deleteTreoModule(array_diff($beforeDelete, $afterDelete));

                // clear module activation and sort order data
                $this->clearModuleData($modules);

                // drop cache
                $this->getDataManager()->clearCache();

                // triggered event
                $eventData = ['modules' => $modules, 'composer' => $result];
                $this->triggeredEvent('deleteModules', $eventData);
            }
        }

        return $result;
    }

    /**
     * Get logs
     *
     * @param Request $request
     *
     * @return array
     */
    public function getLogs(Request $request): array
    {
        // prepare result
        $result = [
            'total' => 0,
            'list'  => []
        ];

        // prepare where
        $where = [
            'whereClause' => [
                'parentType' => 'ModuleManager'
            ],
            'offset'      => (int)$request->get('offset'),
            'limit'       => (int)$request->get('maxSize'),
            'orderBy'     => 'number',
            'order'       => 'DESC'
        ];

        $result['total'] = $this
            ->getEntityManager()
            ->getRepository('Note')
            ->count(['whereClause' => $where['whereClause']]);

        if ($result['total'] > 0) {
            if (!empty($request->get('after'))) {
                $where['whereClause']['createdAt>'] = $request->get('after');
            }

            // get collection
            $result['list'] = $this
                ->getEntityManager()
                ->getRepository('Note')
                ->find($where)
                ->toArray();
        }

        return $result;
    }

    /**
     * Init
     */
    protected function init()
    {
        /**
         * Add dependencies
         */
        $this->addDependencyList(
            [
                'metadata',
                'language',
                'fileManager',
                'dataManager',
                'serviceFactory',
                'migration',
                'eventManager'
            ]
        );
    }

    /**
     * Is module changable?
     *
     * @param string $moduleId
     *
     * @return bool
     * @throws Exceptions\Error
     */
    protected function isModuleChangeable(string $moduleId): bool
    {
        // is system module ?
        $this->isModuleSystem($moduleId);

        // checking requireds
        if ($this->hasRequireds($moduleId)) {
            throw new Exceptions\Error(
                $this
                    ->getLanguage()
                    ->translate('hasRequiredsDelete', 'exceptions', 'ModuleManager')
            );
        }

        return true;
    }

    /**
     * Is module system ?
     *
     * @param string $moduleId
     *
     * @return bool
     * @throws Exceptions\Error
     */
    protected function isModuleSystem(string $moduleId): bool
    {
        // is system module ?
        if (!empty($this->getModuleConfigData("{$moduleId}.isSystem"))) {
            throw new Exceptions\Error(
                $this
                    ->getLanguage()
                    ->translate('isSystem', 'exceptions', 'ModuleManager')
            );
        }

        return false;
    }

    /**
     * Update module file
     *
     * @param string $moduleId
     * @param bool   $isDisabled
     *
     * @return bool
     */
    protected function updateModuleFile(string $moduleId, bool $isDisabled): bool
    {
        // prepare data
        $data = [];

        // reload modules
        $this->getMetadata()->init(true);

        foreach ($this->getMetadata()->getAllModules() as $module) {
            if (!in_array($module, ['Crm', 'TreoCore'])) {
                $data[$module] = [
                    'order'    => $this->createModuleLoadOrder($module),
                    'disabled' => !in_array($module, $this->getMetadata()->getModuleList())
                ];

                if ($module == $moduleId) {
                    $data[$module]['disabled'] = $isDisabled;
                }
            }
        }

        return $this->getFileManager()->putContentsJson($this->moduleJsonPath, $data);
    }

    /**
     * Clear module data from "module.json" file
     *
     * @param array $modules
     *
     * @return bool
     */
    protected function clearModuleData(array $modules): bool
    {
        foreach ($modules as $package) {
            $this
                ->getFileManager()
                ->unsetContents($this->moduleJsonPath, $package['extra']['treoId']);
        }

        return true;
    }

    /**
     * Create module load order
     *
     * @param string $moduleId
     *
     * @return int
     */
    protected function createModuleLoadOrder(string $moduleId): int
    {
        // prepare result
        $result = 5100;

        /**
         * For requireds
         */
        if (!empty($requireds = $this->getModuleRequireds($moduleId))) {
            foreach ($requireds as $require) {
                $requireMax = $this->createModuleLoadOrder($require);
                if ($requireMax > $result) {
                    $result = $requireMax;
                }
            }

            $result = $result + 10;
        }

        /**
         * For extends
         */
        if (!empty($extends = $this->getModuleConfigData($moduleId)['extends'])) {
            foreach ($extends as $extend) {
                $extendMax = $this->createModuleLoadOrder($extend);
                if ($extendMax > $result) {
                    $result = $extendMax;
                }
            }

            $result = $result + 10;
        }

        return $result;
    }

    /**
     * Get module requireds
     *
     * @param string $moduleId
     *
     * @return array
     */
    protected function getModuleRequireds(string $moduleId): array
    {
        if (!isset($this->moduleRequireds[$moduleId])) {
            // prepare result
            $this->moduleRequireds[$moduleId] = [];

            if (!empty($package = $this->getComposerModuleService()->getModulePackage($moduleId))) {
                if (!empty($composerRequire = $package['require']) && is_array($composerRequire)) {
                    // get treo modules
                    $treoModule = TreoComposer::getTreoModules();

                    foreach ($composerRequire as $key => $version) {
                        if (preg_match_all("/^(" . TreoComposer::TREODIR . "\/)(.*)$/", $key, $matches)) {
                            if (!empty($matches[2][0])) {
                                $this->moduleRequireds[$moduleId][] = array_flip($treoModule)[$matches[2][0]];
                            }
                        }
                    }
                }
            }
        }

        return $this->moduleRequireds[$moduleId];
    }

    /**
     * Is module has requireds
     *
     * @param string $moduleId
     *
     * @return bool
     */
    protected function hasRequireds(string $moduleId): bool
    {
        // prepare result
        $result = false;

        // is module requireds by another modules
        if (empty($this->getModuleConfigData("{$moduleId}.disabled"))) {
            foreach ($this->getMetadata()->getModuleList() as $module) {
                // get module requireds
                $requireds = $this->getModuleRequireds($module);

                if (isset($requireds) && in_array($moduleId, $requireds)) {
                    // prepare result
                    $result = true;

                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Translate field
     *
     * @param string $module
     * @param string $key
     *
     * @return string
     */
    protected function translateModule(string $module, string $key): string
    {
        // prepare result
        $result = '';

        // get language
        $lang = $this->getLanguage()->getLanguage();

        // get module packages
        $package = $this->getComposerModuleService()->getModulePackage($module);

        if (!empty($translate = $package['extra'][$key][$lang])) {
            $result = $translate;
        } elseif (!empty($translate = $package['extra'][$key]['default'])) {
            $result = $translate;
        }

        return $result;
    }

    /**
     * Translate error
     *
     * @param string $key
     *
     * @return string
     */
    protected function translateError(string $key): string
    {
        return $this->getLanguage()->translate($key, 'exceptions', 'ModuleManager');
    }

    /**
     * Prepare module version
     *
     * @param string $version
     *
     * @return string
     */
    protected function prepareModuleVersion(string $version): string
    {
        return str_replace('v', '', $version);
    }

    /**
     * Prepare module versions
     *
     * @param string $id
     *
     * @return array
     */
    protected function prepareModuleVersions(string $id): array
    {
        // prepare result
        $result = [];

        // get packages
        $packages = $this->getComposerModuleService()->getModulePackages();

        if (!empty($packages) && !empty($data = $packages[$id])) {
            // get current language
            $currentLang = $this->getLanguage()->getLanguage();

            foreach ($data as $version => $row) {
                if ($version != 'max') {
                    // prepare require
                    $require = [];

                    foreach ($row['require'] as $k => $v) {
                        // for system
                        if ($k == 'treo/treo') {
                            $require[$k] = [
                                'id'       => $k,
                                'name'     => 'Treo System',
                                'version'  => $v,
                                'isModule' => false
                            ];
                        }

                        // for modules
                        foreach ($packages as $pac) {
                            if ($pac['max']['name'] == $k) {
                                // prepare name
                                $name = $pac['max']['extra']['name']['default'];
                                if (isset($pac['max']['extra']['name'][$currentLang])) {
                                    $name = $pac['max']['extra']['name'][$currentLang];
                                }

                                $require[$k] = [
                                    'id'       => $k,
                                    'name'     => $name,
                                    'version'  => $v,
                                    'isModule' => true
                                ];
                            }
                        }

                        // for else
                        if (!isset($require[$k])) {
                            $require[$k] = [
                                'id'       => $k,
                                'name'     => $k,
                                'version'  => $v,
                                'isModule' => false
                            ];
                        }
                    }

                    // push
                    $result[str_replace('*', '999', $version)] = [
                        'version' => $version,
                        'require' => array_values($require)
                    ];
                }
            }

            // sort
            ksort($result);

            // prepare result
            $result = array_values($result);
        }

        return $result;
    }

    /**
     * Triggered event
     *
     * @param string $action
     * @param array  $data
     *
     * @return void
     */
    protected function triggeredEvent(string $action, array $data = [])
    {
        $this
            ->getInjection('eventManager')
            ->triggered('ModuleManager', $action, $data);
    }

    /**
     * Get module config data
     *
     * @param string $key
     *
     * @return mixed
     */
    protected function getModuleConfigData(string $key)
    {
        return $this->getMetadata()->getModuleConfigData($key);
    }

    /**
     * Get DataManager
     *
     * @return DataManager
     */
    protected function getDataManager(): DataManager
    {
        return $this->getInjection('dataManager');
    }

    /**
     * Get ComposerModule service
     *
     * @return ComposerModule
     */
    protected function getComposerModuleService(): ComposerModule
    {
        return $this->getInjection('serviceFactory')->create('ComposerModule');
    }

    /**
     * Get metadata
     *
     * @return Metadata
     */
    protected function getMetadata(): Metadata
    {
        return $this->getInjection('metadata');
    }

    /**
     * Get language
     *
     * @return Language
     */
    protected function getLanguage(): Language
    {
        return $this->getInjection('language');
    }

    /**
     * Get File Manager
     *
     * @return FileManager
     */
    protected function getFileManager(): FileManager
    {
        return $this->getInjection('fileManager');
    }

    /**
     * Get Composer service
     *
     * @return Composer
     */
    protected function getComposerService(): Composer
    {
        return $this->getInjection('serviceFactory')->create('Composer');
    }

    /**
     * Module list sort
     *
     * @param array $a
     * @param array $b
     *
     * @return int
     */
    private static function moduleListSort(array $a, array $b): int
    {
        // prepare params
        $a = $a['name'];
        $b = $b['name'];

        if ($a == $b) {
            return 0;
        }

        return ($a < $b) ? -1 : 1;
    }
}
