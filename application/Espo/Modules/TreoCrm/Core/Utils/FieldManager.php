<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Core\Utils;

use Espo\Modules\TreoCrm\Traits\ContainerTrait;
use Espo\Modules\TreoCrm\Core\Utils\Metadata;
use Espo\Core\Utils\FieldManager as EspoFieldManager;
use Espo\Core\Utils\Metadata\Helper as MetadataHelper;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\FieldManager\Hooks\Base as BaseHook;

/**
 * FieldManager util
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class FieldManager extends EspoFieldManager
{

    use ContainerTrait;
    /**
     *
     * @var MetadataHelper
     */
    protected $metadataHelper = null;

    /**
     * Construct
     */
    public function __construct()
    {
        // blocking parent construct
    }

    /**
     * Get attribute list by type
     *
     * @param string $scope
     * @param string $name
     * @param string $type
     *
     * @return array
     */
    protected function getAttributeListByType(string $scope, string $name, string $type): array
    {
        $fieldType = $this->getMetadata()->get('entityDefs.'.$scope.'.fields.'.$name.'.type');

        if (!$fieldType) {
            return [];
        }

        $defs = $this->getMetadata()->get('fields.'.$fieldType);
        if (!$defs) {
            return [];
        }

        if (is_object($defs)) {
            $defs = get_object_vars($defs);
        }

        $fieldList = [];
        if (isset($defs[$type.'Fields'])) {
            $list   = $defs[$type.'Fields'];
            $naming = 'suffix';
            if (isset($defs['naming'])) {
                $naming = $defs['naming'];
            }
            if ($naming == 'prefix') {
                foreach ($list as $f) {
                    $fieldList[] = $f.ucfirst($name);
                }
            } else {
                foreach ($list as $f) {
                    $fieldList[] = $name.ucfirst($f);
                }
            }
        } else {
            if ($type == 'actual') {
                $fieldList[] = $name;
            }
        }

        return $fieldList;
    }

    /**
     * Get actual attribute list
     *
     * @param string $scope
     * @param string $name
     *
     * @return array
     */
    public function getActualAttributeList($scope, $name)
    {
        return $this->getAttributeListByType($scope, $name, 'actual');
    }

    /**
     * Get not actual attribute list
     *
     * @param string $scope
     * @param string $name
     *
     * @return array
     */
    public function getNotActualAttributeList($scope, $name)
    {
        return $this->getAttributeListByType($scope, $name, 'notActual');
    }

    /**
     * Get attribute list
     *
     * @param string $scope
     * @param string $name
     *
     * @return array
     */
    public function getAttributeList($scope, $name)
    {
        // prepare data
        $actualAttributeList    = $this->getActualAttributeList($scope, $name);
        $notActualAttributeList = $this->getNotActualAttributeList($scope, $name);

        return array_merge($actualAttributeList, $notActualAttributeList);
    }

    /**
     * Get metadata
     *
     * @return Metadata
     */
    protected function getMetadata()
    {
        return $this->getContainer()->get('metadata');
    }

    /**
     * Get language
     *
     * @return Language
     */
    protected function getLanguage()
    {
        return $this->getContainer()->get('language');
    }

    /**
     * Get metadata helper
     *
     * @return MetadataHelper
     */
    protected function getMetadataHelper()
    {
        if (is_null($this->metadataHelper)) {
            $this->metadataHelper = new MetadataHelper($this->getMetadata());
        }

        return $this->metadataHelper;
    }

    /**
     * Get default language
     *
     * @return Language
     */
    protected function getDefaultLanguage()
    {
        return $this->getContainer()->get('defaultLanguage');
    }

    /**
     * Get hook for fields
     *
     * @param $type
     *
     * @return BaseHook|null
     */
    protected function getHook($type)
    {
        $hook = null;

        $className = $this->getMetadata()->get(['fields', $type, 'hookClassName']);

        if (!empty($className) && class_exists($className)) {
            // create hook
            $hook = new $className();

            // inject dependencies
            foreach ($hook->getDependencyList() as $name) {
                $hook->inject($name, $this->getContainer()->get($name));
            }
        } else {
            $GLOBALS['log']->error("Field Manager hook class '{$className}' does not exist.");
        }

        return $hook;
    }
}
