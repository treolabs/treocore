<?php
/**
 * This file is part of EspoCRM and/or TreoCore.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoCore is EspoCRM-based Open Source application.
 * Copyright (C) 2017-2019 TreoLabs GmbH
 * Website: https://treolabs.com
 *
 * TreoCore as well as EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TreoCore as well as EspoCRM is distributed in the hope that it will be useful,
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
 * and "TreoCore" word.
 */

declare(strict_types=1);

namespace Treo\Migrations;

use Treo\Core\Migration\Base;

/**
 * Migration class for version 3.25.20
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class V3Dot25Dot20 extends Base
{
    /**
     * @inheritdoc
     */
    public function up(): void
    {
        $this->getPDO()->exec(
            "DELETE FROM treo_store WHERE 1;INSERT INTO `treo_store` (`id`, `name`, `deleted`, `description`, `package_id`, `tags`, `url`, `status`, `versions`, `created_at`) VALUES
    ('ActivitiesTasks', 'Activities and Tasks', 0, 'The module enables planning and fulfillment of activities (meetings, calls, e-mails) and tasks for any entity in the system.', 'treolabs/activities-tasks', '[\"manage\",\"planning\",\"usability\"]', NULL, 'buyable', NULL, NULL),
('AdvancedClassification', 'Advanced Classification', 0, 'Advanced Classification module adds hierarchy of product families.', 'treolabs/advanced-classification', NULL, NULL, 'buyable', NULL, NULL),
('AssetRendition', 'Asset Renditions', 0, 'Module for auto creating renditions', 'treolabs/asset-rendition', '[\"manage\",\"usability\",\"assets\",\"attachments\",\"DAM\"]', NULL, 'buyable', NULL, NULL),
('AssetVersion', 'Asset Versions', 0, 'Module for creating version for assets and renditions', 'treolabs/asset-version', '[\"manage\",\"usability\",\"DAM\",\"assets\",\"renditions\"]', NULL, 'buyable', NULL, NULL),
('ColoredFields', 'Colored Fields', 0, 'Module enables defining different background colors to values of Enum and Multi-Enum field type.', 'treolabs/colored-fields', NULL, NULL, 'buyable', NULL, NULL),
('Completeness', 'Completeness', 0, 'The module enables control and improvement of the product data quality by measuring completeness of data.', 'treolabs/completeness', '[\"data\",\"quality\",\"control\"]', NULL, 'buyable', NULL, NULL),
('Connector', 'Connector', 0, 'Connector core', 'treolabs/connector', NULL, NULL, 'buyable', NULL, NULL),
('ConnectorMagento2', 'Connector Magento 2', 0, 'Connector adapter for Magento 2', 'treolabs/connector-magento2', NULL, NULL, 'buyable', NULL, NULL),
('ConnectorOdoo', 'Connector Odoo', 0, 'Connector adapter for Odoo', 'treolabs/connector-odoo', NULL, NULL, 'buyable', NULL, NULL),
('ConnectorOxid', 'Connector Oxid 6', 0, 'Connector adapter for Oxid 6', 'treolabs/connector-oxid6', NULL, NULL, 'buyable', NULL, NULL),
('Crm', 'TreoCRM', 0, 'The module enables to organize and plan sales and marketing (leads, opportunities, target lists)', 'treolabs/treo-crm', '[\"manage\",\"planning\"]', NULL, 'buyable', NULL, NULL),
('Dam', 'DAM', 0, 'Digital asset management. User-friendly mechanism of assets managing.', 'treolabs/dam', '[\"manage\",\"usability\",\"files\",\"attachments\"]', NULL, 'buyable', NULL, NULL),
('Discussions', 'Discussions', 0, 'The module extends Stream with ability to write sub-comments (discussions).', 'treolabs/discussions', '[\"stream\",\"comment\",\"discussion\"]', NULL, 'buyable', NULL, NULL),
('Export', 'Export Feeds', 0, 'The module adds possibility to use export feeds in a user-friendly way.', 'treolabs/export-feeds', '[\"usability\",\"data\",\"exchange\"]', NULL, 'buyable', NULL, NULL),
('Import', 'Import Feeds', 0, 'The module adds possibility to use import feeds in a user-friendly way.', 'treolabs/import-feeds', '[\"usability\",\"data\",\"exchange\"]', NULL, 'buyable', NULL, NULL),
('Invoices', 'Invoices', 0, 'Module Invoices for Sales module', 'treolabs/invoices', '[\"invoices\",\"sales\"]', NULL, 'buyable', NULL, NULL),
('LeadMiner', 'Lead Miner', 0, 'Module for searching leads.', 'treolabs/leadminer', '[\"leads\"]', NULL, 'buyable', NULL, NULL),
('Multilang', 'Multi-Languages', 0, 'The module enables storing multi-language and locale field values.', 'treolabs/multi-languages', NULL, NULL, 'buyable', NULL, NULL),
('NavMenu', 'Advanced Navigation', 0, 'Advanced Navigation for TreoCore.', 'treolabs/advanced-navigation', '[\"usability\",\"design\"]', NULL, 'buyable', NULL, NULL),
('PdfGenerator', 'PDF generator', 0, 'The module adds a user-friendly interface to create PDF files for entity.', 'treolabs/pdf-generator', '[\"pdf\"]', NULL, 'buyable', NULL, NULL),
('PdfProductsheets', 'PDF Productsheets', 0, 'User-friendly mechanism of generating PDF Productsheets.', 'treolabs/pdf-productsheets', '[\"usability\",\"preview\",\"design\",\"pdf\"]', NULL, 'buyable', NULL, NULL),
('Pim', 'PIM', 0, 'PIM module for Treo Core.', 'treolabs/pim', NULL, NULL, 'buyable', NULL, NULL),
('PimAssets', 'Assets', 0, 'User-friendly mechanism of assets managing for TreoPIM.', 'treolabs/pim-assets', '[\"manage\",\"usability\",\"files\",\"attachments\"]', NULL, 'buyable', NULL, NULL),
('Pricing', 'Pricing', 0, 'The module enables using advanced price mechanisms (i.e. prices in different currencies, scale prices, separate prices for different customer groups etc.)', 'treolabs/pricing', '[\"price\"]', NULL, 'buyable', NULL, NULL),
('ProductBundles', 'Product Bundles', 0, 'The module allows managing product bundles.', 'treolabs/product-bundles', '[\"bundle\"]', NULL, 'buyable', NULL, NULL),
('ProductPreview', 'Product Preview', 0, 'The module adds new interfaces for viewing and filling in the product.', 'treolabs/product-preview', '[\"design\",\"usability\"]', NULL, 'buyable', NULL, NULL),
('ProductVariants', 'Product Variants', 0, 'The module allows managing product variants with the help of user-friendly interfaces.', 'treolabs/product-variants', '[\"usability\",\"manage\",\"product variants\"]', NULL, 'buyable', NULL, NULL),
('Revisions', 'Revisions', 0, 'The module adds a user-friendly interface to overview the changes of any field values and enables their recovery.', 'treolabs/revisions', '[\"usability\",\"history\",\"data restore\"]', NULL, 'buyable', NULL, NULL),
('Sales', 'Sales', 0, 'Sales module for TreoCore', 'treolabs/sales', '[\"sales\"]', NULL, 'buyable', NULL, NULL);"
        );
    }
}
