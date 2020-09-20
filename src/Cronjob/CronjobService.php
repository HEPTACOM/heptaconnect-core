<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Cronjob;

use Cron\CronExpression;
use Heptacom\HeptaConnect\Portal\Base\Cronjob\Contract\CronjobInterface;
use Heptacom\HeptaConnect\Portal\Base\Cronjob\Contract\CronjobServiceInterface;
use Heptacom\HeptaConnect\Portal\Base\Cronjob\Exception\InvalidCronExpressionException;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\CronjobKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\CronjobRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;

class CronjobService implements CronjobServiceInterface
{
    private CronjobRepositoryContract $cronjobRepository;

    public function __construct(CronjobRepositoryContract $cronjobRepository)
    {
        $this->cronjobRepository = $cronjobRepository;
    }

    public function register(
        PortalNodeKeyInterface $portalNodeKey,
        string $cronjobHandler,
        string $cronExpression,
        ?array $payload = null
    ): CronjobInterface {
        if (!CronExpression::isValidExpression($cronExpression)) {
            throw new InvalidCronExpressionException($cronExpression);
        }

        try {
            $nextExecution = CronExpression::factory($cronExpression)->getNextRunDate();
        } catch (\Throwable $t) {
            throw new InvalidCronExpressionException($cronExpression, $t);
        }

        return $this->cronjobRepository->create($portalNodeKey, $cronExpression, $cronjobHandler, $nextExecution, $payload);
    }

    public function unregister(CronjobKeyInterface $cronjobKey): void
    {
        try {
            $this->cronjobRepository->delete($cronjobKey);
        } catch (NotFoundException $e) {
        }
    }
}
