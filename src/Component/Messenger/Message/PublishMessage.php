<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Component\Messenger\Message;

use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;

class PublishMessage
{
    private MappingInterface $mapping;

    public function __construct(MappingInterface $mapping)
    {
        $this->mapping = $mapping;
    }

    public function getMapping(): MappingInterface
    {
        return $this->mapping;
    }
}
