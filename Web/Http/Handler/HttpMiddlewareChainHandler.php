<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http\Handler;

use Heptacom\HeptaConnect\Core\Support\HttpMiddlewareCollector;
use Heptacom\HeptaConnect\Core\Web\Http\HttpMiddlewareHandler;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandleContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandlerContract;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandlerStackInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

final class HttpMiddlewareChainHandler extends HttpHandlerContract
{
    private string $path;

    private bool $isStackEmpty;

    public function __construct(string $path, bool $isStackEmpty)
    {
        $this->path = $path;
        $this->isStackEmpty = $isStackEmpty;
    }

    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        HttpHandleContextInterface $context,
        HttpHandlerStackInterface $stack
    ): ResponseInterface {
        // TODO: Use PortalNodeContainerFacade
        /** @var MiddlewareInterface[] $middlewares */
        $middlewares = $context->getContainer()->get(HttpMiddlewareCollector::class);

        $executeHttpHandlerStack = \Closure::fromCallable(
            fn (ServerRequestInterface $request) => $this->handleNext(
                $request,
                $response,
                $context,
                $stack
            )
        );

        $request = $request->withAttribute(
            HttpHandleContextInterface::REQUEST_ATTRIBUTE_IS_STACK_EMPTY,
            $this->isStackEmpty
        );

        $middlewareHandler = new HttpMiddlewareHandler($executeHttpHandlerStack, ...$middlewares);

        return $middlewareHandler->handle($request);
    }

    protected function supports(): string
    {
        return $this->path;
    }
}
