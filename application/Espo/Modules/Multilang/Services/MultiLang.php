<?php

namespace Espo\Modules\Multilang\Services;

use Espo\Core\Templates\Services\Base;
use Espo\Core\Utils\Util;

/**
 * MultiLang service
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
class MultiLang extends Base
{
    /**
     * Default field definitions for nested fields.
     *
     * @access protected
     * @var string
     */
    protected $multiLangFieldDefs = [
        'layoutListDisabled'       => true,
        'layoutDetailDisabled'     => true,
        'layoutFiltersDisabled'     => true,
        'layoutMassUpdateDisabled' => true,
        'customizationDisabled'    => true
    ];

    /**
     * Default params for fields
     *
     * @access protected
     * @var array
     */
    protected $multiLangParamDefs = ['__APPEND__'];

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
            'metadataPath'     => 'custom/Espo/Custom/Resources/metadata/fields/textMultiLang.json',
            'paramsDefault'    => false
        ],
        'varcharMultiLang'   => [
            'fieldType'        => 'varchar',
            'typeNestedFields' => 'varchar',
            'metadataPath'     => 'custom/Espo/Custom/Resources/metadata/fields/varcharMultiLang.json',
            'paramsDefault'    => false
        ],
        'enumMultiLang'      => [
            'typeNestedFields' => 'varchar',
            'fieldType'        => 'enum',
            'isOptions'        => true,
            'paramsDefault'    => true,
            'metadataPath'     => 'custom/Espo/Custom/Resources/metadata/fields/enumMultiLang.json'
        ],
        'multiEnumMultiLang' => [
            'typeNestedFields' => 'jsonArray',
            'fieldType'        => 'multiEnum',
            'isOptions'        => true,
            'paramsDefault'    => true,
            'metadataPath'     => 'custom/Espo/Custom/Resources/metadata/fields/multiEnumMultiLang.json'
        ],
        'arrayMultiLang'     => [
            'typeNestedFields' => 'jsonArray',
            'fieldType'        => 'array',
            'isOptions'        => true,
            'paramsDefault'    => true,
            'metadataPath'     => 'custom/Espo/Custom/Resources/metadata/fields/arrayMultiLang.json'
        ]
    ];

    /**
     * Regenerate metadata for MultiLang fields
     *
     * @access public
     *
     * @param array $languages
     */
    public function regenerateMultiLang(array $languages)
    {
        $fileManager = $this->getFileManager();

        if (!empty($languages)) {
            $metaData = $this->getMultiLangMetadata($languages);
            foreach ($this->fieldsMultiLang as $fieldName => $fieldData) {
                $fileManager->putContentsJson($fieldData['metadataPath'], $metaData[$fieldName]);
            }
        } else {
            foreach ($this->fieldsMultiLang as $fieldName => $fieldData) {
                foreach ($this->fieldsMultiLang as $fieldData) {
                    $fileManager->unlink($fieldData['metadataPath']);
                }
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
    protected function getMultiLangMetadata($languages)
    {
        $metadataFields    = [];
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
                $metadataFields[$fields]['actualFields'][]            = $language;
                $metadataFields[$fields]['fields'][$language]         = $this->multiLangFieldDefs;
                $metadataFields[$fields]['fields'][$language]['type'] = $data['typeNestedFields'];

                //Set default params if this option is in config for field
                if ($defaultParameters[$fields]['paramsDefault']) {
                    $metadataFields[$fields]['params']           = $this->multiLangParamDefs;
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
    protected function getOptionsMultiLang($language)
    {
        $options         = [];
        $options['name'] = Util::toCamelCase('options_'.$language);
        $options['type'] = 'array';
        $options['view'] = 'multilang:views/admin/field-manager/fields/optionsMultiLang';

        return $options;
    }
}
