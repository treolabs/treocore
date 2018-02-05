<?php

namespace Espo\Modules\Pim\Core\Hooks;

use Espo\Core\CronManager;
use Espo\Core\Hooks\Base as BaseHook;
use Espo\ORM\Entity;

/**
 * AbstractImageHook hook
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
abstract class AbstractImageHook extends BaseHook
{
    /**
     * @var string
     */
    protected $entityName = null;

    /**
     * @param $entity
     *
     * @return string
     */
    abstract protected function getCondition(Entity $entity);

    /**
     * Before Save hook
     *
     * @param Entity $entity
     * @param array  $options
     */
    public function beforeSave(Entity $entity, $options = [])
    {
        $this->checkMainImage($entity, $options);
        $this->clearUnusedFields($entity);
    }

    /**
     * After Save hook
     *
     * @param Entity $entity
     * @param array  $options
     */
    public function afterSave(Entity $entity, $options = [])
    {
        if (empty($options['isImageDataSaved'])) {
            $this->setImageData($entity);
        }
    }

    /**
     * After Remove hook
     *
     * @param Entity $entity
     * @param array  $options
     */
    public function afterRemove(Entity $entity, $options = [])
    {
        $this->mainImageDelete($entity, $options);
    }

    /**
     * Set data for image
     *
     * @param Entity $entity
     */
    protected function setImageData($entity)
    {
        // create job
        if (!is_null($this->entityName)) {
            $job = $this->getEntityManager()->getEntity('Job');
            $job->set([
                'name'        => 'Set image data',
                'status'      => CronManager::PENDING,
                'executeTime' => (new \DateTime())->format('Y-m-d H:i:s'),
                'serviceName' => 'ImageData',
                'method'      => 'cron',
                'data'        => ['entityName' => $this->entityName, 'entityId' => $entity->get('id')],
            ]);
            $this->getEntityManager()->saveEntity($job);
        }
    }

    /**
     * Check and set isMain parameter
     *
     * @param Entity $entity
     * @param array  $options
     */
    protected function checkMainImage(Entity $entity, $options = ['skipIsMain' => false])
    {
        if (!$options['skipIsMain']) {
            // Get CategoryImage with isMain parameter
            $mainImage = $this->getEntityManager()
                ->getRepository($entity->getEntityType())
                ->where([
                    'isMain' => true
                ])
                ->where($this->getCondition($entity))
                ->findOne();
            // Unset isMain for old CategoryImage
            if ($entity->get('isMain')
                && isset($mainImage)
                && $mainImage->get('id') !== $entity->get('id')
            ) {
                $mainImage->set(['isMain' => false]);
                $this->saveEntity($mainImage);
                // Set isMain if not exist yet
            } elseif (!$entity->get('isMain')
                      && (!isset($mainImage) || $entity->get('id') === $mainImage->get('id'))
            ) {
                // Get CategoryImage what will be isMain
                $newMainImage = $this->getOldestImage($entity);
                // Set newMainImage
                if (isset($newMainImage) && $newMainImage->get('id') != $entity->get('id')) {
                    $newMainImage->set(['isMain' => true]);
                    $this->saveEntity($newMainImage);
                } else {
                    $entity->set(['isMain' => true]);
                }
            }
        }
    }

    /**
     * Return Oldest image
     *
     * @param $entity
     *
     * @return mixed
     */
    protected function getOldestImage(Entity $entity)
    {
        return $this->getEntityManager()
            ->getRepository($entity->getEntityType())
            ->where($this->getCondition($entity))
            ->order('createdAt', 'ASC')
            ->findOne();
    }

    /**
     * Set Oldest Image isMain if current MainImage is deleted
     *
     * @param Entity $entity
     */
    protected function mainImageDelete(Entity $entity)
    {
        if ($entity->get('isMain')) {
            $newMainImage = $this->getOldestImage($entity);
            if (!empty($newMainImage)) {
                $newMainImage->set(['isMain' => true]);
                $this->saveEntity($newMainImage);
            }
        }
    }

    /**
     * Save entity with parameter skipIsMain
     *
     * @param Entity $entity
     */
    protected function saveEntity(Entity $entity)
    {
        $this->getEntityManager()->saveEntity($entity, ['skipIsMain' => true]);
    }

    /**
     * Clean unused fields
     *
     * @param Entity $entity
     */
    protected function clearUnusedFields(Entity $entity)
    {
        if ($entity->isNew()) {
            switch ($entity->get('type')) {
                case 'Link':
                    $image = $entity->get('image');
                    if ($image instanceof Entity) {
                        $this->getEntityManager()->removeEntity($image);
                        $entity->set(['imageId' => null]);
                    }
                    break;
                case 'File':
                    $entity->set(['imageLink' => null]);
                    break;
            }
        }
    }
}
