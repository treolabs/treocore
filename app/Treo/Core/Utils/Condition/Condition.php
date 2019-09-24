<?php
/**
 * This file is part of EspoCRM and/or TreoCore.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoCore is EspoCRM-based Open Source application.
 * Copyright (C) 2017-2019 TreoLabs GmbH
 * Website: https://treolabs.com
 *
 * TreoCore as well as EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TreoCore as well as EspoCRM is distributed in the hope that it will be useful,
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
 * and "TreoCore" word.
 */

declare(strict_types=1);

namespace Treo\Core\Utils\Condition;

use DateInterval as DateInterval;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\ORM\Entity;
use Espo\ORM\EntityCollection as EntityCollection;
use \DateTime;
use Exception as Exception;

/**
 * Class Condition
 * @package Treo\Core\Utils
 *
 * @author Maksim Kokhanskyi <m.kokhanskyi@treolabs.com>
 */
class Condition
{
    /**
     * @param Entity $entity
     * @param array $items
     * @return bool
     * @throws BadRequest
     */
    public static function prepareAndCheck(Entity $entity, array $items): bool
    {
        $data = self::prepare($entity, $items);

        return self::isCheck($data);
    }

    /**
     * @param ConditionGroup $condition
     * @return bool
     * @throws BadRequest
     */
    public static function isCheck(ConditionGroup $condition): bool
    {
        $method = 'check' . ucfirst($condition->getType());

        if (method_exists(self::class, $method)) {
            return self::{$method}($condition->getValues());
        } else {
            throw new BadRequest("Type {$condition->getType()} does not exists");
        }
    }

    /**
     * @param Entity $entity
     * @param array $items
     * @return ConditionGroup
     * @throws BadRequest
     */
    public static function prepare(Entity $entity, array $items): ConditionGroup
    {
        if (empty($items)) {
            throw new BadRequest('Empty items in condition');
        }
        $result = null;
        if (isset($items['type'])) {
            if (!in_array($items['type'], ['and', 'or', 'not'])) {
                $result = self::prepareConditionGroup($entity, $items);
            } else {
                if (empty($items['value'])) {
                    throw new BadRequest('Empty value or in condition');
                }
                $valuesConditionGroup = [];
                foreach ($items['value'] as $value) {
                    $valuesConditionGroup[] = self::prepare($entity, $value);
                }
                $result = new ConditionGroup($items['type'], $valuesConditionGroup);
            }
        } else {
            $type = 'and';
            $valuesConditionGroup = [];
            foreach ($items as $value) {
                $valuesConditionGroup[] = self::prepare($entity, $value);
            }
            $result = new ConditionGroup($type, $valuesConditionGroup);
        }
        return $result;
    }

    /**
     * @param Entity $entity
     * @param array $item
     * @return ConditionGroup
     * @throws BadRequest
     */
    private static function prepareConditionGroup(Entity $entity, array $item): ConditionGroup
    {
        if (!isset($item['attribute'])) {
            throw new BadRequest('Empty attribute or in condition');
        }

        $attribute = $item['attribute'];

        if (!$entity->hasAttribute($attribute) && !$entity->hasRelation($attribute)) {
            throw new BadRequest("Attribute '{$attribute}' does not exists in '{$entity->getEntityType()}'");
        }

        $currentValue = $entity->get($attribute);

        if (is_null($currentValue)
            && !empty($item['data']['field'])
            && $entity->get($item['data']['field'])) {
            $currentValue = $entity->get($item['data']['field']);
        }

        if ($currentValue instanceof EntityCollection) {
            $currentValue = array_column($currentValue->toArray(), 'id');
        }

        $values[] = $currentValue;
        if (isset($item['value'])) {
            $values[] = $item['value'];
        }

        return new ConditionGroup($item['type'], $values);
    }

    /**
     * @param array $values Array containing the necessary value
     *      $values = [
     *          0   => (ConditionGroup)
     *          1   => (ConditionGroup)
     *          .....
     *          n   => (ConditionGroup)
     *      ]
     * @return bool
     * @throws BadRequest
     */
    protected static function checkAnd(array $values): bool
    {
        $result = true;

        foreach ($values as $value) {
            $result = self::isCheck($value);
            if (!$result) {
                break;
            }
        }

        return $result;
    }

