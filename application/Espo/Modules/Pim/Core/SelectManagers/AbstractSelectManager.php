<?php

namespace Espo\Modules\Pim\Core\SelectManagers;

use Espo\Core\SelectManagers\Base;
use \Espo\Core\Templates\Services\Base as BaseService;

/**
 * Class of AbstractSelectManager
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
abstract class AbstractSelectManager extends Base
{

    /**
     * @var array
     */
    protected $selectData = [];

    /**
     * Get select params
     *
     * @param array $params
     * @param bool  $withAcl
     * @param bool  $checkWherePermission
     *
     * @return array
     */
    public function getSelectParams(array $params, $withAcl = false, $checkWherePermission = false)
    {
        // set select data
        $this->selectData = $params;

        return parent::getSelectParams($params, $withAcl, $checkWherePermission);
    }

    /**
     * OnlyActive filter
     *
     * @param array $result
     */
    protected function boolFilterOnlyActive(&$result)
    {
        $result['whereClause'][] = array(
            'isActive' => true
        );
    }

    /**
     * NotEntity filter
     *
     * @param array $result
     */
    protected function boolFilterNotEntity(&$result)
    {
        foreach ($this->getSelectData('where') as $key => $row) {
            if ($row['type'] == 'bool' && !empty($row['data']['notEntity'])) {
                // prepare value
                $value = (array)$row['data']['notEntity'];
                // prepare where clause
                foreach ($value as $id) {
                    $result['whereClause'][] = [
                        'id!=' => (string)$id
                    ];
                }
            }
        }
    }

    /**
     * Get select data
     *
     * @param string $key
     *
     * @return array
     */
    protected function getSelectData($key = '')
    {
        $result = [];

        if (empty($key)) {
            $result = $this->selectData;
        } elseif (isset($this->selectData[$key])) {
            $result = $this->selectData[$key];
        }

        return $result;
    }

    /**
     * Get Condition for boolFilter
     *
     * @param string $filterName
     *
     * @return mixed
     */
    protected function getSelectCondition(string  $filterName)
    {
        foreach ($this->getSelectData('where') as $key => $row) {
            if ($row['type'] == 'bool' && !empty($row['data'][$filterName])) {
                $condition = $row['data'][$filterName];
            }
        }
        return $condition ?? false;
    }

    /**
     * Create Service
     *
     * @param string $name
     *
     * @return BaseService
     */
    protected function createService(string $name): BaseService
    {
        return $this
            ->getEntityManager()
            ->getContainer()
            ->get('serviceFactory')
            ->create($name);
    }
}
