<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2018 TreoLabs GmbH
 * Website: http://www.treopim.com
 *
 * TreoPIM as well as EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TreoPIM as well as EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word
 * and "TreoPIM" word.
 */

declare(strict_types=1);

namespace Treo\Services;

use Espo\ORM\Entity;
use Espo\Core\Exceptions\Error;

/**
 * Class Image
 *
 * @author r.ratsun@treolabs.com
 */
class Image extends \Treo\Core\Templates\Services\Base
{
    /**
     * @inheritdoc
     */
    protected function beforeCreateEntity(Entity $entity, $data)
    {
        if (!$this->isCodeValid($entity, 'name')) {
            throw new Error($this->translate('Code is invalid', 'exceptions'));
        }
    }

    /**
     * @inheritdoc
     */
    protected function afterCreateEntity(Entity $entity, $data)
    {
        // parent
        parent::afterCreateEntity($entity, $data);

        // parse image data
        $this->parseImage($entity);
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    protected function parseImage(Entity $entity): bool
    {
        if (!empty($entity->get('isLink'))) {
            return false;
        }

        // get attachment
        $attachment = $entity->get('imageFile');

        // get file path
        $filePath = $this
            ->getEntityManager()
            ->getRepository('Attachment')
            ->getFilePath($attachment);

        // get image sizes
        $imageBytes = $attachment->get('size');
        $imageSize = getimagesize($filePath);

        // prepare entity
        $entity->set('size', round($imageBytes / pow(2, 20), 2));
        $entity->set('width', $imageSize[0]);
        $entity->set('height', $imageSize[1]);
        $entity->set('mimeType', $attachment->get('type'));
        if (empty($entity->get('alt'))) {
            $entity->set('alt', $entity->get('imageFileName'));
        }

        // save
        $this->getEntityManager()->saveEntity($entity, ['skipAll' => true]);

        return true;
    }
}
