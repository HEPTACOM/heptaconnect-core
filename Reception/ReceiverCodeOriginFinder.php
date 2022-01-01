<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception;

use Closure;
use Heptacom\HeptaConnect\Portal\Base\Builder\Component\Receiver;
use Heptacom\HeptaConnect\Portal\Base\FlowComponent\CodeOrigin;
use Heptacom\HeptaConnect\Portal\Base\FlowComponent\Exception\CodeOriginNotFound;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverCodeOriginFinderInterface;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverContract;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;

class ReceiverCodeOriginFinder implements ReceiverCodeOriginFinderInterface
{
    public function findOrigin(ReceiverContract $receiver): CodeOrigin
    {
        if ($receiver instanceof Receiver) {
            /** @var array<Closure|null> $closures */
            $closures = [
                $receiver->getRunMethod(),
                $receiver->getBatchMethod(),
            ];

            $lastReflectionException = null;

            foreach ($closures as $closure) {
                if ($closure instanceof Closure) {
                    try {
                        $reflection = new ReflectionFunction($closure);
                        $filepath = $reflection->getFileName();

                        if (\is_string($filepath)) {
                            return new CodeOrigin($filepath, $reflection->getStartLine(), $reflection->getEndLine());
                        }
                    } catch (ReflectionException $e) {
                        $lastReflectionException = $e;
                    }
                }
            }

            throw new CodeOriginNotFound($receiver, 1641079368, $lastReflectionException);
        }

        try {
            $reflection = new ReflectionClass($receiver);
            $filepath = $reflection->getFileName();

            if (\is_string($filepath)) {
                return new CodeOrigin($filepath, $reflection->getStartLine(), $reflection->getEndLine());
            }
        } catch (ReflectionException $e) {
            throw new CodeOriginNotFound($receiver, 1641079369, $e);
        }

        throw new CodeOriginNotFound($receiver, 1641079370);
    }
}
