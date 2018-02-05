<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Core\Utils;

use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Util;
use Espo\Core\Utils\Json;
use Espo\Entities\User;
use Espo\Modules\TreoCrm\Traits\ContainerTrait;
use Espo\Modules\TreoCrm\Services\AbstractProgressManager;

/**
 * Class of ProgressManager
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class ProgressManager
{

    /**
     * Traits
     */
    use ContainerTrait;
    /**
     * @var array
     */
    protected $progressConfig = null;

    /**
     * Create progress item
     *
     * @param string $name
     * @param string $type
     * @param array $data
     *
     * @return bool
     */
    public function push(string $name, string $type, array $data = []): bool
    {
        // prepare result
        $result = false;

        // get config
        $config = $this->getProgressConfig();

        if (isset($config['type'][$type])) {
            $result = $this->insert($name, $type, $data);
        }

        return $result;
    }

    /**
     * Get progress config
     *
     * @return array
     */
    public function getProgressConfig(): array
    {
        if (is_null($this->progressConfig)) {
            $this->progressConfig = [];
            foreach ($this->getContainer()->get('metadata')->getModuleList() as $module) {
                // prepare path
                $path = "application/Espo/Modules/{$module}/Configs/ProgressManager.php";

                if (file_exists($path)) {
                    $data = include "application/Espo/Modules/{$module}/Configs/ProgressManager.php";

                    $this->progressConfig = array_merge_recursive($this->progressConfig, $data);
                }
            }
        }

        return $this->progressConfig;
    }

    /**
     * Insert
     *
     * @param string $name
     * @param string $type
     * @param array $data
     *
     * @return bool
     */
    protected function insert(string $name, string $type, array $data): bool
    {
        // prepare data
        $result = false;

        if (!empty($name) && !empty($type)) {
            // get pdo
            $pdo = $this->getEntityManager()->getPDO();

            // prepare params
            $id     = Util::generateId();
            $name   = $pdo->quote($name);
            $type   = $pdo->quote($type);
            $data   = $pdo->quote(Json::encode($data));
            $status = $pdo->quote(AbstractProgressManager::$progressStatus['new']);
            $userId = $pdo->quote($this->getUser()->get('id'));
            $date   = $pdo->quote(date('Y-m-d H:i:s'));

            // prepare sql
            $sql = "INSERT INTO progress_manager SET "
                ."id='{$id}', progress_manager.name={$name}, progress_manager.type={$type}, "
                ."progress_manager.data={$data}, progress_manager.status={$status}, progress=0, "
                ."created_by_id={$userId}, created_at={$date}, modified_at={$date};";

            $sth = $this
                ->getEntityManager()
                ->getPDO()
                ->prepare($sql);
            $sth->execute();

            // prepare result
            $result = true;
        }

        return $result;
    }

    /**
     * Get entity manager
     *
     * @return EntityManager
     */
    protected function getEntityManager(): EntityManager
    {
        return $this->getContainer()->get('entityManager');
    }

    /**
     * Get user
     *
     * @return User
     */
    protected function getUser(): User
    {
        return $this->getContainer()->get('user');
    }
}