    /**
     * @param array $values Array containing the necessary value
     *      $values = [
     *          0   => (ConditionGroup)
     *          1   => (ConditionGroup)
     *          .....
     *          n   => (ConditionGroup)
     *      ]
     * @return bool
     * @throws BadRequest
     */
    protected static function checkOr(array $values): bool
    {
        $result = false;

        foreach ($values as $value) {
            $result = self::isCheck($value);
            if ($result) {
                break;
            }
        }
        return $result;
    }

    /**
     * @param array $values Array containing the necessary value
     *      $values = [
     *          0   => (string|array|float|null|int)
     *          0   => (string|array|float|null|int)
     *      ]
     * @return bool
     * @throws BadRequest
     */
    protected static function checkEquals(array $values): bool
    {
        Validation::isValidCountArray(2, $values);

        $left = array_shift($values);
        if ($left instanceof Entity) {
            $left = $left->get('id');
        }
        $right = array_shift($values);

        return $left === $right;
    }

    /**
     * @param array $values Array containing the necessary value
     *      $values = [
     *          0   => (string|array|float|null|int)
     *          0   => (string|array|float|null|int)
     *      ]
     * @return bool
     * @throws BadRequest
     */
    protected static function checkNotEquals(array $values): bool
    {
        return !self::checkEquals($values);
    }

    /**
     * @param array $values Array containing the necessary value
     *      $values = [
     *          0   => (string|array|null)
     *      ]
     * @return bool
     * @throws BadRequest
     */
    protected static function checkIsEmpty(array $values)
    {
        Validation::isValidCountArray(1, $values);

        $value = array_shift($values);

        return is_null($value) || $value === '' || $value === [];
    }

    /**
     * @param array $values Array containing the necessary value
     *      $values = [
     *          0   => (string|array|null)
     *      ]
     * @return bool
     * @throws BadRequest
     */
    protected static function checkIsNotEmpty(array $values)
    {
        return !self::checkIsEmpty($values);
    }

    /**
     * @param array $values Array containing the necessary value
     *      $values = [
     *          0   => (bool)
     *      ]
     * @return bool
     * @throws BadRequest
     */
    protected static function checkIsTrue(array $values): bool
    {
        Validation::isValidCountArray(1, $values);

        return (bool)array_shift($values);
    }

    /**
     * @param array $values Array containing the necessary value
     *      $values = [
     *          0   => (bool)
     *      ]
     * @return bool
     * @throws BadRequest
     */
    protected static function checkIsFalse(array $values): bool
    {
        return !self::checkIsTrue($values);
    }

    /**
     * @param array $values Array containing the necessary value
     *      $values = [
     *          0   => (EntityCollection|array)
     *          1   => (string|int|float|bool|null)
     *      ]
     * @return bool
     * @throws BadRequest
     */
    protected static function checkContains(array $values): bool
    {
        Validation::isValidCountArray(2, $values);

        $currentValue = array_shift($values);
        Validation::isValidFirstValueIsArray($currentValue);

        $needValue = array_shift($values);
        Validation::isValidNotArrayAndObject($needValue);

        return in_array($needValue, $currentValue);
    }

    /**
     * @param array $values Array containing the necessary value
     *      $values = [
     *          0   => (EntityCollection|array)
     *          1   => (string|int|float|bool|null)
     *      ]
     * @return bool
     * @throws BadRequest
     */
    protected function checkNotContains(array $values): bool
    {
        return !self::checkContains($values);
    }

    /**
     * @param array $values Array containing the necessary value
     *      $values = [
     *          0   => (array)
     *          1   => (string|int|float|bool|null)
     *      ]
     * @return bool
     * @throws BadRequest
     */
    protected function checkHas(array $values): bool
    {
        return self::checkContains($values);
    }

    /**
     * @param array $values Array containing the necessary value
     *      $values = [
     *          0   => (array)
     *          1   => (string|int|float|bool|null)
     *      ]
     * @return bool
     * @throws BadRequest
     */
    protected function checkNotHas(array $values): bool
    {
        return !self::checkHas($values);
    }

    /**
     * @param array $values Array containing the necessary value
     *      $values = [
     *          0   => (float|int) First numeric
     *          1   => (float|int) Second numeric
     *      ]
     * @return bool
     * @throws BadRequest
     */
    protected function checkGreaterThan(array $values): bool
    {
        Validation::isValidCountArray(2, $values);

        $currentValue = array_shift($values);
        $needValue = array_shift($values);

        return (float)$currentValue > (float)$needValue;
    }

