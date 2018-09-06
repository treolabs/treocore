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

use Espo\Core\Services\Base;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Exceptions;
use Slim\Http\Request;
use Espo\Modules\TreoCore\Core\Utils\Metadata;
use Espo\Modules\TreoCore\Core\Utils\ModuleMover as TreoComposer;

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

        // get diff
        $composerDiff = $this->getComposerService()->getComposerDiff();

        // for installed modules
        foreach ($this->getMetadata()->getModuleList() as $id) {
            // skip core module
            if ($id == 'TreoCore') {
                continue;
            }

            // push for custom module
            $result['list'][$id] = [
                'id'                 => $id,
                'name'               => $id,
                'description'        => '',
                'settingVersion'     => '',
                'currentVersion'     => '',
                'versions'           => [],
                'required'           => [],
                'requiredTranslates' => [],
                'isSystem'           => !empty($this->getModuleConfigData("{$id}.isSystem")),
                'isComposer'         => false,
                'status'             => $this->getModuleStatus($composerDiff, $id),
            ];

            if (!empty($package = $this->getMetadata()->getModule($id))) {
                $result['list'][$id]['name'] = $this->packageTranslate($package['extra']['name'], $id);
                $result['list'][$id]['description'] = $this->packageTranslate($package['extra']['description'], '-');
                $result['list'][$id]['settingVersion'] = '*';
                if ($settingVersion = $composerData['require'][$package['name']]) {
                    $result['list'][$id]['settingVersion'] = Metadata::prepareVersion($settingVersion);
                }
                $result['list'][$id]['currentVersion'] = $package['version'];
                $result['list'][$id]['versions'] = $this->getPackagistPackage($id)['versions'];
                if (!empty($requireds = $this->getModuleRequireds($id))) {
                    $result['list'][$id]['required'] = $requireds;
                    foreach ($requireds as $required) {
                        $pRequired = $this
                            ->getMetadata()
                            ->getModule($required);

                        $result['list'][$id]['requiredTranslates'][] = $this
                            ->packageTranslate($pRequired['extra']['name']);
                    }
                }
                $result['list'][$id]['isComposer'] = true;
            }
        }

        // for uninstalled modules
        foreach ($composerDiff['install'] as $row) {
            $item = [
                "id"             => $row['id'],
                "name"           => $row['id'],
                "description"    => '',
                "settingVersion" => '*',
                "currentVersion" => '',
                "required"       => [],
                "isSystem"       => false,
                "isComposer"     => true,
                "status"         => 'install'
            ];

            if (!empty($package = $this->getPackagistPackage($row['id']))) {
                $item['name'] = $this->packageTranslate($package['name'], $row['id']);
                $item['description'] = $this->packageTranslate($package['description'], "-");
                if (!empty($settingVersion = $composerData['require'][$package['name']])) {
                    $item['settingVersion'] = Metadata::prepareVersion($settingVersion);
                }
            }

            // push
            $result['list'][$row['id']] = $item;
        }

        // prepare result
        $result['list'] = array_values($result['list']);
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

        if (!empty($packages = $this->getPackagistPackages())) {
            // get diff
            $diff = $this->getComposerService()->getComposerDiff();

            // get installed modules
            $installed = array_merge($this->getMetadata()->getModuleList(), array_column($diff['install'], 'id'));

            foreach ($packages as $package) {
                if (!in_array($package['treoId'], $installed)) {
                    $result['list'][] = [
                        'id'          => $package['treoId'],
                        'name'        => $this->packageTranslate($package['name'], $package['treoId']),
                        'description' => $this->packageTranslate($package['description'], '-'),
                        'status'      => $package['status'],
                        'versions'    => $package['versions']
                    ];
                }

            }

            // prepare total
            $result['total'] = count($result['list']);
        }

        return $result;
    }

    /**
     * Install module
     *
     * @param string $id
     * @param string $version
     *
     * @return bool
     * @throws Exceptions\Error
     */
    public function installModule(string $id, string $version = null): bool
    {
        // is changing blocked?
        if ($this->getConfig()->get('isSystemUpdating')) {
            return false;
        }

        // prepare params
        $packagistPackage = $this->getPackagistPackage($id);

        // prepare version
        if (empty($version)) {
            $version = '*';
        }

        // validation
        if (empty($packagistPackage)) {
            throw new Exceptions\Error($this->translateError('No such module'));
        }
        if (!empty($this->getMetadata()->getModule($id))) {
            throw new Exceptions\Error($this->translateError('Such module is already installed'));
        }
        if (!$this->isVersionValid($version)) {
            throw new Exceptions\Error($this->translateError('Version in invalid'));
        }

        // update composer.json
        $this
            ->getComposerService()
            ->update($packagistPackage['packageId'], $version);

        return true;
    }

    /**
     * Update module
     *
     * @param string $id
     * @param string $version
     *
     * @return bool
     * @throws Exceptions\Error
     */
    public function updateModule(string $id, string $version): bool
    {
        // is changing blocked?
        if ($this->getConfig()->get('isSystemUpdating')) {
            return false;
        }

        // prepare params
        $package = $this->getMetadata()->getModule($id);

        // validation
        if (empty($this->getPackagistPackage($id))) {
            throw new Exceptions\Error($this->translateError('No such module'));
        }
        if (empty($package)) {
            throw new Exceptions\Error($this->translateError('Module was not installed'));
        }
        if (!$this->isVersionValid($version)) {
            throw new Exceptions\Error($this->translateError('Version in invalid'));
        }

        // update composer.json
        $this
            ->getComposerService()
            ->update($package['name'], $version);

        return true;
    }

    /**
     * Delete module
     *
     * @param string $id
     *
     * @return bool
     * @throws Exceptions\Error
     */
    public function deleteModule(string $id): bool
    {
        // is changing blocked?
        if ($this->getConfig()->get('isSystemUpdating')) {
            return false;
        }

        // prepare modules
        if ($this->isModuleSystem($id)) {
            throw new Exceptions\Error($this->translateError('isSystem'));
        }

        // prepare params
        $package = $this->getMetadata()->getModule($id);

        // validation
        if (empty($package)) {
            throw new Exceptions\Error($this->translateError('No such module'));
        }

        // update composer.json
        $this
            ->getComposerService()
            ->delete($package['name']);

        return true;
    }

    /**
     * Cancel module changes
     *
     * @param string $id
     *
     * @return bool
     * @throws Exceptions\Error
     */
    public function cancel(string $id): bool
    {
        // is changing blocked?
        if ($this->getConfig()->get('isSystemUpdating')) {
            return false;
        }

        // prepare result
        $result = false;

        // get package
        $package = $this->getPackagistPackage($id);

        if (!empty($name = $package['packageId'])) {
            // get data
            $composerData = $this->getComposerService()->getModuleComposerJson();
            $composerStableData = $this->getComposerService()->getModuleStableComposerJson();

            if (!empty($value = $composerStableData['require'][$name])) {
                $composerData['require'][$name] = $value;
            } elseif (isset($composerData['require'][$name])) {
                unset($composerData['require'][$name]);
            }

            // save
            $this->getComposerService()->setModuleComposerJson($composerData);

            // prepare result
            $result = true;
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
     * Update module file
     *
     * @return bool
     */
    public function updateModuleFile(): bool
    {
        // prepare data
        $data = [];

        // reload modules
        $this->getMetadata()->init(true);

        foreach ($this->getMetadata()->getModuleList() as $module) {
            if (!in_array($module, ['Crm', 'TreoCore'])) {
                $data[$module] = [
                    'order' => $this->createModuleLoadOrder($module)
                ];
            }
        }

        return $this->getFileManager()->putContentsJson($this->moduleJsonPath, $data);
    }

    /**
     * Clear module data from "module.json" file
     *
     * @param string $id
     *
     * @return bool
     */
    public function clearModuleData(string $id): void
    {
        $this
            ->getFileManager()
            ->unsetContents($this->moduleJsonPath, $id);
    }

    /**
     * Init
     */
    protected function init()
    {
        parent::init();
        /**
         * Add dependencies
         */
        $this->addDependencyList(
            [
                'metadata',
                'language',
                'fileManager',
                'serviceFactory'
            ]
        );
    }

    /**
     * Get module status
     *
     * @param array  $diff
     * @param string $id
     *
     * @return mixed
     */
    protected function getModuleStatus(array $diff, string $id)
    {
        foreach ($diff as $status => $row) {
            foreach ($row as $item) {
                if ($item['id'] == $id) {
                    return $status;
                }
            }
        }

        return null;
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

            if (!empty($package = $this->getMetadata()->getModule($moduleId))) {
                if (!empty($composerRequire = $package['require']) && is_array($composerRequire)) {
                    // get treo modules
                    $treoModule = TreoComposer::getModules();

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
     * Is version valid?
     *
     * @param string $version
     *
     * @return bool
     */
    protected function isVersionValid(string $version): bool
    {
        // prepare result
        $result = true;

        // create version parser
        $versionParser = new \Composer\Semver\VersionParser();

        try {
            $versionParser->parseConstraints($version)->getPrettyString();
            if (preg_match("/^(.*)\-$/", $version)) {
                $result = false;
            }
        } catch (\Exception $e) {
            $result = false;
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
        $this->triggered('ModuleManager', $action, $data);
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
     * @param array  $field
     * @param string $default
     *
     * @return string
     */
    protected function packageTranslate(array $field, string $default = ''): string
    {
        // get current language
        $currentLang = $this->getLanguage()->getLanguage();

        $result = $default;
        if (!empty($field[$currentLang])) {
            $result = $field[$currentLang];
        } elseif ($field['default']) {
            $result = $field['default'];
        }

        return $result;
    }

    /**
     * Get packages
     *
     * @param string $id
     *
     * @return array
     * @throws Exceptions\Error
     */
    protected function getPackagistPackage(string $id): array
    {
        return $this->getInjection('serviceFactory')->create('Packagist')->getPackage($id);
    }


    /**
     * Get packages
     *
     * @return array
     * @throws Exceptions\Error
     */
    protected function getPackagistPackages(): array
    {
        return $this->getInjection('serviceFactory')->create('Packagist')->getPackages();
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
