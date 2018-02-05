<?php
declare(strict_types = 1);

namespace Espo\Modules\Pim\Services;

use PDO;

/**
 * Service of ProductFamily
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
class ProductFamily extends \Espo\Core\Templates\Services\Base
{

    /**
     * Has system product family?
     *
     * @param array $productFamilyIds
     * @return bool
     */
    public function hasSystemProductFamily(array $productFamilyIds): bool
    {
        $result = false;

        foreach ($this->getProductFamilyData($productFamilyIds) as $productFamily) {
            if ($productFamily['is_system']) {
                $result = true;
                break;
            }
        }

        return $result;
    }

    /**
     * Get Attributes
     *
     * @param string $familyId
     *
     * @return array
     */
    public function getAttributes(string $familyId): array
    {
        $result = $this->formatAttribute($this->getFamilyAttributes($familyId));
        return $result;
    }

    /**
     * Return formatted attribute data for get actions
     *
     * @param array $attributes
     *
     * @return array
     */
    protected function formatAttribute(array $attributes = []): array
    {
        foreach ($attributes as $key => $attribute) {
            $attributes[$key]['isMultiChannel'] = (bool) $attribute['isMultiChannel'];
            $attributes[$key]['isRequired'] = (bool) $attribute['isRequired'];
        }

        return $attributes;
    }

    /**
     * Get Attributes from DB
     *
     * @param string $familyId
     *
     * @return array
     */
    protected function getFamilyAttributes(string $familyId): array
    {
        // prepare pdo
        $pdo = $this->getEntityManager()->getPDO();
        $sql = "SELECT
                  pfa.id                   AS productFamilyAttributeId,
                  pfa.is_multi_channel     AS isMultiChannel,
                  pfa.is_required          AS isRequired,
                  a.id                     AS attributeId,
                  a.name                   AS attributeName,
                  a.type                   AS attributeType,
                  a.attribute_group_id AS attributeGroupId,
                  ag.name                  AS attributeGroupName
                FROM attribute AS a
                  JOIN product_family_attribute AS pfa 
                    ON a.id = pfa.attribute_id
                  LEFT JOIN attribute_group AS ag 
                    ON ag.id = a.attribute_group_id AND ag.deleted = 0
                WHERE a.deleted = 0 AND pfa.deleted = 0 AND pfa.product_family_id = ".$pdo->quote($familyId);

        // execute
        $sth = $pdo->prepare($sql);
        $sth->execute();

        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get product family data from DB by ids
     *
     * @param array $productFamilyIds
     * @return array
     */
    protected function getProductFamilyData(array $productFamilyIds): array
    {
        // prepare pdo
        $pdo = $this->getEntityManager()->getPDO();
        $sql = "SELECT
                  id,
                  is_system
                FROM product_family
                WHERE id IN ('".implode("','", $productFamilyIds)."') AND deleted = 0";

        // execute
        $sth = $pdo->prepare($sql);
        $sth->execute();

        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }
}
