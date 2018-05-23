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
use Espo\Core\Utils\Json;
use Espo\Modules\TreoCore\Services\Composer as TreoComposer;

/**
 * ComposerModule service
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class ComposerModule extends Base
{
    /**
     * @var string
     */
    public static $packagistPath = 'https://packagist.treopim.com';

    /**
     * @var array
     */
    protected $packagistData = null;

    /**
     * @var bool
     */
    protected $isModulePackagesLoaded = false;

    /**
     * @var array
     */
    protected $modulePackage = [];

    /**
     * @var array
     */
    protected $composerLockData = null;

    /**
     * @var string
     */
    protected $cacheFile = 'data/cache/modules-packages.json';

    /**
     * Construct
     */
    public function __construct(...$args)
    {
        parent::__construct(...$args);

        // load composer lock
        $this->loadComposerLock();
    }

    /**
     * Caching packages
     *
     * @param array $data
     */
    public function cachingPackages(array $data = []): void
    {
        // load module packages
        $this->loadModulesPackages(true);
    }

    /**
     * Get current module package
     *
     * @param string $module
     *
     * @return array
     */
    public function getModulePackage(string $module): array
    {
        // prepare result
        $result = [];

        if (!empty($packages = $this->composerLockData['packages'])) {
            foreach ($packages as $package) {
                if (!empty($package['extra']['treoId']) && $module == $package['extra']['treoId']) {
                    $result = $package;

                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Get module(s) packages
     *
     * @param string $moduleId
     *
     * @return array
     */
    public function getModulePackages(string $moduleId = null): array
    {
        // load module packages
        $this->loadModulesPackages();

        // prepare result
        $result = $this->modulePackage;

        // set max
        foreach ($result as $module => $versions) {
            $max = null;
            foreach ($versions as $version => $row) {
                $version = (int)str_replace(['.', 'v'], ['', ''], $version);
                if ($version > $max) {
                    $result[$module]['max'] = $row;
                }
            }
        }

        if (!is_null($moduleId)) {
            $result = (!isset($result[$moduleId])) ? [] : $result[$moduleId];
        }

        return $result;
    }

    /**
     * Get packagist data
     *
     * @return array
     */
    public function getPackagistData(): array
    {
        if (is_null($this->packagistData)) {
            // prepare result
            $this->packagistData = [];

            if (!empty($packagesJson = file_get_contents(self::$packagistPath . '/packages.json'))) {
                // parse json
                $packagesJsonData = Json::decode($packagesJson, true);

                if (!empty($includes = $packagesJsonData['includes']) && is_array($includes)) {
                    foreach ($includes as $path => $row) {
                        if (!empty($includeJson = file_get_contents(self::$packagistPath . '/' . $path))) {
                            // parse json
                            $includeJsonData = Json::decode($includeJson, true);

                            if (!empty($packages = $includeJsonData['packages']) && is_array($packages)) {
                                $this->packagistData = array_merge_recursive($this->packagistData, $packages);
                            }
                        }
                    }
                }
            }
        }

        return $this->packagistData;
    }

    /**
     * Load module packages
     *
     * @param bool $droppingCache
     */
    protected function loadModulesPackages(bool $droppingCache = false): void
    {
        if (!$this->isModulePackagesLoaded) {
            $this->isModulePackagesLoaded = true;

            if ($droppingCache || empty($this->getCachedPackagistData())) {
                // prepare packages
                foreach ($this->getPackagistData() as $repository => $versions) {
                    if (is_array($versions)) {
                        foreach ($versions as $version => $data) {
                            if (!empty($data['extra']['treoId'])) {
                                $treoId = $data['extra']['treoId'];
                                $version = strtolower($version);
                                if (preg_match_all('/^v\d.\d.\d$/', $version, $matches)
                                    || preg_match_all('/^v\d.\d.\d-rc\d$/', $version, $matches)
                                    || preg_match_all('/^\d.\d.\d$/', $version, $matches)
                                    || preg_match_all('/^\d.\d.\d-rc\d$/', $version, $matches)
                                ) {
                                    // prepare version
                                    $version = str_replace('v', '', $matches[0][0]);

                                    // set row
                                    $this->modulePackage[$treoId][$version] = $data;
                                }
                            }
                        }
                    }
                }

                // caching
                $this->cachingPackagistData($this->modulePackage);
            }

            // load from cache
            $this->modulePackage = $this->getCachedPackagistData();
        }
    }

    /**
     * Get cached packagist data
     *
     * @return array
     */
    protected function getCachedPackagistData(): array
    {
        // prepare result
        $result = [];

        if (file_exists($this->cacheFile)) {
            $data = file_get_contents($this->cacheFile);
            if (!empty($data)) {
                $result = Json::decode($data, true);
            }
        }

        return $result;
    }

    /**
     * Caching packagist data
     *
     * @param array $data
     */
    protected function cachingPackagistData(array $data): void
    {
        $fp = fopen($this->cacheFile, 'w');
        fwrite($fp, Json::encode($data));
        fclose($fp);
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
        $vendorTreoDir = 'vendor/' . TreoComposer::TREODIR . '/';

        if (file_exists($vendorTreoDir) && is_dir($vendorTreoDir) && file_exists($composerLock)) {
            // prepare data
            $this->composerLockData = Json::decode(file_get_contents($composerLock), true);
        }
    }
}
