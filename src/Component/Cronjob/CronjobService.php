<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Component\Cronjob;

use Cron\CronExpression;
use Heptacom\HeptaConnect\Portal\Base\Cronjob\Contract\CronjobInterface;
use Heptacom\HeptaConnect\Portal\Base\Cronjob\Contract\CronjobServiceInterface;
use Heptacom\HeptaConnect\Portal\Base\Cronjob\Exception\InvalidCronExpressionException;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\CronjobKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Heptacom\HeptaConnect\Storage\Base\Exception\StorageMethodNotImplemented;

class CronjobService implements CronjobServiceInterface
{
    private StorageInterface $storage;

    public function __construct(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    public function register(string $cronjobHandler, string $cronExpression, ?array $payload = null): CronjobInterface
    {
        if (!CronExpression::isValidExpression($cronExpression)) {
            throw new InvalidCronExpressionException($cronExpression);
        }

        try {
            $nextExecution = CronExpression::factory($cronExpression)->getNextRunDate();
        } catch (\Throwable $t) {
            throw new InvalidCronExpressionException($cronExpression, $t);
        }

        return $this->storage->createCronjob($cronExpression, $cronjobHandler, $nextExecution, $payload);
    }

    public function unregister(CronjobKeyInterface $cronjobKey): void
    {
        try {
            $this->storage->removeCronjob($cronjobKey);
        } catch (NotFoundException $e) {
        } catch (StorageMethodNotImplemented $e) {
        }
    }
}
