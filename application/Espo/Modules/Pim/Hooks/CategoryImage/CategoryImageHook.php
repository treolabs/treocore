<?php

namespace Espo\Modules\Pim\Hooks\CategoryImage;

use Espo\Modules\Pim\Core\Hooks\AbstractImageHook;
use Espo\ORM\Entity;

/**
 * CategoryImageHook hook
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
class CategoryImageHook extends AbstractImageHook
{
    /**
     * @var string
     */
    protected $entityName = 'CategoryImage';

    /**
     * Return condition for query
     *
     * @param Entity $entity
     *
     * @return array
     */
    protected function getCondition(Entity $entity)
    {
        return ['categoryId' => $entity->get('categoryId')];
    }
}
