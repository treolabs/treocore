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

use Espo\Modules\Pim\Entities\ProductAttributeValue as Entity;
use Espo\Core\Utils\Util;

/**
 * ProductAttributeValue service
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class ProductAttributeValue extends AbstractService
{

    /**
     * Handle audited attribute
     *
     * @param Entity $attribute
     * @param array $post
     * @param string $productId
     *
     * @return void
     */
    public function handleAuditedAttribute(Entity $attribute, array $post, string $productId): void
    {
        if (!empty($attribute->get('attribute'))) {
            // get note data
            $data = $this->getNoteData($attribute, $post);

            if (!empty($data['attributes']['was'])) {
                // create note
                $note = $this->getEntityManager()->getEntity('Note');
                $note->set('type', 'Update');
                $note->set('parentId', $productId);
                $note->set('parentType', 'Product');
                $note->set('data', $data);
                $note->set('attributeId', $post['attributeId']);

                $this->getEntityManager()->saveEntity($note);
            }
        }
    }

    /**
     * Get note data
     *
     * @param Entity $attribute
     * @param array $post
     *
     * @return array
     */
    protected function getNoteData(Entity $attribute, array $post): array
    {
        // prepare field name
        $fieldName = $this->translate('Attribute').' '.$attribute->get('attribute')->get('name');

        // prepare result
        $result = [];

        // for value
        if ($post['value'] != $attribute->get('value')) {
            $result['fields'][]                         = $fieldName;
            $result['attributes']['was'][$fieldName]    = $attribute->get('value');
            $result['attributes']['became'][$fieldName] = $post['value'];
        }

        // for multilang value
        if ($this->getConfig()->get('isMultilangActive')) {
            foreach ($this->getConfig()->get('inputLanguageList') as $locale) {
                // prepare field
                $field = Util::toCamelCase('value_'.strtolower($locale));

                if (isset($post[$field]) && $post[$field] != $attribute->get($field)) {
                    // prepare field name
                    $localeFieldName = $fieldName." ($locale)";

                    $result['fields'][]                               = $localeFieldName;
                    $result['attributes']['was'][$localeFieldName]    = $attribute->get($field);
                    $result['attributes']['became'][$localeFieldName] = $post[$field];
                }
            }
        }

        return $result;
    }

    /**
     * Translate
     *
     * @param string $key
     *
     * @return string
     */
    protected function translate(string $key): string
    {
        return $this->getTranslate($key, 'custom', 'ProductAttributeValue');
    }
}
