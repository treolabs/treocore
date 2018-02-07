<?php
declare(strict_types = 1);

namespace Espo\Modules\Multilang\Metadata;

use Espo\Modules\TreoCrm\Metadata\AbstractMetadata;
use Espo\Core\Utils\Util;

/**
 * Class Metadata
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
class Metadata extends AbstractMetadata
{

    /**
     * All MultiLang fields
     *
     * @access protected
     * @var array
     */
    protected $fieldsMultiLang = [
        'textMultiLang'      => [
            'fieldType'        => 'text',
            'typeNestedFields' => 'text',
            'paramsDefault'    => false
        ],
        'varcharMultiLang'   => [
            'fieldType'        => 'varchar',
            'typeNestedFields' => 'varchar',
            'paramsDefault'    => false
        ],
        'enumMultiLang'      => [
            'typeNestedFields' => 'varchar',
            'fieldType'        => 'enum',
            'isOptions'        => true,
            'paramsDefault'    => true
        ],
        'multiEnumMultiLang' => [
            'typeNestedFields' => 'jsonArray',
            'fieldType'        => 'multiEnum',
            'isOptions'        => true,
            'paramsDefault'    => true
        ],
        'arrayMultiLang'     => [
            'typeNestedFields' => 'jsonArray',
            'fieldType'        => 'array',
            'isOptions'        => true,
            'paramsDefault'    => true
        ]
    ];


    /**
     * Default field definitions for nested fields.
     *
     * @access protected
     * @var string
     */
    protected $multiLangFieldDefs = [
        'layoutListDisabled'       => true,
        'layoutDetailDisabled'     => true,
        'layoutFiltersDisabled'    => true,
        'layoutMassUpdateDisabled' => true,
        'customizationDisabled'    => true
    ];

    /**
     * Modify
     *
     * @param array $data
     *
     * @return array
     */
    public function modify(array $data): array
    {
        $config = $this->getContainer()->get('config');

        if ($config->get('isMultilangActive')) {
            // get languages
            $languages = $config->get('inputLanguageList');
            if (!empty($languages)) {
                // add get MultiLang metadata
                $multilangMetadata = $this->getMultiLangMetadata($languages);

                // load additional metadata for multilang fields
                foreach ($multilangMetadata as $fieldName => $fieldData) {
                    if (isset($data['fields'][$fieldName])) {
                        $data['fields'][$fieldName] = array_merge_recursive($data['fields'][$fieldName], $fieldData);
                    }
                }
            }

            // modify fields in entity to multilang type
            $data['entityDefs'] = $this->modifyEntityFieldsToMultilang($data['entityDefs'], $multilangMetadata);
        } else {
            // deactivete MultiLang if not exists
            $data['fields'] = $this->deactivateMultilangFields($data['fields']);
        }

        return $data;
    }

    /**
     * Change fields type to multilang type
     *
     * @param array $entityDefs
     * @param array $multilangMetadata
     *
     * @return array
     */
    protected function modifyEntityFieldsToMultilang(array $entityDefs, array $multilangMetadata): array
    {
        // search multilang fields in entity
        foreach ($entityDefs as $entityName => $defs) {
            foreach ($defs['fields'] as $fieldName => $feidsDefs) {
                // check is a multilang field
                if ($feidsDefs['isMultilang']) {
                    $multilangType = $this->getMultilangTypeName($feidsDefs['type']);

                    if (isset($multilangType)) {
                        // change fields type  on type multilang
                        $entityDefs[$entityName]['fields'][$fieldName]['type'] = $multilangType;

                        // load additional multilang fields to entity
                        foreach ($multilangMetadata[$multilangType]['fields'] as $languagePrefix => $additionalData) {
                            $entityDefs[$entityName]['fields'] = array_merge(
                                $entityDefs[$entityName]['fields'],
                                [$fieldName . ucfirst(Util::toCamelCase($languagePrefix)) => $additionalData]
                            );
                        }
                    }
                }
            }
        }

        return $entityDefs;
    }

    /**
     * Get multilang type name
     * (examle: $fieldType = 'text' - return 'textMultilang')
     *
     * @param string $fieldType
     *
     * @return null|string
     */
    protected function getMultilangTypeName(string $fieldType)
    {
        // find if exists multilang type
        foreach ($this->fieldsMultiLang as $multilangTypeName => $data) {
            // find if exists multilang type
            if ($data['fieldType'] === $fieldType) {
                return $multilangTypeName;
            }
        }
    }

    /**
     * Get Metadata for multiLang fields
     *
     * @param array $languages
     *
     * @access protected
     * @return array
     */
    protected function getMultiLangMetadata(array $languages = [])
    {
        $metadataFields = [];
        //Parameters for if need set default data
        $defaultParameters = [];

        foreach ($languages as $language) {
            $language = strtolower($language);
            foreach ($this->fieldsMultiLang as $fields => $data) {
                //Set default option
                $defaultParameters[$fields]['paramsDefault'] = isset($defaultParameters[$fields]['paramsDefault']) ?
                    $defaultParameters[$fields]['paramsDefault'] :
                    $data['paramsDefault'];

                //Set data for all type multiLang fields
                $metadataFields[$fields]['actualFields'][] = $language;
                $metadataFields[$fields]['fields'][$language] = $this->multiLangFieldDefs;
                $metadataFields[$fields]['fields'][$language]['type'] = $data['typeNestedFields'];

                //Set default params if this option is in config for field
                if ($defaultParameters[$fields]['paramsDefault']) {
                    $defaultParameters[$fields]['paramsDefault'] = false;
                }
                //If fields is enum and multiEnum - set options
                if ($data['isOptions']) {
                    $metadataFields[$fields]['params'][] = $this->getOptionsMultiLang($language);
                }
            }
        }

        return $metadataFields;
    }

    /**
     * Get options for enum and multiEnum fields
     *
     * @param string $language
     *
     * @return array
     */
    protected function getOptionsMultiLang(string $language): array
    {
        $options = [];
        $options['name'] = Util::toCamelCase('options_' . $language);
        $options['type'] = 'array';
        $options['view'] = 'multilang:views/admin/field-manager/fields/optionsMultiLang';

        return $options;
    }

    /**
     * Remove Multilang fields form metadata if Multilang is not active
     *
     * @param array $fieldsDefs
     *
     * @return array
     */
    protected function deactivateMultilangFields(array $fieldsDefs): array
    {
        foreach ($this->fieldsMultiLang as $fieldName => $data) {
            unset($fieldsDefs[$fieldName]);
        }

        return $fieldsDefs;
    }
}
