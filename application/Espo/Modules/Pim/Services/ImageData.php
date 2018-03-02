<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM ist Open Source Product Information Managegement (PIM) application,
 * based on EspoCRM.
 * Copyright (C) 2017-2018 Zinit Solutions GmbH
 * Website: http://www.treopim.com
 *
 * TreoPIM as well es EspoCRM is free software: you can redistribute it and/or modify
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

declare(strict_types = 1);

namespace Espo\Modules\Pim\Services;

/**
 * ImageData service
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class ImageData extends AbstractService
{

    /**
     * Set image data by cron
     *
     * @param array $data
     *
     * @return bool
     */
    public function cron(array $data): bool
    {
        // prepare result
        $result = false;

        if (isset($data['entityName']) && isset($data['entityId'])) {
            // get image entity
            $entity = $this->getEntityManager()->getEntity($data['entityName'], $data['entityId']);

            if (!empty($entity)) {
                // prepare data
                $data = [];
                switch ($entity->get('type')) {
                    case 'File':
                        // set alt image
                        if (empty($entity->get('alt'))) {
                            $data['alt'] = $entity->get('imageName');
                        }

                        // prepare image data
                        $image    = $entity->get('image');
                        $filePath = $this->getEntityManager()->getRepository('Attachment')->getFilePath($image);

                        // get image sizes
                        $imageBytes = $image->get('size');
                        $imageSize  = getimagesize($filePath);

                        // set fetched value to avoid looping
                        $entity->setFetched('imageId', $entity->get('imageId'));
                        break;
                    case 'Link':
                        $imageLink = $entity->get('imageLink');
                        // set alt image
                        if (empty($entity->get('alt'))) {
                            $data['alt'] = pathinfo($imageLink, PATHINFO_FILENAME);
                        }
                        // get image sizes
                        $imageBytes = get_headers($imageLink, 1)['Content-Length'];
                        $imageSize  = getimagesize($imageLink);
                        // set fetched value to avoid looping
                        $entity->setFetched('imageLink', $imageLink);
                        break;
                }
                // set image sizes
                $data['size']   = round($imageBytes / pow(2, 20), 2);
                $data['width']  = $imageSize[0];
                $data['height'] = $imageSize[1];
                $data['state']  = 'processed';

                // update data image
                $entity->setFetched('type', $entity->get('type'));
                $entity->set($data);

                $this->getEntityManager()->saveEntity($entity, ['isImageDataSaved' => true]);
            }

            // prepare result
            $result = true;
        }

        return $result;
    }
}
