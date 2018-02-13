<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Services;

use Espo\Core\DataManager;
use Espo\Core\Utils\Json;
use Espo\Core\Services\Base;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Exceptions;
use Espo\Modules\TreoCrm\Core\Utils\Metadata;

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
     * @var string
     */
    protected $vendorTreoDir = 'vendor/treo-crm/';

    /**
     * Construct
     */
    public function __construct(...$args)
    {
        parent::__construct(...$args);

        /**
         * Add dependencies
         */
        $this->addDependency('metadata');
        $this->addDependency('language');
        $this->addDependency('fileManager');
        $this->addDependency('dataManager');
    }

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

        foreach ($this->getMetadata()->getAllModules() as $module) {
            if ($this->isModuleAllowed($module)) {
                $result['list'][] = [
                    "id"          => $module,
                    "name"        => $this->translate('moduleNames', $module),
                    "description" => $this->translate('moduleDescriptions', $module),
                    "version"     => $this->getModuleVersion($module),
                    "required"    => $this->prepareRequireds($this->getModuleConfigData("{$module}.required")),
                    "isActive"    => $this->getMetadata()->isModuleActive($module)
                ];
            }
        }

        $result['total'] = count($result['list']);

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
        // get config data
        $config = $this->getModuleConfigData($moduleId);

        // is system module ?
        if (!empty($config['isSystem'])) {
            throw new Exceptions\Error($this->getLanguage()->translate('isSystem', 'exceptions', 'ModuleManager'));
        }

        // checking requireds
        if ($this->hasRequireds($moduleId, $config)) {
            throw new Exceptions\Error($this->getLanguage()->translate('hasRequireds', 'exceptions', 'ModuleManager'));
        }


        // get file data
        $fileData = $this->getFileManager()->getContents($this->moduleJsonPath);

        //prepare json data
        $data                        = (empty($fileData)) ? [] : json_decode($fileData, true);
        $data[$moduleId]['disabled'] = empty($config['disabled']);

        // drop cache
        $this->getMetadata()->dropCache();

        $result = $this->getFileManager()->putContentsJson($this->moduleJsonPath, $data);

        // reload metadata
        $this->getMetadata()->init(true);

        // rebuild DB
        if ($result && !$data[$moduleId]['disabled']) {
            $this->getDataManager()->rebuild();
        }

        return $result;
    }

    /**
     * Is module allowed
     *
     * @param string $module
     *
     * @return bool
     */
    protected function isModuleAllowed(string $module): bool
    {
        // prepare result
        $result = true;

        // get config
        $config = $this->getModuleConfigData($module);

        // hide system
        if ($config['isSystem']) {
            $result = false;
        }

        // find system modules in requireds
        foreach ($this->getMetadata()->getAllModules() as $moduleName) {
            // get config
            $rowConfig = $this->getMetadata()->getModuleConfigData($moduleName);
            if (!empty($rowConfig['required']) && in_array($module, $rowConfig['required']) && $rowConfig['isSystem']) {
                $result = false;

                break;
            }
        }

        return $result;
    }

    /**
     * Prepare requireds
     *
     * @param array $requireds
     *
     * @return array
     */
    protected function prepareRequireds(array $requireds): array
    {
        // prepare result
        $result = [];

        foreach ($requireds as $module) {
            if ($this->isModuleAllowed($module)) {
                $result[] = $module;
            }
        }

        return $result;
    }

    /**
     * Is module has requireds
     *
     * @param string $moduleId
     * @param array $moduleConfig
     *
     * @return bool
     */
    protected function hasRequireds(string $moduleId, array $moduleConfig): bool
    {
        // prepare result
        $result     = false;
        $moduleList = $this->getMetadata()->getModuleList();

        // is module requireds by another modules
        if (empty($moduleConfig['disabled'])) {
            foreach ($moduleList as $module) {
                // get config
                $config = $this->getModuleConfigData($module);
                if (isset($config['required']) && in_array($moduleId, $config['required'])) {
                    // prepare result
                    $result = true;

                    break;
                }
            }
        } elseif (!empty($moduleConfig['required'])) {
            // is module has own requireds
            foreach ($moduleConfig['required'] as $module) {
                if (!in_array($module, $moduleList)) {
                    // prepare result
                    $result = true;

                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Get module version
     *
     * @param string $module
     *
     * @return string
     */
    protected function getModuleVersion(string $module): string
    {
        // prepare result
        $result = '1.0.0';

        if (!empty($package = $this->getComposerPackage($module)) && !empty($package['version'])) {
            $result = $package['version'];
        }

        return $result;
    }

    /**
     * Get composer package
     *
     * @param string $module
     *
     * @return array
     */
    protected function getComposerPackage(string $module): array
    {
        // prepare result
        $result = [];

        // prepare composerLock
        $composerLock = 'composer.lock';

        if (file_exists($this->vendorTreoDir) && is_dir($this->vendorTreoDir) && file_exists($composerLock)) {
            foreach (scandir($this->vendorTreoDir) as $dir) {
                // prepare path
                $path = "{$this->vendorTreoDir}$dir/application/Espo/Modules/$module";

                if (file_exists($path)) {
                    // get data
                    $data = Json::decode(file_get_contents($composerLock), true);

                    if (!empty($packages = $data['packages'])) {
                        foreach ($packages as $package) {
                            if ($package['name'] == "treo-crm/{$dir}") {
                                $result = $package;
                            }
                        }
                    }
                }
            }
        }

        return $result;
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
     * Translate field
     *
     * @param string $tab
     * @param string $key
     *
     * @return string
     */
    protected function translate(string $tab, string $key): string
    {
        return $this->getLanguage()->translate($key, $tab, 'ModuleManager');
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
}
