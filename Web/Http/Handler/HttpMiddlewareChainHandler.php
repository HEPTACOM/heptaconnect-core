<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http\Handler;

use Heptacom\HeptaConnect\Core\Portal\PortalNodeContainerFacade;
use Heptacom\HeptaConnect\Core\Web\Http\HttpMiddlewareHandler;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandleContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandlerContract;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandlerStackInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class HttpMiddlewareChainHandler extends HttpHandlerContract
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        HttpHandleContextInterface $context,
        HttpHandlerStackInterface $stack
    ): ResponseInterface {
        $container = new PortalNodeContainerFacade($context->getContainer());
        $middlewares = $container->getHttpHandlerMiddlewareCollector();

        $executeHttpHandlerStack = \Closure::fromCallable(
            fn (ServerRequestInterface $request) => $this->handleNext(
                $request,
                $response,
                $context,
                $stack
            )
        );

        $middlewareHandler = new HttpMiddlewareHandler($executeHttpHandlerStack, ...$middlewares);

        return $middlewareHandler->handle($request);
    }

    protected function supports(): string
    {
        return $this->path;
    }
}
