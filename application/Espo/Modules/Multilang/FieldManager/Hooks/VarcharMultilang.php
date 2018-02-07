<?php
declare(strict_types = 1);

namespace Espo\Modules\Multilang\FieldManager\Hooks;

/**
 * Class VarcharMultilang
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
class VarcharMultilang extends AbstractMultilangHook
{
    /**
     * Modified fieldsDefs
     *
     * @var array
     */
    protected $modifedFieldsDefs = ['type' => 'varchar'];
}
