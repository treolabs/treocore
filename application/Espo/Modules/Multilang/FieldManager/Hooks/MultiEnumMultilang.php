<?php
declare(strict_types = 1);

namespace Espo\Modules\Multilang\FieldManager\Hooks;

/**
 * Class MultiEnumMultilang
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
class MultiEnumMultilang extends AbstractMultilangHook
{
    /**
     * Modified fieldsDefs
     *
     * @var array
     */
    protected $modifedFieldsDefs = ['type' => 'multiEnum'];
}
