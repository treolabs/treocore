<?php


namespace Espo\Modules\Pim\Core\Services;

use Espo\Core\Templates\Services\Base;
use \Espo\Core\Exceptions\Forbidden;

/**
 * AbstractService
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
class AbstractService extends Base
{

    /**
     * Find linked Entities with the use of custom relation
     * prepare selectParams for query and entityCollection for output
     *
     * @param string $link       name of the related entities
     * @param array  $params
     * @param string $customJoin custom relation (left, right or inner join)
     *
     * @return array
     * @throws Forbidden
     */
    protected function findCustomLinkedEntities(string $link, array $params, string $customJoin): array
    {
        // check acl for related entity
        if (!$this->getAcl()->check($link, 'read')) {
            throw new Forbidden();
        }
        // prepare select params
        $selectParams = $this->getSelectManager($link)->getSelectParams($params, true);
        $selectParams['customJoin'] = $customJoin;
        $this->getEntityManager()->getRepository($link)->handleSelectParams($selectParams);

        // find linked entities
        $collection = $this->getRepository()->findCustomLinkedEntities($link, $selectParams);
        // prepare entity for output
        $recordService = $this->getRecordService($link);
        foreach ($collection as $entity) {
            $recordService->loadAdditionalFieldsForList($entity);
            $recordService->prepareEntityForOutput($entity);
        }

        return [
            'collection' => $collection,
            'total' => $this->getRepository()->getCustomTotal($link, $selectParams)
        ];
    }
}
