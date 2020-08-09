<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Contract;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface as SymfonyDenormalizerInterface;

interface DenormalizerInterface extends SymfonyDenormalizerInterface
{
    public function getType(): string;
}
