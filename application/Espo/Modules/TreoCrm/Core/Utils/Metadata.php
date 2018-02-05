<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Core\Utils;

use Espo\Core\Utils\Metadata as EspoMetadata;
use Espo\Modules\TreoCrm\Metadata\AbstractMetadata;
use Espo\Modules\TreoCrm\Traits\ContainerTrait;

/**
 * Metadata class
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Metadata extends EspoMetadata
{

    /**
     * Traits
     */
    use ContainerTrait;
    /**
     * @var object
     */
    protected $unifier;

    /**
     * @var object
     */
    protected $fileManager;

    /**
     * @var object
     */
    protected $moduleConfig;

    /**
     * @var object
     */
    protected $metadataHelper;

    /**
     * @var array
     */
    protected $moduleListAll = null;

    /**
     * @var array
     */
    protected $moduleList = null;

    /**
     * @var array
     */
    protected $deletedData = [];

    /**
     * @var array
     */
    protected $changedData = [];

    /**
     * @var string
     */
    protected $moduleMetadataClass = 'Espo\Modules\%s\Metadata\Metadata';

    /**
     * Get all modules
     *
     * @return array
     */
    public function getAllModules(): array
    {
        if (is_null($this->moduleListAll)) {
            // get all
            $modules = $this->getFileManager()->getFileList($this->pathToModules, false, '', false);

            // prepare modules
            $data = [];
            if (is_array($modules)) {
                foreach ($modules as $moduleName) {
                    if (!empty($moduleName) && !isset($data[$moduleName])) {
                        $data[$moduleName] = $this
                                ->getModuleConfig()->get($moduleName.'.order', $this->defaultModuleOrder);
                    }
                }
            }
            // sorting
            array_multisort(array_values($data), SORT_ASC, array_keys($data), SORT_ASC, $data);

            // prepare result
            $this->moduleListAll = array_keys($data);
        }

        return $this->moduleListAll;
    }

    /**
     * Get Module List
     *
     * @return array
     */
    public function getModuleList()
    {
        if (!isset($this->moduleList)) {
            $this->moduleList = [];

            foreach ($this->getAllModules() as $module) {
                if (empty($this->getModuleConfig()->get($module.'.disabled'))) {
                    $this->moduleList[] = $module;
                }
            }
        }

        return $this->moduleList;
    }

    /**
     * Get module config data
     *
     * @param string $module
     *
     * @return mixed
     */
    public function getModuleConfigData(string $module)
    {
        return $this->getModuleConfig()->get($module);
    }

    /**
     * Init metadata
     *
     * @param  boolean $reload
     *
     * @return void
     */
    public function init($reload = false)
    {
        // call parent init
        parent::init($reload);

        // modify metadata by modules
        $this->modulesModification();
    }

    /**
     * Drop metadata cache
     */
    public function dropCache(): void
    {
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }

    /**
     * Modify metadata by modules
     *
     * @return void
     */
    protected function modulesModification()
    {
        foreach ($this->getModuleList() as $module) {
            $className = sprintf($this->moduleMetadataClass, $module);
            if (class_exists($className)) {
                $metadata = (new $className())->setContainer($this->getContainer());
                if ($metadata instanceof AbstractMetadata) {
                    $this->data = $metadata->modify($this->data);
                }
            }
        }
    }
}