    /**
     * @param array $values Array containing the necessary value
     *      $values = [
     *          0   => (float|int) First numeric
     *          1   => (float|int) Second numeric
     *      ]
     * @return bool
     * @throws BadRequest
     */
    protected function checkLessThan(array $values): bool
    {
        return !self::checkGreaterThan($values);
    }

    /**
     * @param array $values Array containing the necessary value
     *      $values = [
     *          0   => (float|int) First numeric
     *          1   => (float|int) Second numeric
     *      ]
     * @return bool
     * @throws BadRequest
     */
    protected static function checkGreaterThanOrEquals(array $values): bool
    {
        Validation::isValidCountArray(2, $values);

        $currentValue = array_shift($values);
        $needValue = array_shift($values);

        return (float)$currentValue >= (float)$needValue;
    }


    /**
     * @param array $values Array containing the necessary value
     *      $values = [
     *          0   => (float|int) First numeric
     *          1   => (float|int) Second numeric
     *      ]
     * @return bool
     * @throws BadRequest
     */
    protected static function checkLessThanOrEquals(array $values): bool
    {
        Validation::isValidCountArray(2, $values);

        $currentValue = array_shift($values);
        $needValue = array_shift($values);

        return (float)$currentValue <= (float)$needValue;
    }

    /**
     * @param array $values Array containing the necessary value
     *      $values = [
     *          0   => (int|string|float|bool|null)
     *          1   => (array)
     *      ]
     * @return bool
     * @throws BadRequest
     */
    protected static function checkIn(array $values): bool
    {
        Validation::isValidCountArray(2, $values);

        $currentValue = array_shift($values);
        if (is_array($currentValue) || is_object($currentValue)) {
            throw new BadRequest('The first value should not be an Array or Object type');
        }
        $needValue = array_shift($values);

        if (!is_array($needValue)) {
            throw new BadRequest('The second value must be an Array type');
        }
        return in_array($currentValue, $needValue);
    }

    /**
     * @param array $values Array containing the necessary value
     *      $values = [
     *          0   => (int|string|float|bool|null)
     *          1   => (array)
     *      ]
     * @return bool
     * @throws BadRequest
     */
    protected static function checkNotIn(array $values): bool
    {
        return !self::checkIn($values);
    }

    /**
     * @param array $values Array containing the necessary value
     *      $values = [
     *          0   => (string|DataTime) Time.
     *      ]
     * @return bool
     * @throws BadRequest
     * @throws Exception
     */
    protected static function checkIsToday(array $values): bool
    {
        Validation::isValidCountArray(1, $values);
        $currentValue = array_shift($values);
        $result = false;
        if (!is_null($currentValue)) {
            Validation::isValidDateTime($currentValue);

            $time = (int)self::howTime($currentValue)->format("%R%a");
            $result = $time === 0;
        }
        return $result;
    }

    /**
     * @param array $values Array containing the necessary value
     *      $values = [
     *          0   => (string|DataTime) Time.
     *      ]
     * @return bool
     * @throws BadRequest
     * @throws Exception
     */
    protected static function checkinFuture(array $values): bool
    {
        Validation::isValidCountArray(1, $values);
        $currentValue = array_shift($values);
        $result = false;
        if (!is_null($currentValue)) {
            Validation::isValidDateTime($currentValue);

            $time = (int)self::howTime($currentValue)->format("%R%h%i%s");
            $result = $time > 0;
        }
        return $result;
    }

    /**
     * @param array $values Array containing the necessary value
     *      $values = [
     *          0   => (string|DataTime) Time.
     *      ]
     * @return bool
     * @throws BadRequest
     * @throws Exception
     */
    protected static function checkinPast(array $values): bool
    {
        Validation::isValidCountArray(1, $values);
        $currentValue = array_shift($values);
        $result = false;
        if (!is_null($currentValue)) {
            Validation::isValidDateTime($currentValue);

            $time = (int)self::howTime($currentValue)->format("%R%h%i%s");
            $result = $time < 0;
        }
        return $result;
    }

    /**
     * @param string| DateTime $time
     * @return DateInterval
     * @throws Exception
     */
    private static function howTime($time): DateInterval
    {
        $compareTime = $time instanceof DateTime
            ? $time
            : new DateTime($time);

        $today = new DateTime();

        return $today
            ->diff($compareTime);
    }
}
