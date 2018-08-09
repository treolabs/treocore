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

declare(strict_types=1);

namespace Espo\Modules\TreoCore\Core;

use Espo\Core\CronManager as CoreCronManager;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Utils\Json;
use Espo\Modules\TreoCore\Core\Utils\Cron\Job as JobUtil;

/**
 * Class of CronManager
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class CronManager extends CoreCronManager
{
    /**
     * @var null|JobUtil
     */
    protected $treoCronJobUtil = null;

    /**
     * Run Service
     *
     * @param  array $job
     *
     * @return void
     */
    protected function runService($job)
    {
        $serviceName = $job->get('serviceName');

        if (!$serviceName) {
            throw new Error('Job with empty serviceName.');
        }

        if (!$this->getServiceFactory()->checkExists($serviceName)) {
            throw new NotFound();
        }

        $service = $this->getServiceFactory()->create($serviceName);

        $methodNameDeprecated = $job->get('method');
        $methodName = $job->get('methodName');

        $isDeprecated = false;
        if (!$methodName) {
            $isDeprecated = true;
            $methodName = $methodNameDeprecated;
        }

        if (!$methodName) {
            throw new Error('Job with empty methodName.');
        }

        if (!method_exists($service, $methodName)) {
            throw new NotFound();
        }

        $data = $job->get('data');

        if ($isDeprecated) {
            $data = Json::decode(Json::encode($data), true);
        }

        // set container to service if it needs
        if (method_exists($service, 'setContainer')) {
            $service->setContainer($this->getContainer());
        }

        $service->$methodName($data, $job->get('targetId'), $job->get('targetType'));
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
     * @return JobUtil
     */
    protected function getCronJobUtil()
    {
        if (is_null($this->treoCronJobUtil)) {
            $this->treoCronJobUtil = new JobUtil($this->getConfig(), $this->getEntityManager());
            $this->treoCronJobUtil->setEventManager($this->getContainer()->get('eventManager'));
        }

        return $this->treoCronJobUtil;
    }
}
