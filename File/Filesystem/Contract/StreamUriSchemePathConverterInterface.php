<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\File\Filesystem\Contract;

use Heptacom\HeptaConnect\Portal\Base\File\Filesystem\Exception\UnexpectedFormatOfUriException;

/**
 * Converts between URIs and paths based on a certain prefix.
 */
interface StreamUriSchemePathConverterInterface
{
    /**
     * Converts a URI into a path by removing some sort of prefix.
     * This result can be converted back by using @see convertToUri
     *
     * @throws UnexpectedFormatOfUriException
     */
    public function convertToPath(string $uri): string;

    /**
     * Converts a path into a URI by adding some sort of prefix.
     * This result can be converted back by using @see convertToPath
     *
     * @throws UnexpectedFormatOfUriException
     */
    public function convertToUri(string $path): string;
}
