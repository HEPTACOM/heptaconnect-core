<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Portal\Exception\ServiceNotFoundException;
use Heptacom\HeptaConnect\Core\Portal\Exception\ServiceNotInstantiable;
use Heptacom\HeptaConnect\Core\Portal\Exception\ServiceNotInstantiableEndlessLoopDetected;
use Psr\Container\ContainerInterface;

class PortalStackServiceContainer implements ContainerInterface
{
    private array $services;

    public function __construct(array $services)
    {
        $this->services = $services;
    }

    public function get($id)
    {
        if (!\is_string($id)) {
            throw new ServiceNotFoundException('');
        }

        if (!\array_key_exists($id, $this->services)) {
            throw new ServiceNotFoundException($id);
        }

        $service = $this->services[$id];

        try {
            if (\is_callable($service)) {
                $loopBreaker = 100;

                while (\is_callable($service)) {
                    if ($loopBreaker-- <= 0) {
                        throw new ServiceNotInstantiableEndlessLoopDetected();
                    }

                    $service = $service($this);
                }

                $this->services[$id] = $service;
            }
        } catch (\Throwable $throwable) {
            // TODO: log error
            throw new ServiceNotInstantiable($id, $throwable);
        }

        return $service;
    }

    public function has($id)
    {
        return \is_string($id) && \array_key_exists($id, $this->services);
    }
}
