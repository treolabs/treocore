<?php
declare(strict_types = 1);

namespace Espo\Modules\Multilang\Core\Utils;

use Espo\Modules\TreoCrm\Core\Utils\FieldManager as TreoFieldManager;
use Espo\Core\Utils\Util;

/**
 * FieldManager util
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class FieldManager extends TreoFieldManager
{
    /**
     * @var array
     */
    protected $multilangConfig = null;

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
        // get field type
        $fieldType = $this->getMetadata()->get('entityDefs.'.$scope.'.fields.'.$name.'.type');

        // prepare result
        $result = parent::getAttributeListByType($scope, $name, $type);

        // for multilang fields
        if (in_array($fieldType, $this->getMultilangFields()) && !empty($defs = $this->getMFieldDefs($fieldType))) {
            $result = [$name];
            if (isset($defs[$type.'Fields'])) {
                foreach ($defs[$type.'Fields'] as $locale) {
                    $result[] = Util::toCamelCase($name.'_'.$locale);
                }
            }
        }

        return !empty($result) ? $result : [];
    }

    /**
     * Get field defs
     *
     * @param string $fieldType
     *
     * @return array
     */
    protected function getMFieldDefs(string $fieldType): array
    {
        $defs = $this->getMetadata()->get('fields.'.$fieldType);
        if (is_object($defs)) {
            $defs = get_object_vars($defs);
        }

        return !empty($defs) ? $defs : [];
    }

    /**
     * Get multilang fields
     *
     * @return array
     */
    protected function getMultilangFields(): array
    {
        // get config
        $config = $this->getMultilangConfig();

        return (!empty($config['multilangFields'])) ? $config['multilangFields'] : [];
    }

    /**
     * Get multilang config
     *
     * @return array
     */
    protected function getMultilangConfig(): array
    {
        if (is_null($this->multilangConfig)) {
            $this->multilangConfig = include 'application/Espo/Modules/Multilang/Configs/Config.php';
        }

        return $this->multilangConfig;
    }
}
