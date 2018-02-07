<?php
declare(strict_types = 1);

namespace Espo\Modules\Multilang\FieldManager\Hooks;

/**
 * Class EnumMultilang
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
class EnumMultilang extends AbstractMultilangHook
{
    /**
     * Modified fieldsDefs
     *
     * @var array
     */
    protected $modifedFieldsDefs = ['type' => 'enum'];
}
