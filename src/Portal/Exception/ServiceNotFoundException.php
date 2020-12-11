<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\Exception;

use Psr\Container\NotFoundExceptionInterface;
use Throwable;

class ServiceNotFoundException extends \Exception implements NotFoundExceptionInterface
{
    private string $id;

    public function __construct(string $id, Throwable $previous = null)
    {
        parent::__construct(\sprintf('Service by id %s not found', $id), 0, $previous);
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
