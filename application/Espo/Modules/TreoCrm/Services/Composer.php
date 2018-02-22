<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Services;

use Espo\Core\Services\Base;
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
     * Run require command
     *
     * @param string $repo
     * @param string $version
     *
     * @return array
     */
    public function runRequire(string $repo, string $version): array
    {
        return $this->run("require {$repo}:{$version}");
    }

    /**
     * Run composer command
     *
     * @param string $command
     *
     * @return array
     */
    protected function run(string $command): array
    {
        // prepare params
        $workingDir   = APPLICATION_PATH;
        $extractDir   = "{$workingDir}/vendor/composer/composer-extract";
        $composerPhar = "{$workingDir}/composer.phar";

        // extract composer
        if (!file_exists("{$extractDir}/vendor/autoload.php") == true) {
            if (!file_exists($composerPhar)) {
                return false;
            }
            $composerPhar = new \Phar($composerPhar);
            $composerPhar->extractTo($extractDir);
        }

        putenv("COMPOSER_HOME={$extractDir}");
        require_once "{$extractDir}/vendor/autoload.php";

        $input       = new StringInput("{$command} --working-dir={$workingDir}");
        $output      = new BufferedOutput();
        $application = new Application();
        $application->setAutoExit(false);
        $status      = $application->run($input, $output);

        return ['status' => ($status === 0), 'output' => $output->fetch()];
    }
}
