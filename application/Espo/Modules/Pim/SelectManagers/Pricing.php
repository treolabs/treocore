<?php
declare(strict_types=1);

namespace Espo\Modules\Pim\SelectManagers;

use Espo\Modules\Pim\Core\SelectManagers\AbstractSelectManager;

/**
 * Class of Pricing
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
class Pricing extends AbstractSelectManager
{

    /**
     * NotLinkedWithChannel filter
     *
     * @param array $result
     */
    protected function boolFilterNotLinkedWithChannel(&$result)
    {
        $channelId = (string)$this->getSelectCondition('notLinkedWithChannel');
        if (!empty($channelId)) {
            $channel = $this->getEntityManager()
                ->getRepository('Pricing')
                ->distinct()
                ->join('channels')
                ->where(['channels.id' => $channelId])
                ->find();
            foreach ($channel as $row) {
                $result['whereClause'][] = [
                    'id!=' => $row->get('id')
                ];
            }
        }
    }
}
