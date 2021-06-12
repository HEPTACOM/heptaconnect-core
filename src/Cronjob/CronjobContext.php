<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Cronjob;

use Heptacom\HeptaConnect\Core\Portal\AbstractPortalNodeContext;
use Heptacom\HeptaConnect\Portal\Base\Cronjob\Contract\CronjobContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Cronjob\Contract\CronjobInterface;
use Psr\Container\ContainerInterface;

class CronjobContext extends AbstractPortalNodeContext implements CronjobContextInterface
{
    private CronjobInterface $cronjob;

    public function __construct(ContainerInterface $container, ?array $configuration, CronjobInterface $cronjob)
    {
        parent::__construct($container, $configuration);
        $this->cronjob = $cronjob;
    }

    public function getCronjob(): CronjobInterface
    {
        return $this->cronjob;
    }
}
