<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Support;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\StorageKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\PortalNodeKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Get\JobGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Get\PortalNodeGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Get\RouteGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\RouteGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\JobKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\RouteKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Base\JobKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\RouteKeyCollection;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\StorageKeyDataNotSupportedException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\StorageKeyNotSupportedException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Support\StorageKeyAccessorInterface;

final class StorageKeyAccessor implements StorageKeyAccessorInterface
{
    private StorageKeyGeneratorContract $storageKeyGenerator;

    private PortalNodeGetActionInterface $portalNodeGetAction;

    private RouteGetActionInterface $routeGetAction;

    private JobGetActionInterface $jobGetAction;

    public function __construct(
        StorageKeyGeneratorContract $storageKeyGenerator,
        PortalNodeGetActionInterface $portalNodeGetAction,
        RouteGetActionInterface $routeGetAction,
        JobGetActionInterface $jobGetAction
    ) {
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->portalNodeGetAction = $portalNodeGetAction;
        $this->routeGetAction = $routeGetAction;
        $this->jobGetAction = $jobGetAction;
    }

    public function deserialize(string $keyData): StorageKeyInterface
    {
        try {
            return $this->storageKeyGenerator->deserialize($keyData);
        } catch (\Throwable $exception) {
            throw new StorageKeyDataNotSupportedException($keyData, 1660417907, $exception);
        }
    }

    public function serialize(StorageKeyInterface $storageKey): string
    {
        try {
            return $this->storageKeyGenerator->serialize($storageKey);
        } catch (\Throwable $exception) {
            throw new StorageKeyNotSupportedException($storageKey, 1660417908, $exception);
        }
    }

    public function exists(StorageKeyInterface $storageKey): bool
    {
        try {
            if ($storageKey instanceof PortalNodeKeyInterface) {
                return $this->canGetPortalNode($storageKey);
            }

            if ($storageKey instanceof RouteKeyInterface) {
                return $this->canGetRoute($storageKey);
            }

            if ($storageKey instanceof JobKeyInterface) {
                return $this->canGetJob($storageKey);
            }
        } catch (\Throwable $exception) {
            throw new StorageKeyNotSupportedException($storageKey, 1660417909, $exception);
        }

        throw new StorageKeyNotSupportedException($storageKey, 1660417910);
    }

    /**
     * @throws UnsupportedStorageKeyException
     */
    private function canGetPortalNode(PortalNodeKeyInterface $portalNodeKey): bool
    {
        $criteria = new PortalNodeGetCriteria(new PortalNodeKeyCollection([$portalNodeKey]));

        foreach ($this->portalNodeGetAction->get($criteria) as $portalNode) {
            return $portalNode->getPortalNodeKey()->equals($portalNodeKey);
        }

        return false;
    }

    /**
     * @throws UnsupportedStorageKeyException
     */
    private function canGetRoute(RouteKeyInterface $routeKey): bool
    {
        $criteria = new RouteGetCriteria(new RouteKeyCollection([$routeKey]));

        foreach ($this->routeGetAction->get($criteria) as $route) {
            return $route->getRouteKey()->equals($routeKey);
        }

        return false;
    }

    /**
     * @throws UnsupportedStorageKeyException
     */
    private function canGetJob(JobKeyInterface $jobKey): bool
    {
        $criteria = new JobGetCriteria(new JobKeyCollection([$jobKey]));

        foreach ($this->jobGetAction->get($criteria) as $job) {
            return $job->getJobKey()->equals($jobKey);
        }

        return false;
    }
}
