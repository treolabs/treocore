<?php
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
