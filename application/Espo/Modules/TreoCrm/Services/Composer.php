<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Services;

use Espo\Core\Services\Base;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Util;
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
     * @var array
     */
    const SKIP = ['.', '..'];

    /**
     * @var string
     */
    const TREODIR = 'treo-crm';

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
            (new \Phar(CORE_PATH."/composer.phar"))->extractTo($this->extractDir);
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

        return ['status' => $status, 'output' => $output->fetch()];
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

    /**
     * Get treo modules
     *
     * @return array
     */
    public static function getTreoModules(): array
    {
        // prepare result
        $result = [];

        // prepare treo crm vendor dir path
        $path = "vendor/".self::TREODIR."/";

        if (file_exists($path) && is_dir($path)) {
            foreach (scandir($path) as $row) {
                if (!in_array($row, self::SKIP)) {
                    $modulePath = "{$path}/{$row}/application/Espo/Modules/";
                    if (file_exists($modulePath) && is_dir($modulePath)) {
                        foreach (scandir($modulePath) as $moduleId) {
                            if (!in_array($moduleId, self::SKIP)) {
                                $result[$moduleId] = $row;
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Update treo modules
     */
    public static function updateTreoModules(): void
    {
        foreach (self::getTreoModules() as $moduleId => $key) {
            // update frontend files
            self::updateFrontend($moduleId);

            // update backend files
            self::updateBackend($moduleId);
        }
    }

    /**
     * Delete treo module
     *
     * @param array $modules
     */
    public static function deleteTreoModule(array $modules): void
    {
        foreach ($modules as $moduleId => $key) {
            // delete dir from frontend
            self::deleteDir('client/modules/'.Util::fromCamelCase($moduleId, '-').'/');

            // delete dir from backend
            self::deleteDir("application/Espo/Modules/{$moduleId}/");
        }
    }

    /**
     * Update backend
     *
     * @param string $moduleId
     */
    protected static function updateBackend(string $moduleId): void
    {
        if (array_key_exists($moduleId, self::getTreoModules())) {
            // prepare params
            $moduleKey = self::getTreoModules()[$moduleId];
            $source    = "vendor/".self::TREODIR."/{$moduleKey}/application/Espo/Modules/{$moduleId}/";
            $dest      = "application/Espo/Modules/{$moduleId}/";

            // delete dir
            self::deleteDir($dest);

            // copy dir
            self::copyDir($source, $dest);
        }
    }

    /**
     * Update frontend
     *
     * @param string $moduleId
     */
    protected static function updateFrontend(string $moduleId): void
    {
        if (array_key_exists($moduleId, self::getTreoModules())) {
            // prepare params
            $moduleKey = self::getTreoModules()[$moduleId];
            $module    = Util::fromCamelCase($moduleId, '-');
            $source    = "vendor/".self::TREODIR."/{$moduleKey}/client/modules/{$module}/";
            $dest      = "client/modules/{$module}/";

            // delete dir
            self::deleteDir($dest);

            // copy dir
            self::copyDir($source, $dest);
        }
    }

    /**
     * Recursively copy files from one directory to another
     *
     * @param string $src
     * @param string $dest
     *
     * @return bool
     */
    protected static function copyDir(string $src, string $dest): bool
    {
        if (!is_dir($src)) {
            return false;
        }

        if (!is_dir($dest)) {
            if (!mkdir($dest)) {
                return false;
            }
        }

        $i = new \DirectoryIterator($src);
        foreach ($i as $f) {
            if ($f->isFile()) {
                copy($f->getRealPath(), "$dest/".$f->getFilename());
            } elseif (!$f->isDot() && $f->isDir()) {
                self::copyDir($f->getRealPath(), "$dest/$f");
            }
        }

        return true;
    }

    /**
     * Recursively move files from one directory to another
     *
     * @param string $src
     * @param string $dest
     *
     * @return bool
     */
    protected static function moveDir(string $src, string $dest): bool
    {
        if (!is_dir($src)) {
            return false;
        }

        if (!is_dir($dest)) {
            if (!mkdir($dest)) {
                return false;
            }
        }

        $i = new \DirectoryIterator($src);
        foreach ($i as $f) {
            if ($f->isFile()) {
                rename($f->getRealPath(), "$dest/".$f->getFilename());
            } elseif (!$f->isDot() && $f->isDir()) {
                self::moveDir($f->getRealPath(), "$dest/$f");
                unlink($f->getRealPath());
            }
        }
        unlink($src);

        return true;
    }

    /**
     * Delete directory
     *
     * @param string $dirname
     *
     * @return bool
     */
    protected static function deleteDir(string $dirname): bool
    {
        if (!file_exists($dirname)) {
            return false;
        }

        if (is_dir($dirname)) {
            $dir_handle = opendir($dirname);
        }

        if (!$dir_handle) {
            return false;
        }

        while ($file = readdir($dir_handle)) {
            if ($file != "." && $file != "..") {
                if (!is_dir($dirname."/".$file)) {
                    unlink($dirname."/".$file);
                } else {
                    self::deleteDir($dirname.'/'.$file);
                }
            }
        }
        closedir($dir_handle);
        rmdir($dirname);

        return true;
    }
}
