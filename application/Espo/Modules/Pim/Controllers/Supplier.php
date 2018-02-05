<?php
declare(strict_types = 1);

namespace Espo\Modules\Pim\Controllers;

use Espo\Core\Exceptions;

/**
 * Supplier controller
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
class Supplier extends AbstractController
{
    /**
     * Get Product action
     *
     * @ApiDescription(description="Get Product in Supplier")*
     * @ApiMethod(type="GET")
     * @ApiRoute(name="/Markets/Supplier/{supplier_id}/product")
     * @ApiParams(name="supplier_id", type="string", is_required=1, description="Supplier id")
     * @ApiReturn(sample="[{
     *     'supplierProductId': 'string',
     *     'productId': 'bool',
     *     'productName': 'string',
     *     'productSku': 'string',
     *     'isActive': 'bool'
     * },{}]")
     *
     * @param string $supplierId
     *
     * @return array
     * @throws Exceptions\Error
     */
    public function getProduct(string $supplierId): array
    {
        if ($this->isReadEntity($this->name, $supplierId)) {
            return $this->getRecordService()->getProduct($supplierId);
        }

        throw new Exceptions\Error();
    }
}
