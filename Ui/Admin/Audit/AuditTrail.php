<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Audit;

use Heptacom\HeptaConnect\Core\Ui\Admin\Audit\Contract\AuditTrailInterface;

final class AuditTrail implements AuditTrailInterface
{
    private bool $hasEnded = false;

    /**
     * @var \Closure(object): void
     */
    private \Closure $logResult;

    /**
     * @var \Closure(\Throwable): void
     */
    private \Closure $logThrowable;

    /**
     * @var \Closure(): void
     */
    private \Closure $logEnd;

    /**
     * @param \Closure(object     $output):    void $logResult
     * @param \Closure(\Throwable $throwable): void $logThrowable
     * @param \Closure(): void    $logEnd
     */
    public function __construct(\Closure $logResult, \Closure $logThrowable, \Closure $logEnd)
    {
        $this->logResult = $logResult;
        $this->logThrowable = $logThrowable;
        $this->logEnd = $logEnd;
    }

    public function __destruct()
    {
        if (!$this->hasEnded) {
            $this->end();
        }
    }

    public function return(object $result): object
    {
        ($this->logResult)($result);
        $this->end();

        return $result;
    }

    public function yield(object $result): object
    {
        ($this->logResult)($result);

        return $result;
    }

    public function returnIterable(iterable $result): iterable
    {
        $log = $this->logResult;

        try {
            foreach ($result as $key => $value) {
                $log($value);

                yield $key => $value;
            }

            $this->end();
        } catch (\Throwable $throwable) {
            throw $this->throwable($throwable);
        }
    }

    public function throwable(\Throwable $throwable): \Throwable
    {
        ($this->logThrowable)($throwable);

        $this->end();

        return $throwable;
    }

    public function end(): void
    {
        ($this->logEnd)();

        $this->hasEnded = true;
    }
}
