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

declare(strict_types = 1);

namespace Espo\Modules\Pim\Services;

use Espo\Modules\Completeness\Services\Completeness as ParentCompleteness;
use Espo\Modules\Pim\Services\Product as ProductService;
use Espo\ORM\Entity;
use Espo\Core\Utils\Util;
use Espo\Core\Exceptions;

/**
 * Completeness service
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Completeness extends ParentCompleteness
{

    /**
     * Construct
     */
    public function __construct(...$args)
    {
        // call parent
        parent::__construct(...$args);

        /**
         * Dependencies
         */
        $this->addDependency('serviceFactory');
    }

    /**
     * Update completeness
     *
     * @param Entity $entity
     * @param bool $showException
     *
     * @return Entity
     */
    public function updateCompleteness(Entity $entity, bool $showException = true): Entity
    {
        // prepare name
        $entityName = 'Product';

        if ($this->getEntityName($entity) == $entityName) {
            if ($this->hasCompleteness($entityName)) {
                // get attributes
                $attributes = $this->getProductService()->getAttributes($entity->get('id'));

                // get requireds
                $requireds = $this->getRequireds($entityName);

                // get required attributes
                $requiredAttributes = $this->getRequiredAttributes($attributes);

                // get total
                $total = count($requireds) + count($requiredAttributes);

                // prepare complete
                $complete = 0;

                if ($total > 0) {
                    // prepare coefficient
                    $coefficient = 100 / $total;

                    /**
                     * For fields
                     */
                    foreach ($requireds as $field) {
                        if (!empty($entity->get($field))) {
                            $complete += $coefficient;
                        }
                    }
                    foreach ($requiredAttributes as $attribute) {
                        if (!empty($attribute['value'])) {
                            $complete += $coefficient;
                        }
                    }
                    $entity->set('complete', $complete);

                    /**
                     * For multilang fields
                     */
                    if ($this->getConfig()->get('isMultilangActive')) {
                        // get requireds
                        $multilangRequireds = $this->getRequireds($entityName, true);

                        // get required attributes
                        $multilangRequiredAttributes = $this->getRequiredAttributes($attributes, true);

                        // get total
                        $total = count($multilangRequireds) + count($multilangRequiredAttributes);

                        // prepare coefficient
                        $multilangCoefficient = 100 / $total;

                        foreach ($this->getLanguages() as $language) {
                            $multilangComplete = 0;
                            foreach ($multilangRequireds as $field) {
                                if (!empty($entity->get(Util::toCamelCase($field.'_'.strtolower($language))))) {
                                    $multilangComplete += $multilangCoefficient;
                                }
                            }
                            foreach ($multilangRequiredAttributes as $attribute) {
                                if (!empty($attribute[Util::toCamelCase('value_'.strtolower($language))])) {
                                    $multilangComplete += $multilangCoefficient;
                                }
                            }
                            $entity->set(Util::toCamelCase('complete_'.strtolower($language)), $multilangComplete);
                        }
                    }
                }

                // checking activation
                if (!empty($entity->get('isActive')) && $complete < 100) {
                    if ($showException) {
                        throw new Exceptions\Error($this->translate('activationFailed'));
                    } else {
                        $entity->set('isActive', 0);
                    }
                }
            }
        } else {
            // call parent action
            $entity = parent::updateCompleteness($entity, $showException);
        }

        return $entity;
    }

    /**
     * Get required attributes
     *
     * @param array $attributes
     * @param bool $isMultilang
     *
     * @return array
     */
    protected function getRequiredAttributes(array $attributes, bool $isMultilang = false): array
    {
        // prepare result
        $result = [];

        // prepare multilang types
        $multilangTypes = array_keys($this->getConfig()->get('modules.multilangFields'));

        foreach ($attributes as $attribute) {
            if ($isMultilang) {
                if (!empty($attribute['isRequired']) && in_array($attribute['type'], $multilangTypes)) {
                    $result[] = $attribute;
                }
            } else {
                if ($attribute['isRequired']) {
                    $result[] = $attribute;
                }
            }
        }

        return $result;
    }

    /**
     * Get product service
     *
     * @return ProductService
     */
    protected function getProductService(): ProductService
    {
        return $this->getInjection('serviceFactory')->create('Product');
    }
}
