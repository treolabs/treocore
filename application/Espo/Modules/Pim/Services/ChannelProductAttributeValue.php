<?php
declare(strict_types = 1);

namespace Espo\Modules\Pim\Services;

use Espo\Core\Exceptions;
use Espo\Core\Utils\Util;

/**
 * ChannelProductAttributeValue service
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
class ChannelProductAttributeValue extends AbstractTechnicalService
{

    /**
     * Update value
     *
     * @param string $id
     * @param array  $data
     *
     * @return bool
     * @throws Exceptions\Forbidden
     * @throws Exceptions\BadRequest
     * @throws Exceptions\NotFound
     */
    public function updateValue(string $id, array $data): bool
    {
        $result = false;

        // get productId
        $productId = $this->getDBProductId($id);

        // check if exists value
        if (empty($productId)) {
            throw new Exceptions\NotFound();
        }

        if ($this->checkAcl('Product', $productId, 'edit')) {
            // check if exists data
            if (empty($data)) {
                throw new Exceptions\BadRequest();
            }

            // prepare data
            $data = $this->prepareData($data);

            $result = $this->updateDBValue($id, $data);
        }

        return $result;
    }

    /**
     * Create value
     *
     * @param array $data
     * @param bool  $checkAcl
     *
     * @return string
     * @throws Exceptions\BadRequest
     * @throws Exceptions\Forbidden
     */
    public function createValue(array $data, $checkAcl = true): string
    {
        // prepare result
        $result = false;


        // check data
        $requiredParams = ['productId', 'channelId', 'attributeId'];
        $isDataValid = $this->isValidCreateData($data, $requiredParams);
        // check acl
        $isGranted = $checkAcl ? $this->checkAcl('Product', $data['productId'], 'edit') : true;

        if ($isDataValid && $isGranted) {
            // prepare data
            $data = $this->prepareData($data);

            // create value in DB
            $result = $this->createDBvalue($data);
        }

        return $result;
    }

    /**
     * Delete value
     *
     * @param string $id
     *
     * @return bool
     * @throws Exceptions\Forbidden
     */
    public function deleteValue(string $id): bool
    {
        $result = false;

        // check if Attribute is MultiChannel
        if ($this->isMultiChannel($id)) {
            // prepare message
            $message = $this->getTranslate('isMultiChannel', 'exceptions', 'ChannelProductAttributeValue');
            throw new Exceptions\Forbidden($message);
        }

        // check acl
        $productId = $this->getDBProductId($id);
        if ($this->checkAcl('Product', $productId, 'edit')) {
            // delete value
            $pdo = $this->getEntityManager()->getPDO();

            // prepare query
            $sql = "UPDATE channel_product_attribute_value 
                SET deleted = 1
                WHERE id = %s;";
            $sql = sprintf($sql, $pdo->quote($id));
            $sth = $pdo->prepare($sql);

            $result = $sth->execute();
        }

        return $result;
    }

    /**
     * Check if Attribute is MultiChannel
     *
     * @param string $id
     *
     * @return bool
     */
    protected function isMultiChannel(string $id): bool
    {
        // prepare query
        $sql = "SELECT pfa.is_multi_channel AS isMultiChannel
                FROM product_family_attribute as pfa
                  JOIN channel_product_attribute_value AS cpav
                    ON cpav.id = :id
                  JOIN product AS p ON p.id = cpav.product_id
                  JOIN product_family AS pf
                    ON pf.id = p.product_family_id AND pf.deleted = 0 AND pf.id = pfa.product_family_id
                WHERE pfa.attribute_id = cpav.attribute_id AND pfa.deleted = 0";
        // get result
        $pdo = $this->getEntityManager()->getPDO();
        $sth = $pdo->prepare($sql);
        $sth->execute([':id' => $id]);

        return (bool)$sth->fetchColumn();
    }

    /**
     * Update value in DB
     *
     * @param string $id
     * @param array  $data
     *
     * @return bool
     */
    protected function updateDBValue(string $id, array $data): bool
    {
        $pdo = $this->getEntityManager()->getPDO();

        // prepare query
        $sql = "UPDATE channel_product_attribute_value 
                SET %s
                WHERE id = %s;";
        $sql = sprintf($sql, implode($data, ','), $pdo->quote($id));
        $sth = $pdo->prepare($sql);

        return $sth->execute();
    }

    /**
     * Create value in DataBase
     *
     * @param array $data
     *
     * @return string
     */
    protected function createDBValue(array $data): string
    {
        $pdo = $this->getEntityManager()->getPDO();

        $id = Util::generateId();
        // prepare query
        $sql = "INSERT INTO channel_product_attribute_value 
                SET id = %s,
                    deleted =  0, 
                    %s;";
        $sql = sprintf($sql, $pdo->quote($id), implode($data, ','));
        $result = $pdo->prepare($sql)->execute();

        return $result ? $id : '';
    }

    /**
     * Get product id by channelProductAttributeValueId
     *
     * @param string $channelProdAttrValId
     *
     * @return null|string
     */
    protected function getDBProductId(string $channelProdAttrValId): string
    {
        $pdo = $this->getEntityManager()->getPDO();
        // prepare query
        $sql = "SELECT product_id FROM channel_product_attribute_value WHERE id = :id AND deleted = 0";

        $sth = $pdo->prepare($sql);
        $sth->execute([':id' => $channelProdAttrValId]);

        $result = $sth->fetchColumn();

        return $result ? $result : '';
    }

    /**
     * Serialization data in json array if value Multi-enum
     *
     * @param array $data
     *
     * @return array
     */
    protected function prepareData(array $data): array
    {
        // prepare result
        $result = [];

        foreach ($data as $key => $value) {
            // prepare value
            $value = is_array($value) ? json_encode($value) : (string)$value;

            $result[] = Util::fromCamelCase($key) . " = '" . $value . "'";
        }

        return $result;
    }
}
