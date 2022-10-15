<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http;

use Heptacom\HeptaConnect\Portal\Base\Builder\Component\HttpHandler;
use Heptacom\HeptaConnect\Portal\Base\FlowComponent\CodeOrigin;
use Heptacom\HeptaConnect\Portal\Base\FlowComponent\Exception\CodeOriginNotFound;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandlerCodeOriginFinderInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandlerContract;

final class HttpHandlerCodeOriginFinder implements HttpHandlerCodeOriginFinderInterface
{
    public function findOrigin(HttpHandlerContract $httpHandler): CodeOrigin
    {
        if ($httpHandler instanceof HttpHandler) {
            /** @var array<\Closure|null> $closures */
            $closures = [
                $httpHandler->getRunMethod(),
                $httpHandler->getOptionsMethod(),
                $httpHandler->getGetMethod(),
                $httpHandler->getPostMethod(),
                $httpHandler->getPutMethod(),
                $httpHandler->getPatchMethod(),
                $httpHandler->getDeleteMethod(),
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

            throw new CodeOriginNotFound($httpHandler, 1637607699, $lastReflectionException);
        }

        try {
            $reflection = new \ReflectionClass($httpHandler);
            $filepath = $reflection->getFileName();

            if (\is_string($filepath)) {
                return $this->createOrigin($reflection, $filepath);
            }
        } catch (\ReflectionException $e) {
            throw new CodeOriginNotFound($httpHandler, 1637607700, $e);
        }

        throw new CodeOriginNotFound($httpHandler, 1637607701);
    }

    /**
     * @param \ReflectionClass<HttpHandlerContract>|\ReflectionFunction $reflection
     */
    private function createOrigin(\Reflector $reflection, string $filepath): CodeOrigin
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
