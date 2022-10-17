<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http;

use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpClientContract;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Exception\HttpException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
final class HttpClient extends HttpClientContract implements LoggerAwareInterface
{
    private LoggerInterface $logger;

    public function __construct(ClientInterface $client, private UriFactoryInterface $uriFactory)
    {
        parent::__construct($client);
        $this->logger = new NullLogger();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        foreach ($this->getDefaultRequestHeaders()->getHeaders() as $header => $values) {
            if (!$request->hasHeader($header)) {
                $request = $request->withHeader($header, $values);
            }
        }

        $remainingRetries = $this->getMaxRetry();
        $remainingRedirect = $this->getMaxRedirect();

        do {
            $response = $this->getClient()->sendRequest($request);
            $now = new \DateTime();
            $code = $response->getStatusCode();

            if (
                $this->isRedirect($response)
                && \is_string($location = $this->getLocationHeader($response))
                && $remainingRedirect-- > 0
            ) {
                $previousUri = $request->getUri()->__toString();

                $request = $request->withUri(
                    $this->isAbsolute($location) ? $this->uriFactory->createUri($location) : $request->getUri()->withPath($location)
                );

                $this->logger->notice(\sprintf(
                    'HttpClient::sendRequest: Got HTTP code %s. Following redirect. Remaining redirects: %s. From URI: %s To URI: %s',
                    $code,
                    $remainingRedirect,
                    $previousUri,
                    $request->getUri()->__toString()
                ));

                continue;
            }

            if (
                (($maxWaitTimeout = $this->getMaxWaitTimeout()[$code] ?? 0) > 0)
                && \is_string($retryAfter = $this->getRetryAfterHeader($response))
                && \is_int($sleepInterval = $this->getSleepInterval($retryAfter, $now))
                && $sleepInterval <= $maxWaitTimeout
                && $remainingRetries-- > 0
            ) {
                $this->logger->notice(\sprintf(
                    'HttpClient::sendRequest: Got HTTP code %s. Waiting for Retry-After of %s. URI: %s.',
                    $code,
                    $retryAfter,
                    $request->getUri()->__toString()
                ));

                \sleep($sleepInterval);

                continue;
            }

            if ($this->isError($response) && $remainingRetries-- > 0) {
                $this->logger->notice(\sprintf(
                    'HttpClient::sendRequest: Got HTTP code %s. Retrying immediately. Remaining retries: %s. URI: %s',
                    $code,
                    $remainingRetries,
                    $request->getUri()->__toString()
                ));

                continue;
            }

            break;
        } while (true);

        if (\in_array($code, $this->getExceptionTriggers(), true)) {
            throw new HttpException($request, $response);
        }

        return $response;
    }

    private function getRetryAfterHeader(ResponseInterface $response): ?string
    {
        $retryAfterHeaders = $response->getHeader('Retry-After');
        $retryAfterHeader = \array_shift($retryAfterHeaders);

        return \is_string($retryAfterHeader) ? $retryAfterHeader : null;
    }

    /**
     * Retry-After header can contain either a date or an amount of seconds.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Retry-After
     */
    private function getSleepInterval(string $retryAfter, \DateTime $now): ?int
    {
        $retryAfterDate = \DateTime::createFromFormat('D, d M Y H:i:s e', $retryAfter);

        if ($retryAfterDate instanceof \DateTime) {
            return $retryAfterDate->getTimestamp() - $now->getTimestamp();
        } elseif (\is_numeric($retryAfter)) {
            return (int) $retryAfter;
        }

        return null;
    }

    private function isRedirect(ResponseInterface $response): bool
    {
        $statusCode = $response->getStatusCode();

        return $statusCode >= 300 && $statusCode < 400 && $response->hasHeader('Location');
    }

    private function isAbsolute(string $location): bool
    {
        return \is_string(\parse_url($location, \PHP_URL_HOST));
    }

    private function getLocationHeader(ResponseInterface $response): ?string
    {
        $locationHeaders = $response->getHeader('Location');
        $locationHeader = \array_shift($locationHeaders);

        return \is_string($locationHeader) ? $locationHeader : null;
    }

    private function isError(ResponseInterface $response): bool
    {
        $statusCode = $response->getStatusCode();

        return $statusCode >= 400 && $statusCode < 600;
    }
}
