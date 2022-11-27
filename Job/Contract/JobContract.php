<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job\Contract;

use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingComponentStructContract;

/**
 * Base class to identify job payloads, that have to be run.
 */
abstract class JobContract
{
    /**
     * Gets the mapping component the job is attached to.
     */
    abstract public function getMappingComponent(): MappingComponentStructContract;

    /**
     * Return a payload. Can be null, if jobs are payload-less runnable.
     */
    public function getPayload(): ?array
    {
        return null;
    }

    /**
     * Return the type of the job.
     */
    public function getType(): string
    {
        return static::class;
    }
}
