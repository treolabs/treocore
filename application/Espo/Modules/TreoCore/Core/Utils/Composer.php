<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2018 Zinit Solutions GmbH
 * Website: http://www.treopim.com
 *
 * TreoPIM as well as EspoCRM is free software: you can redistribute it and/or modify
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

namespace Espo\Modules\TreoCore\Core\Utils;

use Espo\Core\Utils\Json;
use Composer\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Composer util
 *
 * @author r.ratsun@zinitsolutions.com
 */
class Composer
{
    const EXTRACT_DIR = CORE_PATH . "/vendor/composer/composer-extract";
    const AUTH_PATH = self::EXTRACT_DIR . '/auth.json';
    const GITLAB = 'gitlab.zinit1.com';

    /**
     * Construct
     */
    public function __construct()
    {
        /**
         * Extract composer
         */
        if (!file_exists(self::EXTRACT_DIR . "/vendor/autoload.php") == true) {
            (new \Phar(CORE_PATH . "/composer.phar"))->extractTo(self::EXTRACT_DIR);
        }
    }

    /**
     * Get auth data
     *
     * @return array
     */
    public function getAuthData(): array
    {
        // prepare result
        $result = [
            'username' => '',
            'password' => ''
        ];

        if (file_exists(self::AUTH_PATH)) {
            $jsonData = Json::decode(file_get_contents(self::AUTH_PATH), true);
            if (!empty($data = $jsonData['http-basic'][self::GITLAB])) {
                $result = $data;
            }
        }

        return $result;
    }

    /**
     * Set composer user data
     *
     * @param string $username
     * @param string $password
     *
     * @return bool
     */
    public function setAuthData(string $username, string $password): bool
    {
        // get data
        $jsonData = [];
        if (file_exists(self::AUTH_PATH)) {
            $jsonData = Json::decode(file_get_contents(self::AUTH_PATH), true);
        }

        // set username & password
        $jsonData['http-basic'][self::GITLAB]['username'] = $username;
        $jsonData['http-basic'][self::GITLAB]['password'] = $password;

        // set to file
        $file = fopen(self::AUTH_PATH, "w");
        fwrite($file, Json::encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        fclose($file);

        return true;
    }

    /**
     * Run composer command
     *
     * @param string $command
     *
     * @return array
     */
    public function run(string $command): array
    {
        // set memory limit for composer actions
        ini_set('memory_limit', '2048M');

        putenv("COMPOSER_HOME=" . self::EXTRACT_DIR);
        require_once self::EXTRACT_DIR . "/vendor/autoload.php";

        $application = new Application();
        $application->setAutoExit(false);

        $input = new StringInput("{$command} --working-dir=" . CORE_PATH);
        $output = new BufferedOutput();

        // prepare response
        $status = $application->run($input, $output);
        $output = str_replace(
            'Espo\\Modules\\TreoCore\\Services\\Composer::updateTreoModules',
            '',
            $output->fetch()
        );

        return ['status' => $status, 'output' => $output];
    }

}
