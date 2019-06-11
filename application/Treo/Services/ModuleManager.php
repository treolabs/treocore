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

namespace Treo\Services;

use Espo\Core\Exceptions;
use Slim\Http\Request;
use Treo\Core\ModuleManager as TreoModuleManager;

/**
 * ModuleManager service
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ModuleManager extends AbstractService
{
    /**
     * Get list
     *
     * @return array
     * @throws Exceptions\Error
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
        foreach ($this->getInstalledModules() as $id => $module) {
            $result['list'][$id] = [
                'id'             => $id,
                'name'           => (empty($module->getName())) ? $id : $module->getName(),
                'description'    => $module->getDescription(),
                'settingVersion' => '*',
                'currentVersion' => $module->getVersion(),
                'versions'       => [],
                'required'       => [],
                'isSystem'       => $module->isSystem(),
                'isComposer'     => true,
                'status'         => $this->getModuleStatus($composerDiff, $id),
            ];

            // set available versions
            if (!empty($package = $this->getPackage($id))) {
                $result['list'][$id]['versions'] = json_decode(json_encode($package->get('versions')), true);
            }

            // set settingVersion
            if ($composerData['require'][$module->getComposerName()]) {
                $settingVersion = $composerData['require'][$module->getComposerName()];
                $result['list'][$id]['settingVersion'] = TreoModuleManager::prepareVersion($settingVersion);
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

            // get package
            if (!empty($package = $this->getPackage($row['id']))) {
                // set name
                $item['name'] = $package->get('name');

                // set description
                $item['description'] = $package->get('description');

                // set settingVersion
                if (!empty($composerData['require'][$package->get('packageId')])) {
                    $settingVersion = $composerData['require'][$package->get('packageId')];
                    $item['settingVersion'] = TreoModuleManager::prepareVersion($settingVersion);
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
        // prepare version
        if (empty($version)) {
            $version = '*';
        }

        // validation
        if (empty($package = $this->getPackage($id))) {
            throw new Exceptions\Error($this->translateError('No such module'));
        }
        if (!empty($this->getInstalledModule($id))) {
            throw new Exceptions\Error($this->translateError('Such module is already installed'));
        }
        if (!$this->isVersionValid($version)) {
            throw new Exceptions\Error($this->translateError('Version in invalid'));
        }

        // update composer.json
        $this->getComposerService()->update($package->get('packageId'), $version);

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
        // prepare params
        $package = $this->getInstalledModule($id);

        // validation
        if (empty($this->getPackage($id))) {
            throw new Exceptions\Error($this->translateError('No such module'));
        }
        if (empty($package)) {
            throw new Exceptions\Error($this->translateError('Module was not installed'));
        }
        if (!$this->isVersionValid($version)) {
            throw new Exceptions\Error($this->translateError('Version in invalid'));
        }

        // update composer.json
        $this->getComposerService()->update($package->getComposerName(), $version);

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
        // get module
        $package = $this->getInstalledModule($id);

        // prepare modules
        if ($package->isSystem($id)) {
            throw new Exceptions\Error($this->translateError('isSystem'));
        }

        // validation
        if (empty($package)) {
            throw new Exceptions\Error($this->translateError('No such module'));
        }

        // update composer.json
        $this->getComposerService()->delete($package->getComposerName());

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
        // prepare result
        $result = false;

        // get package
        $package = $this->getPackage($id);

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

        $result['total'] = $this->getNoteCount($where);

        if ($result['total'] > 0) {
            if (!empty($request->get('after'))) {
                $where['whereClause']['createdAt>'] = $request->get('after');
            }

            // get collection
            $result['list'] = $this->getNoteData($where);
        }

        return $result;
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
     * Translate error
     *
     * @param string $key
     *
     * @return string
     */
    protected function translateError(string $key): string
    {
        return $this->getContainer()->get('language')->translate($key, 'exceptions', 'ModuleManager');
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
     * Get Composer service
     *
     * @return Composer
     */
    protected function getComposerService(): Composer
    {
        return $this->getContainer()->get('serviceFactory')->create('Composer');
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

    /**
     * Get note count
     *
     * @param array $where
     *
     * @return int
     */
    protected function getNoteCount(array $where): int
    {
        return $this
            ->getEntityManager()
            ->getRepository('Note')
            ->count(['whereClause' => $where['whereClause']]);
    }

    /**
     * Get note data
     *
     * @param array $where
     *
     * @return array
     */
    protected function getNoteData(array $where): array
    {
        $entities = $this
            ->getEntityManager()
            ->getRepository('Note')
            ->find($where);

        return !empty($entities) ? $entities->toArray() : [];
    }

    /**
     * @return array
     */
    private function getInstalledModules(): array
    {
        return $this->getContainer()->get('moduleManager')->getModules();
    }

    /**
     * @return mixed
     */
    private function getInstalledModule(string $id)
    {
        return $this->getContainer()->get('moduleManager')->getModule($id);
    }

    /**
     * @param string $id
     *
     * @return mixed
     * @throws Exceptions\Error
     */
    private function getPackage(string $id)
    {
        return $this->getEntityManager()->getEntity('TreoStore', $id);
    }
}
