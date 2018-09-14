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

use Espo\Core\Utils\Config as EspoConfig;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Util;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Module as ModuleConfig;

/**
 * Class of Config
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Config extends EspoConfig
{
    /**
     * @var string
     */
    protected $configPath = 'data/config.php';

    /**
     * @var string
     */
    protected $defaultConfigPath = 'application/Treo/Configs/defaultConfig.php';

    /**
     * @var string
     */
    protected $systemConfigPath = 'application/Treo/Configs/SystemConfig.php';

    /**
     * @var string
     */
    protected $pathToModules = 'application/Espo/Modules';

    /**
     * @var string
     */
    protected $cacheTimestamp = 'cacheTimestamp';

    /**
     * @var array
     */
    protected $adminItems = [];

    /**
     * @var array
     */
    protected $associativeArrayAttributeList
        = [
            'currencyRates',
            'database',
            'logger',
            'defaultPermissions',
        ];

    /**
     * @var array
     */
    protected $moduleData = null;

    /**
     * @var array
     */
    protected $data = null;

    /**
     * @var array
     */
    protected $changedData = [];

    /**
     * @var array
     */
    protected $removeData = [];

    /**
     * @var FileManager
     */
    protected $fileManager;

    /**
     * @var ModuleConfig
     */
    protected $moduleConfig = null;

    /**
     * @var array
     */
    protected $modules = null;

    /**
     * @var array
     */
    protected $composerJsonData = null;

    /**
     * Construct
     */
    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    /**
     * Get config path
     *
     * @return string
     */
    public function getConfigPath(): string
    {
        return $this->configPath;
    }

    /**
     * Get an option from config
     *
     * @param string $name
     * @param string $default
     *
     * @return string | array
     */
    public function get($name, $default = null)
    {
        $keys = explode('.', $name);

        $lastBranch = $this->loadConfig();
        foreach ($keys as $keyName) {
            if (isset($lastBranch[$keyName]) && (is_array($lastBranch) || is_object($lastBranch))) {
                if (is_array($lastBranch)) {
                    $lastBranch = $lastBranch[$keyName];
                } else {
                    $lastBranch = $lastBranch->$keyName;
                }
            } else {
                return $default;
            }
        }

        return $lastBranch;
    }

    /**
     * Whether parameter is set
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        $keys = explode('.', $name);

        $lastBranch = $this->loadConfig();
        foreach ($keys as $keyName) {
            if (isset($lastBranch[$keyName]) && (is_array($lastBranch) || is_object($lastBranch))) {
                if (is_array($lastBranch)) {
                    $lastBranch = $lastBranch[$keyName];
                } else {
                    $lastBranch = $lastBranch->$keyName;
                }
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Set an option to the config
     *
     * @param string $name
     * @param string $value
     *
     * @return bool
     */
    public function set($name, $value = null, $dontMarkDirty = false)
    {
        if (is_object($name)) {
            $name = get_object_vars($name);
        }

        if (!is_array($name)) {
            $name = array($name => $value);
        }

        foreach ($name as $key => $value) {
            if (in_array($key, $this->associativeArrayAttributeList) && is_object($value)) {
                $value = (array)$value;
            }
            $this->data[$key] = $value;
            if (!$dontMarkDirty) {
                $this->changedData[$key] = $value;
            }
        }
    }

    /**
     * Remove an option in config
     *
     * @param  string $name
     *
     * @return bool | null - null if an option doesn't exist
     */
    public function remove($name)
    {
        if (array_key_exists($name, $this->data)) {
            unset($this->data[$name]);
            $this->removeData[] = $name;
            return true;
        }

        return null;
    }

    /**
     * Save
     *
     * @return array
     */
    public function save()
    {
        $values = $this->changedData;

        if (!isset($values[$this->cacheTimestamp])) {
            $values = array_merge($this->updateCacheTimestamp(true), $values);
        }

        $removeData = empty($this->removeData) ? null : $this->removeData;

        $data = include($this->configPath);

        if (is_array($values)) {
            foreach ($values as $key => $value) {
                $data[$key] = $value;
            }
        }

        if (is_array($removeData)) {
            foreach ($removeData as $key) {
                unset($data[$key]);
            }
        }

        $result = $this->getFileManager()->putPhpContents($this->configPath, $data, true);

        if ($result) {
            $this->changedData = array();
            $this->removeData = array();
            $this->loadConfig(true);
        }

        return $result;
    }

    /**
     * Get defaults
     *
     * @return array
     */
    public function getDefaults()
    {
        return $this->getFileManager()->getPhpContents($this->defaultConfigPath);
    }

    /**
     * Get config acording to restrictions for a user
     *
     * @param $isAdmin
     *
     * @return array
     */
    public function getData($isAdmin = null)
    {
        $data = $this->loadConfig();

        $restrictedConfig = $data;
        foreach ($this->getRestrictItems($isAdmin) as $name) {
            if (isset($restrictedConfig[$name])) {
                unset($restrictedConfig[$name]);
            }
        }

        return $restrictedConfig;
    }

    /**
     * Set JSON data acording to restrictions for a user
     *
     * @param $isAdmin
     *
     * @return bool
     */
    public function setData($data, $isAdmin = null)
    {
        $restrictItems = $this->getRestrictItems($isAdmin);

        if (is_object($data)) {
            $data = get_object_vars($data);
        }

        $values = array();
        foreach ($data as $key => $item) {
            if (!in_array($key, $restrictItems)) {
                $values[$key] = $item;
            }
        }

        return $this->set($values);
    }

    /**
     * Update cache timestamp
     *
     * @param $onlyValue - If need to return just timestamp array
     *
     * @return bool | array
     */
    public function updateCacheTimestamp($onlyValue = false)
    {
        $timestamp = array(
            $this->cacheTimestamp => time(),
        );

        if ($onlyValue) {
            return $timestamp;
        }

        return $this->set($timestamp);
    }

    /**
     * Get site URL
     *
     * @return string
     */
    public function getSiteUrl()
    {
        return rtrim($this->get('siteUrl'), '/');
    }

    /**
     * Load config
     *
     * @param  boolean $reload
     *
     * @return array
     */
    protected function loadConfig($reload = false)
    {
        if ($reload || is_null($this->getConfigData())) {
            // prepare config
            $config = [];

            /**
             * Local config
             */
            $configPath = file_exists($this->configPath) ? $this->configPath : $this->defaultConfigPath;
            $localConfig = $this->getFileManager()->getPhpContents($configPath);
            $config = Util::merge($localConfig, $config);

            /**
             * System config
             */
            $systemConfig = $this->getFileManager()->getPhpContents($this->systemConfigPath);
            $config = Util::merge($systemConfig, $config);

            /**
             * Modules config
             */
            $config = Util::merge(['modules' => $this->getModulesConfig()], $config);

            /**
             * Set version from composer
             *
             * @todo remove ot soon
             */
//            $config['version'] = $this->getTreoVersion();

            /**
             * Set allowUnstable param from composer
             */
            $config['allowUnstable'] = $this->getAllowUnstableParam();

            // set config
            $this->setConfigData($config);
        }

        return $this->getConfigData();
    }

    /**
     * Load module config
     *
     * @return array
     */
    protected function getModulesConfig(): array
    {
        // prepare result
        $moduleData = [];

        foreach ($this->getModules() as $module) {
            $filePath = $this->pathToModules . '/' . $module . '/Configs/ModuleConfig.php';
            if (file_exists($filePath)) {
                $moduleConfigData = include $filePath;
                if (!empty($moduleConfigData) && is_array($moduleConfigData)) {
                    $moduleData = Util::merge($moduleData, $moduleConfigData);
                }
            }
        }

        return $moduleData;
    }

    /**
     * Get treo version
     *
     * @return string
     */
    protected function getTreoVersion(): string
    {
        // prepare result
        $result = '1.0.0';

        if (!empty($version = $this->getComposerJsonData()['version'])) {
            $result = $version;
        }

        return $result;
    }

    /**
     * Get allowUnstable param from composer
     *
     * @return bool
     */
    protected function getAllowUnstableParam(): bool
    {
        // prepare result
        $result = false;

        if (!empty($this->getComposerJsonData()['minimum-stability'])
            && $this->getComposerJsonData()['minimum-stability'] == 'RC') {
            $result = true;
        }

        return $result;
    }

    /**
     * Get modules
     *
     * @return array
     */
    protected function getModules(): array
    {
        if (is_null($this->modules)) {
            // prepare result
            $result = [];

            // get all
            $modules = $this->getFileManager()->getFileList($this->pathToModules, false, '', false);

            foreach ($modules as $moduleName) {
                // get module config
                $config = $this->getModuleConfig()->get($moduleName);

                if (empty($config['disabled'])) {
                    $result[$moduleName] = (!empty($config['order'])) ? $config['order'] : 10;
                }
            }
            // sorting
            array_multisort(array_values($result), SORT_ASC, array_keys($result), SORT_ASC, $result);

            // prepare result
            $this->modules = array_keys($result);
        }

        return $this->modules;
    }

    /**
     * Get module config
     *
     * @return ModuleConfig
     */
    protected function getModuleConfig(): ModuleConfig
    {
        if (is_null($this->moduleConfig)) {
            $this->moduleConfig = new ModuleConfig($this->getFileManager(), false);
        }

        return $this->moduleConfig;
    }

    /**
     * Set config data
     *
     * @param array $data
     *
     * @return Config
     */
    protected function setConfigData(array $data): Config
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get config data
     *
     * @return array|null
     */
    protected function getConfigData()
    {
        return $this->data;
    }

    /**
     * Get FileManager
     *
     * @return FileManager
     */
    protected function getFileManager(): FileManager
    {
        return $this->fileManager;
    }

    /**
     * Get admin items
     *
     * @return array
     */
    protected function getRestrictItems($onlySystemItems = null)
    {
        $data = $this->loadConfig();

        if ($onlySystemItems) {
            return $data['systemItems'];
        }

        if (empty($this->adminItems)) {
            $this->adminItems = array_merge($data['systemItems'], $data['adminItems']);
        }

        if ($onlySystemItems === false) {
            return $this->adminItems;
        }

        return array_merge($this->adminItems, $data['userItems']);
    }

    /**
     * Check if an item is allowed to get and save
     *
     * @param $name
     * @param $isAdmin
     *
     * @return bool
     */
    protected function isAllowed($name, $isAdmin = false)
    {
        if (in_array($name, $this->getRestrictItems($isAdmin))) {
            return false;
        }

        return true;
    }

    /**
     * Get composer.json data
     *
     * @return array
     */
    protected function getComposerJsonData(): array
    {
        if (is_null($this->composerJsonData)) {
            // prepare result
            $this->composerJsonData = [];

            // prepare path
            $path = 'composer.json';

            if (file_exists($path)) {
                $this->composerJsonData = Json::decode(file_get_contents($path), true);
            }
        }

        return $this->composerJsonData;
    }
}
