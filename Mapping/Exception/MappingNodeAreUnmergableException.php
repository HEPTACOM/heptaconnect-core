<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Mapping\Exception;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Throwable;

class MappingNodeAreUnmergableException extends \RuntimeException
{
    private MappingNodeKeyInterface $from;

    private MappingNodeKeyInterface $into;

    public function __construct(MappingNodeKeyInterface $from, MappingNodeKeyInterface $into, ?Throwable $previous = null)
    {
        parent::__construct('The mapping nodes could not be merged as they overlap existances', 0, $previous);
        $this->from = $from;
        $this->into = $into;
    }

    public function getFrom(): MappingNodeKeyInterface
    {
        return $this->from;
    }

    public function getInto(): MappingNodeKeyInterface
    {
        return $this->into;
    }
}
