<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2018 Zinit Solutions GmbH
 * Website: http://www.treopim.com
 *
 * TreoPIM as well as EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TreoPIM as well as EspoCRM is distributed in the hope that it will be useful,
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
 * and "TreoPIM" word.
 */

declare(strict_types = 1);

namespace Espo\Modules\TreoCore\Services;

use Espo\Core\Services\Base;

/**
 * Class of AbstractProgressManager
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
abstract class AbstractProgressManager extends Base
{
    /**
     * @var array
     */
    public static $progressStatus = [
        'new'         => '1_new',
        'in_progress' => '2_in_progress',
        'error'       => '3_error',
        'success'     => '4_success'
    ];

    /**
     * @var float
     */
    protected $progress = 0;

    /**
     * @var int
     */
    protected $offset = 0;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var string
     */
    protected $status = "new";

    /**
     * Get offset
     *
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * Set offset
     *
     * @param int $val
     *
     * @return AbstractProgressManager
     */
    public function setOffset(int $val): AbstractProgressManager
    {
        $this->offset = $val;

        return $this;
    }

    /**
     * Get progress
     *
     * @return float
     */
    public function getProgress(): float
    {
        return $this->progress;
    }

    /**
     * Set progress
     *
     * @param float $val
     *
     * @return AbstractProgressManager
     */
    public function setProgress(float $val): AbstractProgressManager
    {
        $this->progress = $val;

        return $this;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Set data
     *
     * @param array $data
     *
     * @return AbstractProgressManager
     */
    public function setData(array $data): AbstractProgressManager
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @return AbstractProgressManager
     */
    public function setStatus(string $status): AbstractProgressManager
    {
        $this->status = $status;

        return $this;
    }
}
