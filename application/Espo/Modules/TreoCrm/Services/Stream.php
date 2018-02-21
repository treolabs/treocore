<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Services;

use Espo\Services\Stream as EspoStream;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;

/**
 * Stream service
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Stream extends EspoStream
{

    /**
     * Get user stream
     *
     * @param string $userId
     * @param array $params
     *
     * @return array
     * @throws NotFound
     * @throws Forbidden
     */
    public function findUserStream($userId, $params = array())
    {
        $offset  = intval($params['offset']);
        $maxSize = intval($params['maxSize']);

        if ($userId === $this->getUser()->id) {
            $user = $this->getUser();
        } else {
            $user = $this->getEntityManager()->getEntity('User', $userId);
            if (!$user) {
                throw new NotFound();
            }
            if (!$this->getAcl()->checkUser('userPermission', $user)) {
                throw new Forbidden();
            }
        }

        $pdo = $this->getEntityManager()->getPDO();

        $select = [
            'id', 'number', 'type', 'post', 'data', 'parentType', 'parentId', 'relatedType', 'relatedId',
            'targetType', 'createdAt', 'createdById', 'createdByName', 'isGlobal', 'isInternal', 'createdByGender'
        ];

        $selectParamsList = [];

        $selectParamsSubscription = array(
            'select'      => $select,
            'leftJoins'   => ['createdBy'],
            'customJoin'  => "
                JOIN subscription AS `subscription` ON
                    (
                        (
                            note.parent_type = subscription.entity_type AND
                            note.parent_id = subscription.entity_id
                        )
                    ) AND
                    subscription.user_id = ".$pdo->quote($user->id)."
            ",
            'whereClause' => array(),
            'orderBy'     => 'number',
            'order'       => 'DESC'
        );

        if ($user->get('isPortalUser')) {
            $selectParamsSubscription['whereClause'][] = array(
                'isInternal' => false
            );
        }

        $selectParamsList[] = $selectParamsSubscription;

        $selectParamsSubscriptionSuper = array(
            'select'      => $select,
            'leftJoins'   => ['createdBy'],
            'customJoin'  => "
                JOIN subscription AS `subscription` ON
                    (
                        (
                            note.super_parent_type = subscription.entity_type AND
                            note.super_parent_id = subscription.entity_id
                        )
                    ) AND
                    subscription.user_id = ".$pdo->quote($user->id)."
            ",
            'customWhere' => ' (
                    note.parent_id <> note.super_parent_id
                    OR
                    note.parent_type <> note.super_parent_type
                )
            ',
            'whereClause' => array(),
            'orderBy'     => 'number',
            'order'       => 'DESC'
        );

        if ($user->get('isPortalUser')) {
            $selectParamsSubscriptionSuper['whereClause'][] = array(
                'isInternal' => false
            );
        }

        $selectParamsList[] = $selectParamsSubscriptionSuper;

        $selectParamsList[] = array(
            'select'      => $select,
            'leftJoins'   => ['createdBy'],
            'whereClause' => array(
                'createdById' => $user->id,
                'parentId'    => null,
                'type'        => 'Post',
                'isGlobal'    => false
            ),
            'orderBy'     => 'number',
            'order'       => 'DESC'
        );

        $selectParamsList[] = array(
            'select'      => $select,
            'leftJoins'   => ['users', 'createdBy'],
            'whereClause' => array(
                'createdById!='      => $user->id,
                'usersMiddle.userId' => $user->id,
                'parentId'           => null,
                'type'               => 'Post',
                'isGlobal'           => false
            ),
            'orderBy'     => 'number',
            'order'       => 'DESC'
        );

        if (!$user->get('isPortalUser') || $user->get('isAdmin')) {
            $selectParamsList[] = array(
                'select'      => $select,
                'leftJoins'   => ['createdBy'],
                'whereClause' => array(
                    'parentId' => null,
                    'type'     => 'Post',
                    'isGlobal' => true
                ),
                'orderBy'     => 'number',
                'order'       => 'DESC'
            );
        }

        if ($user->get('isPortalUser')) {
            $portalIdList       = $user->getLinkMultipleIdList('portals');
            $portalIdQuotedList = [];
            foreach ($portalIdList as $portalId) {
                $portalIdQuotedList[] = $pdo->quote($portalId);
            }
            if (!empty($portalIdQuotedList)) {
                $selectParamsList[] = array(
                    'select'      => $select,
                    'leftJoins'   => ['portals', 'createdBy'],
                    'whereClause' => array(
                        'parentId'               => null,
                        'portalsMiddle.portalId' => $portalIdList,
                        'type'                   => 'Post',
                        'isGlobal'               => false
                    ),
                    'orderBy'     => 'number',
                    'order'       => 'DESC'
                );
            }
        }

        $teamIdList       = $user->getTeamIdList();
        $teamIdQuotedList = [];
        foreach ($teamIdList as $teamId) {
            $teamIdQuotedList[] = $pdo->quote($teamId);
        }
        if (!empty($teamIdList)) {
            $selectParamsList[] = array(
                'select'      => $select,
                'leftJoins'   => ['teams', 'createdBy'],
                'whereClause' => array(
                    'parentId'           => null,
                    'teamsMiddle.teamId' => $teamIdList,
                    'type'               => 'Post',
                    'isGlobal'           => false
                ),
                'orderBy'     => 'number',
                'order'       => 'DESC'
            );
        }

        $whereClause = array();
        if (!empty($params['after'])) {
            $whereClause[]['createdAt>'] = $params['after'];
        }

        if (!empty($params['filter'])) {
            switch ($params['filter']) {
                case 'posts':
                    $whereClause[]['type'] = 'Post';
                    break;
                case 'updates':
                    $whereClause[]['type'] = ['Update', 'Status'];
                    break;
            }
        }

        $ignoreScopeList = $this->getIgnoreScopeList();

        if (!empty($ignoreScopeList)) {
            $whereClause[] = array(
                'OR' => array(
                    'relatedType'   => null,
                    'relatedType!=' => $ignoreScopeList
                )
            );
            $whereClause[] = array(
                'OR' => array(
                    'parentType'   => null,
                    'parentType!=' => $ignoreScopeList
                )
            );
            if (in_array('Email', $ignoreScopeList)) {
                $whereClause[] = array(
                    'type!=' => ['EmailReceived', 'EmailSent']
                );
            }
        }

        $sqlPartList = [];
        foreach ($selectParamsList as $i => $selectParams) {
            if (empty($selectParams['whereClause'])) {
                $selectParams['whereClause'] = array();
            }
            $selectParams['whereClause'][] = $whereClause;
            $sqlPartList[]                 = "(\n".$this->getEntityManager()->getQuery()->createSelectQuery(
                'Note',
                $selectParams
            )."\n)";
        }

        $sql = implode("\n UNION \n", $sqlPartList)."
            ORDER BY number DESC
        ";

        $sql = $this->getEntityManager()->getQuery()->limit($sql, $offset, $maxSize + 1);

        $collection = $this->getEntityManager()->getRepository('Note')->findByQuery($sql);

        foreach ($collection as $e) {
            if ($e->get('type') == 'Post' || $e->get('type') == 'EmailReceived') {
                $e->loadAttachments();
            }
        }

        foreach ($collection as $e) {
            if ($e->get('parentId') && $e->get('parentType')) {
                $entity = $this->getEntityManager()->getEntity($e->get('parentType'), $e->get('parentId'));
                if ($entity) {
                    $e->set('parentName', $entity->get('name'));
                }
            }
            if ($e->get('relatedId') && $e->get('relatedType')) {
                $entity = $this->getEntityManager()->getEntity($e->get('relatedType'), $e->get('relatedId'));
                if ($entity) {
                    $e->set('relatedName', $entity->get('name'));
                }
            }
            if ($e->get('type') == 'Post' && $e->get('parentId') === null && !$e->get('isGlobal')) {
                $targetType = $e->get('targetType');
                if (!$targetType || $targetType === 'users' || $targetType === 'self') {
                    $e->loadLinkMultipleField('users');
                }
                if ($targetType !== 'users' && $targetType !== 'self') {
                    if (!$targetType || $targetType === 'teams') {
                        $e->loadLinkMultipleField('teams');
                    } elseif ($targetType === 'portals') {
                        $e->loadLinkMultipleField('portals');
                    }
                }
            }
        }

        if (count($collection) > $maxSize) {
            $total = -1;
            unset($collection[count($collection) - 1]);
        } else {
            $total = -2;
        }

        return array(
            'total'      => $total,
            'collection' => $collection,
        );
    }
}
