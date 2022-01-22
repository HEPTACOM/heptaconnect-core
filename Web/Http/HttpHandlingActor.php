<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Web\Http\Contract\HttpHandlingActorInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandleContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandlerStackInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class HttpHandlingActor implements HttpHandlingActorInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function performHttpHandling(
        ServerRequestInterface $request,
        ResponseInterface $response,
        HttpHandlerStackInterface $stack,
        HttpHandleContextInterface $context
    ): ResponseInterface {
        try {
            return $stack->next($request, $response, $context);
        } catch (\Throwable $exception) {
            $this->logger->critical(LogMessage::WEB_HTTP_HANDLE_NO_THROW(), [
                'code' => 1636845126,
                'request' => $request,
                'stack' => $stack,
                'exception' => $exception,
            ]);
        }

        return $response->withStatus(500);
    }
}
