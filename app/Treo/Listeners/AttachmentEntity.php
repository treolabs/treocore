<?php
/** Dam
 * Free Extension
 * Copyright (c) TreoLabs GmbH
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Treo\Listeners;

use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\InternalServerError;
use Espo\ORM\Entity;
use Treo\Core\EventManager\Event;

/**
 * Class AssetEntity
 *
 * @package Dam\Listeners
 */
class AttachmentEntity extends AbstractListener
{
    /**
     * @param Event $event
     * @throws InternalServerError
     */
    public function beforeSave(Event $event)
    {
        $entity = $event->getArgument('entity');
        if ($this->isDuplicate($entity)) {
            $this->copyFile($entity);
        }

        if (!$entity->isNew() && $this->isChangeRelation($entity) && !in_array($entity->get("relatedType"), $this->skipTypes())) {
            $this->moveFromTmp($entity);
        }
    }

    /**
     * @return array
     */
    protected function skipTypes()
    {
        return $this->getMetadata()->get(['attachment', 'skipEntities']) ?? [];
    }

    /**
     * @param Entity $entity
     * @return bool
     */
    protected function isChangeRelation(Entity $entity): bool
    {
        return $entity->isAttributeChanged("relatedId") || $entity->isAttributeChanged("relatedType");
    }

    /**
     * @param Entity $entity
     * @return bool
     * @throws Error
     */
    protected function moveFromTmp(Entity $entity)
    {
        if ($entity->isNew()) {
            return true;
        }

        if (!$this->getService($entity->getEntityType())->moveFromTmp($entity)) {
            throw new Error();
        }
    }

    /**
     * @param Entity $entity
     * @return bool
     */
    protected function isDuplicate(Entity $entity): bool
    {
        return (!$entity->isNew() && $entity->get('sourceId'));
    }

    /**
     * @param Entity $entity
     * @throws InternalServerError
     */
    protected function copyFile(Entity $entity): void
    {
        $repository = $this->getEntityManager()->getRepository($entity->getEntityType());
        $path = $repository->copy($entity);

        if (!$path) {
            throw new InternalServerError($this->getLanguage()->translate("Can't copy file", 'exceptions', 'Global'));
        }

        $entity->set([
            'sourceId' => null,
            'storageFilePath' => $path,
        ]);
    }
}
