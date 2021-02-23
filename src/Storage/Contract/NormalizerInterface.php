<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Contract;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface as SymfonyNormalizerInterface;

interface NormalizerInterface extends SymfonyNormalizerInterface
{
    public function getType(): string;
}
