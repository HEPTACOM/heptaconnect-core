<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Bridge\File;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Psr\Http\Message\UriInterface;

interface FileContentsUrlProviderInterface
{
    /**
     * Generates a public URI to retrieve the provided normalized stream. The returned URI **MUST** comply with the
     * following rules:
     *
     * - The URI **SHOULD** point to an HTTP controller of the HEPTAconnect application.
     * - At the time this URI is returned, a `GET` request without any additional header lines **MUST** respond with the
     *   contents of the provided normalized stream and use the provided MIME type as `Content-Type` header.
     * - After this URI is returned, arbitrary conditions **MAY** invalidate it. These conditions **MAY** include (but
     *   are not limited to):
     *   - The URI **MAY** expire after a certain time has elapsed.
     *   - The URI **MAY** expire after it has been accessed a certain number of times.
     *   - The origin portal node of the file reference **MAY** be deleted.
     */
    public function resolve(
        PortalNodeKeyInterface $portalNodeKey,
        string $normalizedStream,
        string $mimeType
    ): UriInterface;
}
