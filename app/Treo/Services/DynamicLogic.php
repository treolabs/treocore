<?php

declare(strict_types=1);

namespace Treo\Services;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\ORM\Entity;
use Treo\Core\Utils\Condition\Condition;

class DynamicLogic extends AbstractService
{
    /**
     * @var bool|array
     */
    private $relationFields = false;

    /**
     * @param string $field
     * @param Entity $entity
     * @param $typeResult
     * @return bool
     * @throws BadRequest
     */
    public function isRequiredField(string $field, Entity $entity, $typeResult): bool
    {
        if ($this->relationFields === false) {
            $this->setRelationFields($entity);
        }
        if (isset($this->relationFields[$field])) {
            $field = $this->relationFields[$field];
        }

        $result = false;


        $item = $this->getContainer()
            ->get('metadata')
            ->get("clientDefs.{$entity->getEntityName()}.dynamicLogic.fields.$field.$typeResult.conditionGroup", []);

        if (!empty($item)) {
            $result = Condition::prepareAndCheck($entity, $item);
        }

        return $result;
    }

    /**
     * @param Entity $entity
     */
    private function setRelationFields(Entity $entity): void
    {
        foreach ($entity->getRelations() as $key => $relation) {
            if (isset($relation['key'])) {
                $this->relationFields[$relation['key']] = $key;
            }
        }
    }
}
