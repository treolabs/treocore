<?php
/**
 * Pim
 * Free Extension
 * Copyright (c) TreoLabs GmbH
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Treo\Migrations;

use Treo\Core\Migration\AbstractMigration;
use Treo\Core\Utils\Util;

/**
 * Migration class for version 3.19.8
 *
 * @author r.ratsun@treolabs.com
 */
class V3Dot19Dot8 extends AbstractMigration
{
    /**
     * @inheritdoc
     */
    public function up(): void
    {
        // refresh all processes
        file_put_contents("data/process-kill.txt", '1');

        // delete api dir
        Util::removedir('application/Espo/Modules');

        // delete client dir
        foreach (scandir('client/modules') as $module) {
            if (!in_array($module, ['.', '..', 'treo-core'])) {
                Util::removedir('client/modules/' . $module);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function down(): void
    {
    }
}
