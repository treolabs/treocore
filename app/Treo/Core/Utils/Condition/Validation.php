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

use DateTime;
use Espo\Core\Exceptions\Error;

/**
 * Class Validation for Condition
 * @package Treo\Core\Utils\Condition
 *
 * @author Maksim Kokhanskyi <m.kokhanskyi@treolabs.com>
 */
class Validation
{
    /**
     * @param $value
     *
     * @return bool
     * @throws Error
     */
    public static function isValidNotArrayAndObject($value): bool
    {
        if (is_array($value) || is_object($value)) {
            throw new Error('The second value should not be an Array or Object type');
        }

        return true;
    }

    /**
     * @param int $needCount
     * @param array $values
     *
     * @return bool
     * @throws Error
     */
    public static function isValidCountArray(int $needCount, array $values): bool
    {
        if (count($values) < $needCount) {
            throw new Error("Wrong number of values");
        }

        return true;
    }

    /**
     * @param $time
     *
     * @return bool
     * @throws Error
     */
    public static function isValidDateTime($time): bool
    {
        if (!is_string($time) && !$time instanceof DateTime) {
            throw new Error('The first value must be an string or DateTime type');
        }

        return true;
    }

    /**
     * @param $value
     *
     * @return bool
     * @throws Error
     */
    public static function isValidFirstValueIsArray($value): bool
    {
        if (!is_array($value)) {
            throw new Error('The first value must be an Array type');
        }

        return true;
    }
}
