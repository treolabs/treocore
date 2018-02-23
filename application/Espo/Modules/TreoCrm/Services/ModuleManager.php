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
use TreoComposer\AbstractEvent as TreoComposer;

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
    protected $treoModules = null;

    /**
     * @var array
     */
    protected $moduleRequireds = [];

    /**
     * @var string
     */
    protected $gitServer = 'gitlab.zinit1.com';

    /**
     * @var string
     */
    protected $packages = 'https://packagist.zinitsolutions.com/packages.json';

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

        if (!empty($authData['http-basic'][$this->gitServer]) && is_array($authData['http-basic'][$this->gitServer])) {
            $result = $authData['http-basic'][$this->gitServer];
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
        $authData['http-basic'][$this->gitServer] = [
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
                $result['list'][] = [
                    "id"          => $module,
                    "name"        => $this->translateModule('moduleNames', $module),
                    "description" => $this->translateModule('moduleDescriptions', $module),
                    "version"     => $this->getModuleVersion($module),
                    "required"    => $this->prepareRequireds($this->getModuleRequireds($module)),
                    "isActive"    => $this->getMetadata()->isModuleActive($module)
                ];
            }
        }

        $result['total'] = count($result['list']);

        // sorting
        usort($result['list'], [$this, 'moduleListSort']);

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
            "status" => false,
            "output" => ""
        ];

        // get trep modules
        $treoModule = $this->getTreoModules();

        if (array_key_exists($id, $treoModule)) {
            // prepare repo
            $repo = TreoComposer::TREODIR."/".$treoModule[$id];

            return $this->getComposerService()->run("require {$repo}:{$version}");
        }

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
            // get config data
            $config = $this->getModuleConfigData($module);

            if (empty($config['isSystem'])) {
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

        if (!empty($requireds = $this->getModuleRequireds($moduleId))) {
            $max = 0;

            foreach ($requireds as $require) {
                $requireMax = $this->createModuleLoadOrder($require);
                if ($requireMax > $max) {
                    $max = $requireMax;
                }
            }

            $result = $max + 100;
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
            $treoModule = $this->getTreoModules();

            if (array_key_exists($moduleId, $treoModule)) {
                // get composer json
                $path = TreoComposer::VENDOR."/".TreoComposer::TREODIR."/".$treoModule[$moduleId]."/composer.json";

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

        return $this->prepareModuleVersion($result);
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

        // prepare dir
        $vendorTreoDir = TreoComposer::VENDOR.'/'.TreoComposer::TREODIR.'/';

        if (file_exists($vendorTreoDir) && is_dir($vendorTreoDir) && file_exists($composerLock)) {
            // prepare module key
            $key = $this->getTreoModules()[$module];

            // get data
            $data = Json::decode(file_get_contents($composerLock), true);

            if (!empty($packages = $data['packages'])) {
                foreach ($packages as $package) {
                    if ($package['name'] == TreoComposer::TREODIR."/{$key}") {
                        $result = $package;
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
     * Get Composer service
     *
     * @return Composer
     */
    protected function getComposerService(): Composer
    {
        return $this->getInjection('serviceFactory')->create('Composer');
    }

    /**
     * Translate field
     *
     * @param string $tab
     * @param string $key
     *
     * @return string
     */
    protected function translateModule(string $tab, string $key): string
    {
        // default translate
        $translate = $key;

        // get language
        $lang = $this->getLanguage()->getLanguage();

        // prepare path
        $path = "application/Espo/Modules/{$key}/Resources/i18n/{$lang}/ModuleManager.json";

        if (file_exists($path)) {
            $data = Json::decode(file_get_contents($path), true);

            if (!empty($data[$tab][$key])) {
                $translate = $data[$tab][$key];
            }
        }

        return $translate;
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
     * Get treo modules
     *
     * @return array
     */
    protected function getTreoModules(): array
    {
        if (is_null($this->treoModules)) {
            $this->treoModules = TreoComposer::getTreoModules();
        }

        return $this->treoModules;
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
