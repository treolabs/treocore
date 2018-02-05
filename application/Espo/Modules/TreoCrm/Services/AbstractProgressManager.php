<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Services;

use Espo\Core\Services\Base;

/**
 * Class of AbstractProgressManager
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
abstract class AbstractProgressManager extends Base
{
    /**
     * @var array
     */
    public static $progressStatus = [
        'new'         => '1_new',
        'in_progress' => '2_in_progress',
        'error'       => '3_error',
        'success'     => '4_success'
    ];

    /**
     * @var float
     */
    protected $progress = 0;

    /**
     * @var int
     */
    protected $offset = 0;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var string
     */
    protected $status = "new";

    /**
     * Get offset
     *
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * Set offset
     *
     * @param int $val
     *
     * @return AbstractProgressManager
     */
    public function setOffset(int $val): AbstractProgressManager
    {
        $this->offset = $val;

        return $this;
    }

    /**
     * Get progress
     *
     * @return float
     */
    public function getProgress(): float
    {
        return $this->progress;
    }

    /**
     * Set progress
     *
     * @param float $val
     *
     * @return AbstractProgressManager
     */
    public function setProgress(float $val): AbstractProgressManager
    {
        $this->progress = $val;

        return $this;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Set data
     *
     * @param array $data
     *
     * @return AbstractProgressManager
     */
    public function setData(array $data): AbstractProgressManager
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @return AbstractProgressManager
     */
    public function setStatus(string $status): AbstractProgressManager
    {
        $this->status = $status;

        return $this;
    }
}
