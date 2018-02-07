<?php
declare(strict_types = 1);

namespace Espo\Modules\Multilang\FieldManager\Hooks;

/**
 * Class TextMultilang
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
class TextMultilang extends AbstractMultilangHook
{
    /**
     * Modified fieldsDefs
     *
     * @var array
     */
    protected $modifedFieldsDefs = ['type' => 'text'];
}
