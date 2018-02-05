<?php
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
