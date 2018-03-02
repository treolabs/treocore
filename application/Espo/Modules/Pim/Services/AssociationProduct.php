<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM ist Open Source Product Information Managegement (PIM) application,
 * based on EspoCRM.
 * Copyright (C) 2017-2018 Zinit Solutions GmbH
 * Website: http://www.treopim.com
 *
 * TreoPIM as well es EspoCRM is free software: you can redistribute it and/or modify
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

namespace Espo\Modules\Pim\Services;

use Espo\Core\Exceptions;
use Espo\Core\Utils\Util;

/**
 * AssociationProduct service
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
class AssociationProduct extends AbstractTechnicalService
{

    /**
     * Read associationProduct
     *
     * @param string $id
     *
     * @return array
     * @throws Exceptions\Forbidden
     */
    public function getAssociationProduct(string $id): array
    {
        $result = [];

        // get associationProduct
        $data = $this->getDBAssociationProduct($id);

        if (!empty($data)) {
            // check acl
            $isGrantedMainProduct = $this->checkAcl('Product', $data['mainProductId'], 'read');
            $isGrantedRelatedProduct = $this->checkAcl('Product', $data['relatedProductId'], 'read');
            $isGrantedAssociation = $this->checkAcl('Association', $data['associationId'], 'read');
            // set data
            if ($isGrantedMainProduct && $isGrantedRelatedProduct && $isGrantedAssociation) {
                $result = $data;
            }
        }

        return $result;
    }

    /**
     * Create new AssociationProduct
     *
     * @param array $data
     *
     * @return bool
     * @throws Exceptions\BadRequest
     * @throws Exceptions\Forbidden
     * @throws Exceptions\Error
     */
    public function createAssociationProduct(array $data): bool
    {
        // prepare result
        $result = false;
        // fields for create Association product
        $fields = ['mainProductId', 'relatedProductId', 'associationId'];

        // check data
        $isValid = $this->isValidCreateData($data, $fields);
        $isGranted = $this->checkAcl('Product', $data['mainProductId'], 'edit');

        // check acl and data
        if ($isValid && $isGranted && $this->isUnusedAssociation($data)) {
            // prepare data
            $data = $this->prepareData($data, $fields);

            // create value in DB
            $result = $this->createDBAssociationProduct($data);
        }

        return $result;
    }

    /**
     * Update association product
     *
     * @param string $id
     * @param array  $data
     *
     * @return bool
     * @throws Exceptions\BadRequest
     * @throws Exceptions\Forbidden
     * @throws Exceptions\NotFound
     * @throws Exceptions\Error
     */
    public function updateAssociationProduct(string $id, array $data): bool
    {
        $result = false;

        // get associationProduct
        $associationProduct = $this->getDBAssociationProduct($id);

        // check if exists associationProduct
        if (empty($associationProduct)) {
            throw new Exceptions\NotFound();
        }
        // check acl
        $isGranted = $this->checkAcl('Product', $associationProduct['mainProductId'], 'edit');
        // check is unused association
        $isUnused = $this->isUnusedAssociation(array_merge($associationProduct, $data));

        if ($isGranted && $isUnused) {
            // prepare data
            $fields = ['associationId', 'relatedProductId'];
            $data = $this->prepareData($data, $fields);

            // check if exists data
            if (empty($data)) {
                // get message
                $message = $this->getTranslate('notValid', 'exceptions', 'AbstractTechnical');
                throw new Exceptions\BadRequest($message);
            }

            $result = $this->updateDBAssociationProduct($id, $data);
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
     * @throws Exceptions\NotFound
     */
    public function deleteAssociationProduct(string $id): bool
    {
        $result = false;

        // get associationProduct
        $associationProduct = $this->getDBAssociationProduct($id);

        // check if exists associationProduct
        if (empty($associationProduct)) {
            throw new Exceptions\NotFound();
        }

        // checkAcl
        if ($this->checkAcl('Product', $associationProduct['mainProductId'], 'edit')) {
            // delete associationProduct
            $pdo = $this->getEntityManager()->getPDO();
            // prepare query
            $sql = "UPDATE association_product 
                SET deleted = 1
                WHERE id = :id;";
            $sth = $pdo->prepare($sql);

            $result = $sth->execute([':id' => $id]);
        }


        return $result;
    }

    /**
     * Update value in DB
     *
     * @param string $id
     * @param array  $data
     *
     * @return bool
     */
    protected function updateDBAssociationProduct(string $id, array $data): bool
    {
        $pdo = $this->getEntityManager()->getPDO();

        // prepare query
        $sql = "UPDATE association_product 
                SET %s
                WHERE id = %s;";
        $sql = sprintf($sql, implode($data, ','), $pdo->quote($id));
        $sth = $pdo->prepare($sql);

        return $sth->execute();
    }

    /**
     * Get associationProductFromDB
     *
     * @param string $id
     *
     * @return array
     */
    protected function getDBAssociationProduct(string $id): array
    {
        // prepare result
        $result = [];

        $sql = "SELECT 
                  ap.id,
                  ass.name    AS associationName,
                  ass.id      AS associationId,
                  p_rel.name  AS relatedProductName,
                  p_rel.id    AS relatedProductId,
                  p_main.name AS mainProductName,
                  p_main.id   AS mainProductId
                FROM 
                  association_product AS ap 
                JOIN association AS ass 
                  ON ass.id = ap.association_id AND ap.deleted = 0
                JOIN product AS p_rel 
                  ON p_rel.id = ap.related_product_id AND p_rel.deleted = 0
                JOIN product AS p_main 
                  ON p_main.id = ap.main_product_id AND p_main.deleted = 0
                WHERE ap.deleted = 0 AND ap.id = :id ";

        $sth = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute([':id' => $id]);
        $result = $sth->fetch(\PDO::FETCH_ASSOC);

        return $result ? $result : [];
    }

    /**
     * Create in DataBase
     *
     * @param array $data
     *
     * @return bool
     */
    protected function createDBAssociationProduct(array $data): bool
    {
        $pdo = $this->getEntityManager()->getPDO();

        // prepare query
        $sql = "INSERT INTO association_product 
                SET id = %s,
                    deleted =  0, 
                    %s;";
        $sql = sprintf($sql, $pdo->quote(Util::generateId()), implode($data, ','));
        $sth = $pdo->prepare($sql);

        return $sth->execute();
    }


    /**
     * Check if association already exists
     *
     * @param array $data
     *
     * @return bool
     * @throws Exceptions\Error
     */
    protected function isUnusedAssociation(array $data): bool
    {
        $usedAssociation = $this
            ->getSelectManager('Association')
            ->getAssociatedProductAssociations($data['mainProductId'], $data['relatedProductId']);

        // check if exists Association
        foreach ($usedAssociation as $row) {
            if ($data['associationId'] === $row['association_id']) {
                // get message
                $message = $this->getTranslate('isExistsAssociation', 'exceptions', 'AssociationProduct');

                throw new Exceptions\Error($message);
            }
        }

        return true;
    }

    /**
     * Prepare data
     *
     * @param array $data
     * @param array $fields
     *
     * @return array
     */
    protected function prepareData(array $data, array $fields)
    {
        // prepare result
        $result = [];

        // prepare and set data
        foreach ($data as $key => $value) {
            if (in_array($key, $fields, true) && !empty($value)) {
                $result[] = Util::fromCamelCase($key) . " = '" . (string)$value . "'";
            }
        }

        return $result;
    }
}
