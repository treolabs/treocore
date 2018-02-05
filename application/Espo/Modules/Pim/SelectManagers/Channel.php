<?php
declare(strict_types=1);

namespace Espo\Modules\Pim\SelectManagers;

use Espo\Modules\Pim\Core\SelectManagers\AbstractSelectManager;

/**
 * Class of Channel
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Channel extends AbstractSelectManager
{

    /**
     * NotLinkedWithProduct filter
     *
     * @param array $result
     */
    protected function boolFilterNotLinkedWithProduct(&$result)
    {
        $productId = (string)$this->getSelectCondition('notLinkedWithProduct');

        if (!empty($productId)) {
            // get channel related with product
            $ProductChannels = $this->createService('Product') ->getChannels($productId);
            foreach ($ProductChannels as $row) {
                $result['whereClause'][] = [
                    'id!=' => (string)$row['channelId']
                ];
            }
        }
    }

    /**
     * NotLinkedWithPricing filter
     *
     * @param array $result
     */
    protected function boolFilterNotLinkedWithPricing(&$result)
    {
        $pricingId = (string)$this->getSelectCondition('notLinkedWithPricing');

        if (!empty($pricingId)) {
            // get channel related with product
            $channel = $this->getEntityManager()
                ->getRepository('Channel')
                ->distinct()
                ->join('pricings')
                ->where(['pricings.id' => $pricingId])
                ->find();

            // set filter
            foreach ($channel as $row) {
                $result['whereClause'][] = [
                    'id!=' => $row->get('id')
                ];
            }
        }
    }
}
