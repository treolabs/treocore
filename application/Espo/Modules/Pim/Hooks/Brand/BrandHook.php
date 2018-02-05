<?php
declare(strict_types = 1);

namespace Espo\Modules\Pim\Hooks\Brand;

use Espo\Core\Exceptions\BadRequest;
use Espo\Modules\Pim\Core\Hooks\AbstractHook;
use Espo\ORM\Entity;

/**
 * Brand hook
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
class BrandHook extends AbstractHook
{

    /**
     * Before save action
     *
     * @param Entity $entity
     * @param array  $options
     *
     * @throws BadRequest
     */
    public function beforeSave(Entity $entity, $options = [])
    {
        // CODE validation
        if (!$this->isUnique($entity, 'code')) {
            throw new BadRequest('Brand with such CODE already exist');
        }
    }
}
