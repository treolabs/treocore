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

namespace Treo\Core\Utils;

use Espo\Core\Utils\Util;
use Espo\Core\Utils\Module;

/**
 * Class of Config
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Config extends \Espo\Core\Utils\Config
{
    /**
     * @var null|array
     */
    protected $modules = null;

    /**
     * Get modules
     *
     * @param bool $force
     *
     * @return array
     */
    public function getModules(bool $force = false): array
    {
        if (is_null($this->modules) || $force) {
            // prepare result
            $this->modules = [];

            // create moduleConfig
            $moduleConfig = $this->getModuleUtil();

            $modules = $this->getFileManager()->getFileList("application/Espo/Modules/", false, '', false);
            $toSort = [];
            if (is_array($modules)) {
                foreach ($modules as $moduleName) {
                    if (!empty($moduleName) && !isset($toSort[$moduleName])) {
                        $toSort[$moduleName] = $moduleConfig->get($moduleName . '.order', 10);
                    }
                }
            }

            array_multisort(array_values($toSort), SORT_ASC, array_keys($toSort), SORT_ASC, $toSort);

            // prepare result
            $this->modules = array_keys($toSort);
        }

        return $this->modules;
    }

    /**
     * @inheritdoc
     */
    public function getDefaults()
    {
        return array_merge(parent::getDefaults(), include "application/Treo/Configs/defaultConfig.php");
    }

    /**
     * Get module util
     *
     * @return Module
     */
    protected function getModuleUtil()
    {
        return new Module($this->getFileManager()) ;
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
        // load config
        $config = parent::loadConfig($reload);

        // set treo ID
        if (!isset($config['treoId'])) {
            $config['treoId'] = $this->getTreoId();
        }

        // inject modules
        $config = Util::merge(['modules' => $this->getModulesConfig()], $config);

        return $config;
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
            $filePath = "application/Espo/Modules/$module/Configs/ModuleConfig.php";
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
     * @return string
     */
    protected function getTreoId(): string
    {
        // get auth data
        $authData = (new \Treo\Core\Utils\Composer())->getAuthData();

        return base64_encode($authData['username'] . '-treo-' . $authData['password']);
    }
}
