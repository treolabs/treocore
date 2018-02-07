<?php
declare(strict_types = 1);

namespace Espo\Modules\Multilang\FieldManager\Hooks;

use Espo\Core\Utils\FieldManager\Hooks\Base;

/**
 * Class AbstractMultilangHook
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
abstract class AbstractMultilangHook extends Base
{

    /**
     * Modified fieldsDefs
     *
     * @var array
     */
    protected $modifedFieldsDefs;

    /**
     * Replace defs on modifedFieldsDefs defs
     *
     * @param string $scope
     * @param string $name
     * @param array  $defs
     * @param array  $options
     */
    public function beforeSave(string $scope, string $name, array &$defs, array $options)
    {
        $defs = array_merge($defs, $this->modifedFieldsDefs, ['isMultilang' => true]);
    }
}
