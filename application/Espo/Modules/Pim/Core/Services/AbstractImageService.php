<?php

namespace Espo\Modules\Pim\Core\Services;

use Espo\Core\Templates\Services\Base;
use Espo\Core\Exceptions;
use Espo\Core\Templates\Entities\Base as BaseEntity;

/**
 * Class of AbstractImageService
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
abstract class AbstractImageService extends Base
{

    /**
     * Create entity
     *
     * @param array $data
     * @return BaseEntity
     * @throws Exceptions\Error
     */
    public function createEntity($data)
    {
        if ($data['type'] === 'File' && !$this->isValidImageName($data['imageName'])) {
            throw new Exceptions\Error('Wrong file type. '.implode(', ', $this->getAllowedImageTypes()).' allowed.');
        } elseif ($data['type'] === 'Link' && !$this->isValidImageLink($data['imageLink'])) {
            throw new Exceptions\Error('Wrong image link.');
        }

        return parent::createEntity($data);
    }

    /**
     * Is valid image name ?
     *
     * @param string $name
     * @return boolean
     */
    protected function isValidImageName($name)
    {
        // parse image name
        $type = strtoupper(end(explode('.', $name)));

        return in_array($type, $this->getAllowedImageTypes());
    }

    /**
     * Get allowed image file type
     *
     * @return array
     */
    protected function getAllowedImageTypes()
    {
        return ['GIF', 'JPEG', 'PNG', 'JPG'];
    }

    /**
     * Is valid image url?
     *
     * @param  string $link
     * @return bool
     */
    protected function isValidImageLink($link)
    {
        return (bool) exif_imagetype($link);
    }
}
