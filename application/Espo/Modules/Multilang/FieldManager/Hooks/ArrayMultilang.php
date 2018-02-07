<?php
declare(strict_types = 1);

namespace Espo\Modules\Multilang\FieldManager\Hooks;

/**
 * Class ArrayMultilang
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
class ArrayMultilang extends AbstractMultilangHook
{
    /**
     * Modified fieldsDefs
     *
     * @var array
     */
    protected $modifedFieldsDefs = ['type' => 'array'];
}
