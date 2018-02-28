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
use Espo\Modules\TreoCrm\Services\Composer as TreoComposer;

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
    public static $gitServer = 'gitlab.zinit1.com';

    /**
     * @var string
     */
    protected $moduleJsonPath = 'custom/Espo/Custom/Resources/module.json';

    /**
     * @var array
     */
    protected $moduleRequireds = [];

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
        $this->addDependency('serviceFactory');
    }

    /**
     * Get composer user data
     *
     * @return array
     */
    public function getComposerUser(): array
    {
        // prepare result
        $result = [];

        // get auth data
        $authData = $this->getComposerService()->getAuthData();

        if (!empty($authData['http-basic'][self::$gitServer]) && is_array($authData['http-basic'][self::$gitServer])) {
            $result = $authData['http-basic'][self::$gitServer];
        }

        return $result;
    }

    /**
     * Set composer user data
     *
     * @param string $username
     * @param string $password
     *
     * @return bool
     */
    public function setComposerUser(string $username, string $password): bool
    {
        // get auth data
        $authData = $this->getComposerService()->getAuthData();

        // prepare auth data
        $authData['http-basic'][self::$gitServer] = [
            'username' => $username,
            'password' => $password
        ];

        return $this->getComposerService()->setAuthData($authData);
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
                // prepare item
                $item = [
                    "id"               => $module,
                    "name"             => $module,
                    "description"      => '',
                    "version"          => '-',
                    "availableVersion" => '-',
                    "required"         => [],
                    "isActive"         => $this->getMetadata()->isModuleActive($module),
                    "isComposer"       => false
                ];

                // get current module package
                $package = $this->getComposerModuleService()->getModulePackage($module);

                if (!empty($package)) {
                    // get module packages
                    $packages = $this->getComposerModuleService()->getModulePackages($module);

                    // prepare item
                    $item['name']        = $this->translateModule($module, 'name');
                    $item['description'] = $this->translateModule($module, 'description');
                    $item['version']     = $this->prepareModuleVersion($package['version']);
                    $item['required']    = $this->getModuleRequireds($module);
                    $item['isComposer']  = true;

                    if (isset($packages['max'])) {
                        $item['availableVersion'] = $this->prepareModuleVersion($packages['max']['version']);
                    }
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

            foreach ($modules as $moduleId => $packages) {
                // prepare max
                $max = $packages['max'];

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
                    'id'          => $moduleId,
                    'version'     => $max['version'],
                    'name'        => $name,
                    'description' => $description
                ];
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

        // drop cache
        $this->getMetadata()->dropCache();

        // write to file
        $result = $this->updateModuleFile($moduleId, empty($config['disabled']));

        // rebuild DB
        if ($result && !empty($config['disabled'])) {
            $this->getDataManager()->rebuild();
        }

        return $result;
    }

    /**
     * Install module
     *
     * @param string $id
     *
     * @return array
     */
    public function installModule(string $id): array
    {
        // prepare result
        $result = [
            "status" => null,
            "output" => ""
        ];

        $packages = $this->getComposerModuleService()->getModulePackages($id);
        if (!empty($package  = $packages['max'])) {
            // prepare params
            $repo    = $package['name'];
            $version = $package['version'];

            // update modules file
            $this->updateModuleFile($id, true);

            // run composer
            $result = $this->getComposerService()->run("require {$repo}:{$version}");

            // update treo dirs
            TreoComposer::updateTreoModule($id);
        }

        return $result;
    }

    /**
     * Update module
     *
     * @param string $id
     * @param string $version
     *
     * @return array
     */
    public function updateModule(string $id, string $version): array
    {
        // prepare result
        $result = [
            "status" => null,
            "output" => ""
        ];

        $packages = $this->getComposerModuleService()->getModulePackages($id);
        if (!empty($package  = $packages[$version])) {
            // prepare params
            $repo = $package[$version];

            // update modules file
            $this->updateModuleFile($id, true);

            $result = $this->getComposerService()->run("require {$repo}:{$version}");

            // update treo dirs
            TreoComposer::updateTreoModule($id);
        }

        return $result;
    }

    /**
     * Delete module
     *
     * @param string $id
     *
     * @return array
     */
    public function deleteModule(string $id): array
    {
        // prepare result
        $result = [
            "status" => null,
            "output" => ""
        ];

        $packages = $this->getComposerModuleService()->getModulePackages($id);

        if (!empty($package = $packages['max'])) {
            // prepare params
            $repo = $package['name'];

            // update modules file
            $this->updateModuleFile($id, true);

            // run composer
            $result = $this->getComposerService()->run("remove {$repo}");

            if (empty($result['status'])) {
                // delete treo dirs
                TreoComposer::deleteTreoModule($id);
            }
        }

        return $result;
    }

    /**
     * Update module file
     *
     * @param string $moduleId
     * @param bool $isDisabled
     *
     * @return bool
     */
    protected function updateModuleFile(string $moduleId, bool $isDisabled): bool
    {
        // prepare data
        $data = [];

        foreach ($this->getMetadata()->getAllModules() as $module) {
            if (!in_array($module, ['Crm', 'TreoCrm'])) {
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

            // get requireds
            $requireds = $this->getModuleRequireds($module);

            if (!empty($requireds) && in_array($module, $requireds) && $rowConfig['isSystem']) {
                $result = false;

                break;
            }
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

            // get trep modules
            $treoModule = TreoComposer::getTreoModules();

            if (array_key_exists($moduleId, $treoModule)) {
                // get composer json
                $path = "vendor/".TreoComposer::TREODIR."/".$treoModule[$moduleId]."/composer.json";

                if (file_exists($path)) {
                    $composerRequire = Json::decode(file_get_contents($path), true)['require'];
                    if (!empty($composerRequire) && is_array($composerRequire)) {
                        foreach ($composerRequire as $key => $version) {
                            if (preg_match_all("/^(".TreoComposer::TREODIR."\/)(.*)$/", $key, $matches)) {
                                if (!empty($matches[2][0])) {
                                    $this->moduleRequireds[$moduleId][] = array_flip($treoModule)[$matches[2][0]];
                                }
                            }
                        }
                    }
                }
            }
        }

        return $this->moduleRequireds[$moduleId];
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
        $result = false;

        // get module list
        $moduleList = $this->getMetadata()->getModuleList();

        // get module requireds
        $moduleRequireds = $this->getModuleRequireds($moduleId);

        // is module requireds by another modules
        if (empty($moduleConfig['disabled'])) {
            foreach ($moduleList as $module) {
                // get config
                $config = $this->getModuleConfigData($module);

                // get module requireds
                $requireds = $this->getModuleRequireds($module);

                if (isset($requireds) && in_array($moduleId, $requireds)) {
                    // prepare result
                    $result = true;

                    break;
                }
            }
        } elseif (!empty($moduleRequireds)) {
            // is module has own requireds
            foreach ($moduleRequireds as $module) {
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
