<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Portal\Base\Builder\Component\Explorer;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerCodeOriginFinderInterface;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerContract;
use Heptacom\HeptaConnect\Portal\Base\FlowComponent\CodeOrigin;
use Heptacom\HeptaConnect\Portal\Base\FlowComponent\Exception\CodeOriginNotFound;

final class ExplorerCodeOriginFinder implements ExplorerCodeOriginFinderInterface
{
    public function findOrigin(ExplorerContract $explorer): CodeOrigin
    {
        if ($explorer instanceof Explorer) {
            /** @var array<\Closure|null> $closures */
            $closures = [
                $explorer->getRunMethod(),
                $explorer->getIsAllowedMethod(),
            ];

            $lastReflectionException = null;

            foreach ($closures as $closure) {
                if ($closure instanceof \Closure) {
                    try {
                        $reflection = new \ReflectionFunction($closure);
                        $filepath = $reflection->getFileName();

                        if (\is_string($filepath)) {
                            return $this->createOrigin($reflection, $filepath);
                        }
                    } catch (\ReflectionException $e) {
                        $lastReflectionException = $e;
                    }
                }
            }

            throw new CodeOriginNotFound($explorer, 1637421327, $lastReflectionException);
        }

        try {
            $reflection = new \ReflectionClass($explorer);
            $filepath = $reflection->getFileName();

            if (\is_string($filepath)) {
                return $this->createOrigin($reflection, $filepath);
            }
        } catch (\ReflectionException $e) {
            throw new CodeOriginNotFound($explorer, 1637421328, $e);
        }

        throw new CodeOriginNotFound($explorer, 1637421329);
    }

    /**
     * @param \ReflectionClass<ExplorerContract>|\ReflectionFunction $reflection
     */
    private function createOrigin(\ReflectionClass|\ReflectionFunction $reflection, string $filepath): CodeOrigin
    {
        $startLine = $reflection->getStartLine();
        $endLine = $reflection->getEndLine();

        return new CodeOrigin(
            $filepath,
            $startLine !== false ? $startLine : -1,
            $endLine !== false ? $endLine : -1
        );
    }
}
