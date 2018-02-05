<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Services;

/**
 * Interface of ProgressJobInterface
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
interface ProgressJobInterface
{

    /**
     * Execute progress job
     *
     * @param array $data
     *
     * @return bool
     */
    public function executeProgressJob(array $data): bool;

    /**
     * Get progress
     *
     * @return float
     */
    public function getProgress(): float;

    /**
     * Get offset
     *
     * @return int
     */
    public function getOffset(): int;

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus(): string;

    /**
     * Get data
     *
     * @return array
     */
    public function getData(): array;
}
