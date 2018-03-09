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

namespace Espo\Modules\TreoCrm\Core;

use Espo\Core\CronManager as CoreCronManager;
use Espo\Core\Utils\Cron\Job;
use Espo\Core\Utils\Cron\ScheduledJob;
use Espo\Modules\TreoCrm\Traits\ContainerTrait;

/**
 * Class of CronManager
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class CronManager extends CoreCronManager
{

    use ContainerTrait;
    /**
     * @var object
     */
    protected $cronJobUtil = null;

    /**
     * @var object
     */
    protected $cronScheduledJobUtil = null;

    /**
     * Construct
     */
    public function __construct()
    {
        // blocked parent construct
    }

    /**
     * Check scheduled jobs and create related jobs
     */
    protected function createJobsFromScheduledJobs()
    {
        // get parent data
        parent::createJobsFromScheduledJobs();

        // get created jobs
        $this->getServiceFactory()->create('CronJobCreator')->createJobs();
    }

    /**
     * Get config
     *
     * @return object
     */
    protected function getConfig()
    {
        return $this->getContainer()->get('config');
    }

    /**
     * Get fileManager
     *
     * @return object
     */
    protected function getFileManager()
    {
        return $this->getContainer()->get('fileManager');
    }

    /**
     * Get entityManager
     *
     * @return object
     */
    protected function getEntityManager()
    {
        return $this->getContainer()->get('entityManager');
    }

    /**
     * Get serviceFactory
     *
     * @return object
     */
    protected function getServiceFactory()
    {
        return $this->getContainer()->get('serviceFactory');
    }

    /**
     * Get scheduledJob
     *
     * @return object
     */
    protected function getScheduledJobUtil()
    {
        return $this->getContainer()->get('scheduledJob');
    }

    /**
     * Get CronJobUtil
     *
     * @return object
     */
    protected function getCronJobUtil()
    {
        if (is_null($this->cronJobUtil)) {
            $this->cronJobUtil = new Job($this->getConfig(), $this->getEntityManager());
        }

        return $this->cronJobUtil;
    }

    /**
     * Get CronScheduledJobUtil
     *
     * @return object
     */
    protected function getCronScheduledJobUtil()
    {
        if (is_null($this->cronScheduledJobUtil)) {
            $this->cronScheduledJobUtil = new ScheduledJob($this->getConfig(), $this->getEntityManager());
        }

        return $this->cronScheduledJobUtil;
    }
}
