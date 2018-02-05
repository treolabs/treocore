<?php
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
