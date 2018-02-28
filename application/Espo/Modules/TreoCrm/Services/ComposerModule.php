<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Services;

use Espo\Core\Services\Base;
use Espo\Core\Utils\Json;
use TreoComposer\AbstractEvent as TreoComposer;

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
    public static $packagistPath = 'https://packagist.zinitsolutions.com';

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
     * Construct
     */
    public function __construct(...$args)
    {
        parent::__construct(...$args);

        // load composer lock
        $this->loadComposerLock();
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

        if (!is_null($moduleId)) {
            $result = (!isset($this->modulePackage[$moduleId])) ? [] : $this->modulePackage[$moduleId];
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

            if (!empty($packagesJson = file_get_contents(self::$packagistPath.'/packages.json'))) {
                // parse json
                $packagesJsonData = Json::decode($packagesJson, true);

                if (!empty($includes = $packagesJsonData['includes']) && is_array($includes)) {
                    foreach ($includes as $path => $row) {
                        if (!empty($includeJson = file_get_contents(self::$packagistPath.'/'.$path))) {
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
     */
    protected function loadModulesPackages(): void
    {
        if (!$this->isModulePackagesLoaded) {
            $this->isModulePackagesLoaded = true;
            foreach ($this->getPackagistData() as $repository => $versions) {
                if (is_array($versions)) {
                    $max = null;
                    foreach ($versions as $version => $data) {
                        if (!empty($treoId = $data['extra']['treoId'])) {
                            if (preg_match_all('/^(v(\d.\d.\d))|(\d.\d.\d)$/', $version, $matches)) {
                                // prepare version
                                $version = (!empty($matches[3][0])) ? $matches[3][0] : $matches[2][0];

                                // set max
                                if ((int) $max < (int) str_replace('.', '', $version)) {
                                    $max                                 = $version;
                                    $this->modulePackage[$treoId]['max'] = $data;
                                }

                                // push
                                $this->modulePackage[$treoId][$version] = $data;
                            }
                        }
                    }
                }
            }
        }
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
        $vendorTreoDir = TreoComposer::VENDOR.'/'.TreoComposer::TREODIR.'/';

        if (file_exists($vendorTreoDir) && is_dir($vendorTreoDir) && file_exists($composerLock)) {
            // prepare data
            $this->composerLockData = Json::decode(file_get_contents($composerLock), true);
        }
    }
}
