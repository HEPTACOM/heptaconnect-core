<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http\Formatter;

use Heptacom\HeptaConnect\Core\Web\Http\Formatter\Support\Contract\HeaderUtilityInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\Psr7MessageCurlShellFormatterContract;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class Psr7MessageCurlShellFormatter extends Psr7MessageCurlShellFormatterContract
{
    private HeaderUtilityInterface $headerUtility;

    private Psr7MessageRawHttpFormatter $rawFormatter;

    private string $curlCommand;

    public function __construct(
        HeaderUtilityInterface $headerUtility,
        Psr7MessageRawHttpFormatter $rawFormatter,
        string $curlCommand
    ) {
        $this->headerUtility = $headerUtility;
        $this->rawFormatter = $rawFormatter;
        $this->curlCommand = $curlCommand;
    }

    public function formatMessage(MessageInterface $message): string
    {
        if ($message instanceof RequestInterface) {
            return $this->formatRequest($message);
        }

        if ($message instanceof ResponseInterface) {
            return $this->formatResponse($message);
        }

        throw new \InvalidArgumentException('Message must be a request or a response', 1674950002);
    }

    public function getFileExtension(MessageInterface $message): string
    {
        if ($message instanceof RequestInterface) {
            return 'sh';
        }

        if ($message instanceof ResponseInterface) {
            return $this->rawFormatter->getFileExtension($message);
        }

        throw new \InvalidArgumentException('Message must be a request or a response', 1674950003);
    }

    private function formatRequest(RequestInterface $request): string
    {
        $request = $this->headerUtility->sortRequestHeaders($request);

        if (\in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS', 'TRACE'], true)) {
            $request = $request->withoutHeader('content-length');
        }

        return $this->curlCommand . ' ' . \implode(' \\' . \PHP_EOL . '  ', $this->getCurlCommandParts($request));
    }

    private function formatResponse(ResponseInterface $response): string
    {
        return $this->rawFormatter->formatMessage($response);
    }

    private function getCurlCommandParts(RequestInterface $request): array
    {
        $commandParts = [];
        $commandParts[] = (string) $request->getUri();
        $commandParts[] = '-X ' . $request->getMethod();

        switch ($request->getProtocolVersion()) {
            case '0.9':
                $commandParts[] = '--http0.9';

                break;
            case '1.0':
                $commandParts[] = '--http1.0';

                break;
            case '1.1':
                $commandParts[] = '--http1.1';

                break;
            case '2.0':
                $commandParts[] = '--http2';

                break;
            case '3.0':
                $commandParts[] = '--http3';

                break;
        }

        foreach ($request->getHeaders() as $header => $values) {
            if (\in_array(\strtolower($header), ['content-length', 'transfer-encoding'], true)) {
                continue;
            }

            $commandParts[] = '-H "' . $header . ': ' . \addcslashes(\implode(', ', $values), '\\"') . '"';
        }

        if ($request->getBody()->getSize() > 0) {
            $body = (string) $request->getBody();
            $encoded = $this->encodeBinaryForCli($body);
            $commandParts[] = "-d $'" . \addcslashes($encoded, "\\'") . "'";
        }

        $commandParts[] = '-i';
        $commandParts[] = '-L';
        $commandParts[] = '-w "HTTP/%{http_version} %{response_code}"';
        $commandParts[] = '--output -';

        return $commandParts;
    }

    private function encodeBinaryForCli(string $stream): string
    {
        $printables = [
            ' ',
            '"',
            ...\range('a', 'z'),
            ...\range('A', 'Z'),
            ...\range('0', '9'),
        ];

        return \implode('', \array_map(
            static function (string $byte) use ($printables): string {
                if (\in_array($byte, $printables, true)) {
                    return $byte;
                }

                return '\\x' . \bin2hex($byte);
            },
            \str_split($stream)
        ));
    }
}
