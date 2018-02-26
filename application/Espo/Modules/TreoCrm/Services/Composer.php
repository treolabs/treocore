<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Services;

use Espo\Core\Services\Base;
use Espo\Core\Utils\Json;
use Composer\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Composer service
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Composer extends Base
{
    /**
     * @var string
     */
    protected $extractDir = CORE_PATH."/vendor/composer/composer-extract";

    /**
     * Construct
     */
    public function __construct(...$args)
    {
        parent::__construct(...$args);

        /**
         * Extract composer
         */
        if (!file_exists($this->extractDir."/vendor/autoload.php") == true) {
            (new \Phar(CORE_PATH."/composer.phar"))->extractTo($extractDir);
        }
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
        putenv("COMPOSER_HOME=".$this->extractDir);
        require_once $this->extractDir."/vendor/autoload.php";

        $application = new Application();
        $application->setAutoExit(false);

        $input  = new StringInput("{$command} --working-dir=".CORE_PATH);
        $output = new BufferedOutput();

        $status = $application->run($input, $output);

        return ['status' => ($status === 0), 'output' => $output->fetch()];
    }

    /**
     * Get auth data
     *
     * @return array
     */
    public function getAuthData(): array
    {
        // prepare result
        $result = [];

        // prepare path
        $path = $this->extractDir.'/auth.json';
        if (file_exists($path)) {
            $result = Json::decode(file_get_contents($path), true);
        }

        return $result;
    }

    /**
     * Set composer user data
     *
     * @param array $authData
     *
     * @return bool
     */
    public function setAuthData(array $authData): bool
    {
        // prepare path
        $path = $this->extractDir.'/auth.json';

        // delete old
        if (file_exists($path)) {
            unlink($path);
        }

        // create file
        $fp = fopen($path, "w");
        fwrite($fp, Json::encode($authData));
        fclose($fp);

        return true;
    }
}
