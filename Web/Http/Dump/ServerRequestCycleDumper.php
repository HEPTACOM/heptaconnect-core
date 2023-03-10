<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http\Dump;

use Heptacom\HeptaConnect\Core\Bridge\File\HttpHandlerDumpPathProviderInterface;
use Heptacom\HeptaConnect\Core\Web\Http\Contract\HttpHandleServiceInterface;
use Heptacom\HeptaConnect\Core\Web\Http\Dump\Contract\ServerRequestCycleDumperInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\Psr7MessageFormatterContract;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\HttpHandlerStackIdentifier;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\ServerRequestCycle;
use Psr\Http\Message\ServerRequestInterface;

final class ServerRequestCycleDumper implements ServerRequestCycleDumperInterface
{
    public function __construct(
        private HttpHandlerDumpPathProviderInterface $pathProvider,
        private Psr7MessageFormatterContract $formatter
    ) {
    }

    public function dump(HttpHandlerStackIdentifier $httpHandler, ServerRequestCycle $requestCycle): void
    {
        $request = $requestCycle->getRequest();
        $response = $requestCycle->getResponse();

        $originalRequest = $request->getAttribute(HttpHandleServiceInterface::REQUEST_ATTRIBUTE_ORIGINAL_REQUEST);

        if ($originalRequest instanceof ServerRequestInterface) {
            $request = $originalRequest;
        }

        $correlationId = $response->getHeaderLine('X-HeptaConnect-Correlation-Id');
        $dumpDir = $this->pathProvider->provide($httpHandler->getPortalNodeKey()) . $correlationId;
        $message = $this->formatter->formatMessage($request);
        $extension = '.request.' . $this->formatter->getFileExtension($request);

        \file_put_contents($dumpDir . $extension, $message);

        $message = $this->formatter->formatMessage($response);
        $extension = '.response.' . $this->formatter->getFileExtension($response);

        \file_put_contents($dumpDir . $extension, $message);
    }
}
