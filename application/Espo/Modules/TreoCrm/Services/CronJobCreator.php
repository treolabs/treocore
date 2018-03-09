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

namespace Espo\Modules\TreoCrm\Services;

use Espo\Core\Services\Base;
use Espo\Core\CronManager;
use Cron\CronExpression;

/**
 * CronJobCreator service
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class CronJobCreator extends Base
{
    /**
     * @var array
     */
    protected $dependencies = [
        'config',
        'entityManager',
        'user',
        'metadata',
        'serviceFactory'
    ];

    /**
     * @var array
     */
    protected $cronJobConfig = null;

    /**
     * Create jobs
     */
    public function createJobs()
    {
        foreach ($this->getScheduledJobs() as $scheduledJob) {
            // create cron expression object
            try {
                $cronExpression = CronExpression::factory($scheduledJob['scheduling']);
            } catch (\Exception $e) {
                continue;
            }

            // get next date
            try {
                $nextDate = $cronExpression->getNextRunDate()->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                continue;
            }

            // checking if already exists
            if ($this->isExistingJob($scheduledJob, $nextDate)) {
                continue;
            }

            $jobEntity = $this->getEntityManager()->getEntity('Job');
            $jobEntity->set([
                'name'        => $scheduledJob['name'],
                'status'      => CronManager::PENDING,
                'executeTime' => $nextDate,
                'serviceName' => $scheduledJob['service'],
                'method'      => $scheduledJob['method'],
                'data'        => $scheduledJob['data'],
            ]);
            $this->getEntityManager()->saveEntity($jobEntity);
        }
    }

    /**
     * Get scheduled jobs
     *
     * @return array
     */
    protected function getScheduledJobs(): array
    {
        // prepare result
        $result = [];

        // get config
        $config = $this->getJobsConfig();

        // set config scheduled jobs
        if (!empty($config['scheduledJobs'])) {
            $result = array_merge($result, $config['scheduledJobs']);
        }

        // set scheduled jobs from services
        if (!empty($config['scheduledJobsServices'])) {
            foreach ($config['scheduledJobsServices'] as $serviceName) {
                $service = $this->getInjection('serviceFactory')->create($serviceName);
                if ($service instanceof InterfaceJobCreatorService) {
                    $result = array_merge($result, $service->getScheduledJobs());
                }
            }
        }

        return $result;
    }

    /**
     * Get jobs config
     *
     * @return array
     */
    protected function getJobsConfig(): array
    {
        if (is_null($this->cronJobConfig)) {
            // prepare cronJobConfig
            $this->cronJobConfig = [];

            // get module list
            foreach ($this->getInjection('metadata')->getModuleList() as $module) {
                // get path to import config
                $pathToFile = 'application/Espo/Modules/'.$module.'/Configs/Jobs.php';
                // include and merge import config
                if (file_exists($pathToFile)) {
                    $this->cronJobConfig = array_merge_recursive($this->cronJobConfig, include $pathToFile);
                }
            }
        }

        return $this->cronJobConfig;
    }

    /**
     * Is such job already exist?
     *
     * @param  array $scheduledJob
     * @param  string $time
     *
     * @return bool
     */
    protected function isExistingJob(array $scheduledJob, string $time): bool
    {
        $dateObj            = new \DateTime($time);
        $timeWithoutSeconds = $dateObj->format('Y-m-d H:i:');

        $pdo   = $this->getEntityManager()->getPDO();
        $query = "
            SELECT * FROM job
            WHERE
                service_name = ".$pdo->quote($scheduledJob['service'])."
                AND method = ".$pdo->quote($scheduledJob['method'])."
                AND execute_time LIKE ".$pdo->quote($timeWithoutSeconds.'%')."
                AND deleted = 0
            LIMIT 1";

        $sth = $pdo->prepare($query);
        $sth->execute();

        return !empty($sth->fetchAll(\PDO::FETCH_ASSOC));
    }
}
