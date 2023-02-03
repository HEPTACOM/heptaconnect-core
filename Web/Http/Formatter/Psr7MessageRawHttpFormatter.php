<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http\Formatter;

use Heptacom\HeptaConnect\Core\Web\Http\Formatter\Support\Contract\HeaderUtilityInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\Psr7MessageRawHttpFormatterContract;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class Psr7MessageRawHttpFormatter extends Psr7MessageRawHttpFormatterContract
{
    private HeaderUtilityInterface $headerUtility;

    public function __construct(HeaderUtilityInterface $headerUtility)
    {
        $this->headerUtility = $headerUtility;
    }

    public function formatMessage(MessageInterface $message): string
    {
        if ($message instanceof RequestInterface) {
            return $this->formatRequest($message);
        }

        if ($message instanceof ResponseInterface) {
            return $this->formatResponse($message);
        }

        throw new \InvalidArgumentException('Message must be a request or a response', 1674950000);
    }

    public function getFileExtension(MessageInterface $message): string
    {
        if ($message instanceof RequestInterface) {
            return 'request.http';
        }

        if ($message instanceof ResponseInterface) {
            return 'response.bin';
        }

        throw new \InvalidArgumentException('Message must be a request or a response', 1674950001);
    }

    private function formatRequest(RequestInterface $request): string
    {
        $uri = $request->getUri();
        $request = $request->withHeader('host', $uri->getHost());
        $uri = $uri->withHost('')
            ->withPort(null)
            ->withUserInfo('')
            ->withFragment('')
            ->withScheme('');

        if (\in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS', 'TRACE'], true) && $request->hasHeader('content-length')) {
            $request = $request->withoutHeader('content-length');
        }

        $request = $this->headerUtility->sortRequestHeaders($request);
        $intro = \sprintf('%s %s HTTP/%s', $request->getMethod(), $uri, $request->getProtocolVersion());

        return $intro . \PHP_EOL . $this->convertMessageToRaw($request);
    }

    private function formatResponse(ResponseInterface $response): string
    {
        $response = $this->headerUtility->sortResponseHeaders($response);
        $intro = \sprintf('HTTP/%s %s %s', $response->getProtocolVersion(), $response->getStatusCode(), $response->getReasonPhrase());

        return $intro . \PHP_EOL . $this->convertMessageToRaw($response);
    }

    private function convertMessageToRaw(MessageInterface $message): string
    {
        $raw = [];

        foreach ($message->getHeaders() as $header => $values) {
            if (\in_array(\strtolower($header), ['transfer-encoding'], true)) {
                continue;
            }

            $raw[] = $header . ': ' . \implode(', ', $values);
        }

        if ($message->getBody()->getSize() > 0) {
            $raw[] = '';
            $raw[] = (string) $message->getBody();
        }

        return \implode(\PHP_EOL, $raw);
    }
}
