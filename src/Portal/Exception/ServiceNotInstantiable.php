<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\Exception;

use Psr\Container\ContainerExceptionInterface;
use Throwable;

class ServiceNotInstantiable extends \Exception implements ContainerExceptionInterface
{
    private string $id;

    public function __construct(string $id, Throwable $previous = null)
    {
        parent::__construct(\sprintf('Service by id %s could not be instantiated', $id), 0, $previous);
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
