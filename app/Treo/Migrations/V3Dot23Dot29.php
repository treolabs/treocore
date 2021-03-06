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
 * Migration class for version 3.23.29
 *
 * @author r.ratsun@treolabs.com
 */
class V3Dot23Dot29 extends Base
{
    /**
     * @inheritDoc
     */
    public function up(): void
    {
        // copy files
        if (file_exists('vendor/treolabs/treocore/copy/bin/cron.sh')) {
            copy('vendor/treolabs/treocore/copy/bin/cron.sh', 'bin/cron.sh');
        }

        if (file_exists('vendor/treolabs/treocore/copy/bin/treo-composer.sh')) {
            copy('vendor/treolabs/treocore/copy/bin/treo-composer.sh', 'bin/treo-composer.sh');
        }

        // kill processes
        file_put_contents('data/process-kill.txt', '1');
    }

    /**
     * @inheritDoc
     */
    public function down(): void
    {
    }
}
