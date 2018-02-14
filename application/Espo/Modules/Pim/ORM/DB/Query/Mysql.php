<?php
declare(strict_types = 1);

namespace Espo\Modules\Pim\ORM\DB\Query;

use Espo\ORM\DB\Query\Mysql as EspoMysql;
use Espo\ORM\IEntity;

/**
 * Class of Mysql
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Mysql extends EspoMysql
{

    /**
     * Get where
     *
     * @param IEntity $entity
     * @param array $whereClause
     * @param string $sqlOp
     * @param array $params
     * @param int $level
     *
     * @return string
     */
    public function getWhere(IEntity $entity, $whereClause, $sqlOp = 'AND', &$params = array(), $level = 0)
    {
        $whereParts = array();

        if (!is_array($whereClause)) {
            $whereClause = array();
        }

        foreach ($whereClause as $field => $value) {
            if (is_int($field)) {
                $field = 'AND';
            }

            if ($field === 'NOT') {
                if ($level > 1) {
                    break;
                }

                $field = 'id!=s';
                $value = array(
                    'selectParams' => array(
                        'select'      => ['id'],
                        'whereClause' => $value
                    )
                );
                if (!empty($params['joins'])) {
                    $value['selectParams']['joins'] = $params['joins'];
                }
                if (!empty($params['leftJoins'])) {
                    $value['selectParams']['leftJoins'] = $params['leftJoins'];
                }
                if (!empty($params['customJoin'])) {
                    $value['selectParams']['customJoin'] = $params['customJoin'];
                }
            }

            if (!in_array($field, self::$sqlOperators)) {
                $isComplex = false;

                $operator    = '=';
                $operatorOrm = '=';

                $leftPart = null;

                if (!preg_match('/^[a-z0-9]+$/i', $field)) {
                    foreach (self::$comparisonOperators as $op => $opDb) {
                        if (strpos($field, $op) !== false) {
                            $field       = trim(str_replace($op, '', $field));
                            $operatorOrm = $op;
                            $operator    = $opDb;
                            break;
                        }
                    }
                }

                if (strpos($field, '.') !== false || strpos($field, ':') !== false) {
                    $leftPart  = $this->convertComplexExpression($entity, $field);
                    $isComplex = true;
                }


                if (empty($isComplex)) {
                    if (!isset($entity->fields[$field])) {
                        continue;
                    }

                    $fieldDefs = $entity->fields[$field];

                    $operatorModified = $operator;
                    if (is_array($value)) {
                        if ($operator == '=') {
                            $operatorModified = 'IN';
                        } elseif ($operator == '<>') {
                            $operatorModified = 'NOT IN';
                        }
                    } elseif (is_null($value)) {
                        if ($operator == '=') {
                            $operatorModified = 'IS NULL';
                        } elseif ($operator == '<>') {
                            $operatorModified = 'IS NOT NULL';
                        }
                    }

                    if (!empty($fieldDefs['where']) && !empty($fieldDefs['where'][$operatorModified])) {
                        $whereSqlPart = '';
                        if (is_string($fieldDefs['where'][$operatorModified])) {
                            $whereSqlPart = $fieldDefs['where'][$operatorModified];
                        } else {
                            if (!empty($fieldDefs['where'][$operatorModified]['sql'])) {
                                $whereSqlPart = $fieldDefs['where'][$operatorModified]['sql'];
                            }
                        }
                        if (!empty($fieldDefs['where'][$operatorModified]['leftJoins'])) {
                            foreach ($fieldDefs['where'][$operatorModified]['leftJoins'] as $j) {
                                $jAlias = $this->obtainJoinAlias($j);
                                foreach ($params['leftJoins'] as $jE) {
                                    $jEAlias = $this->obtainJoinAlias($jE);
                                    if ($jEAlias === $jAlias) {
                                        continue 2;
                                    }
                                }
                                $params['leftJoins'][] = $j;
                            }
                        }
                        if (!empty($fieldDefs['where'][$operatorModified]['joins'])) {
                            foreach ($fieldDefs['where'][$operatorModified]['joins'] as $j) {
                                $jAlias = $this->obtainJoinAlias($j);
                                foreach ($params['joins'] as $jE) {
                                    $jEAlias = $this->obtainJoinAlias($jE);
                                    if ($jEAlias === $jAlias) {
                                        continue 2;
                                    }
                                }
                                $params['joins'][] = $j;
                            }
                        }
                        if (!empty($fieldDefs['where'][$operatorModified]['customJoin'])) {
                            $params['customJoin'] .= ' '.$fieldDefs['where'][$operatorModified]['customJoin'];
                        }
                        if (!empty($fieldDefs['where'][$operatorModified]['distinct'])) {
                            $params['distinct'] = true;
                        }
                        $whereParts[] = str_replace('{value}', $this->stringifyValue($value), $whereSqlPart);
                    } else {
                        if ($fieldDefs['type'] == IEntity::FOREIGN) {
                            $leftPart = '';
                            if (isset($fieldDefs['relation'])) {
                                $relationName = $fieldDefs['relation'];
                                if (isset($entity->relations[$relationName])) {
                                    $alias = $this->getAlias($entity, $relationName);
                                    if ($alias) {
                                        if (!is_array($fieldDefs['foreign'])) {
                                            $leftPart = $alias.'.'.$this->toDb($fieldDefs['foreign']);
                                        } else {
                                            $leftPart = $this->getFieldPath($entity, $field);
                                        }
                                    }
                                }
                            }
                        } else {
                            $leftPart = $this->toDb($entity->getEntityType()).'.'.$this->toDb($this->sanitize($field));
                        }
                    }
                }
                if (!empty($leftPart)) {
                    if ($operatorOrm === '=s' || $operatorOrm === '!=s') {
                        if (!is_array($value)) {
                            continue;
                        }
                        if (!empty($value['entityType'])) {
                            $subQueryEntityType = $value['entityType'];
                        } else {
                            $subQueryEntityType = $entity->getEntityType();
                        }
                        $subQuerySelectParams = array();
                        if (!empty($value['selectParams'])) {
                            $subQuerySelectParams = $value['selectParams'];
                        }
                        $withDeleted = false;
                        if (!empty($value['withDeleted'])) {
                            $withDeleted = true;
                        }
                        $whereParts[] = $leftPart." ".$operator." (".$this->createSelectQuery(
                            $subQueryEntityType,
                            $subQuerySelectParams,
                            $withDeleted
                        ).")";
                    } elseif (!is_array($value)) {
                        if (!is_null($value)) {
                            if (is_numeric($value) && in_array($operator, ['>', '>=', '<', '<='])) {
                                $whereParts[] = $leftPart." ".$operator." ".$value;
                            } elseif (is_string($value)) {
                                $whereParts[] = $leftPart." ".$operator." ".$this->pdo->quote($value);
                            }
                        } else {
                            if ($operator == '=') {
                                $whereParts[] = $leftPart." IS NULL";
                            } elseif ($operator == '<>') {
                                $whereParts[] = $leftPart." IS NOT NULL";
                            }
                        }
                    } else {
                        $valArr = $value;
                        foreach ($valArr as $k => $v) {
                            $valArr[$k] = $this->pdo->quote($valArr[$k]);
                        }
                        $oppose     = '';
                        $emptyValue = '0';
                        if ($operator == '<>') {
                            $oppose     = 'NOT ';
                            $emptyValue = '1';
                        }
                        if (!empty($valArr)) {
                            $whereParts[] = $leftPart." {$oppose}IN "."(".implode(',', $valArr).")";
                        } else {
                            $whereParts[] = "".$emptyValue;
                        }
                    }
                }
            } else {
                $internalPart = $this->getWhere($entity, $value, $field, $params, $level + 1);
                if ($internalPart) {
                    $whereParts[] = "(".$internalPart.")";
                }
            }
        }

        return implode(" ".$sqlOp." ", $whereParts);
    }
}
