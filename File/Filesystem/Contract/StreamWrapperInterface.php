<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\File\Filesystem\Contract;

/**
 * @property resource $context
 *
 * @see https://www.php.net/manual/en/class.streamwrapper.php
 */
interface StreamWrapperInterface
{
    /**
     * Constructs a new stream wrapper
     */
    public function __construct();

    /**
     * Destructs an existing stream wrapper
     */
    public function __destruct();

    /**
     * Close directory handle
     */
    public function dir_closedir(): bool;

    /**
     * Open directory handle
     */
    public function dir_opendir(string $path, int $options): bool;

    /**
     * Read entry from directory handle
     *
     * @returns string|false
     */
    public function dir_readdir();

    /**
     * Rewind directory handle
     */
    public function dir_rewinddir(): bool;

    /**
     * Create a directory
     */
    public function mkdir(string $path, int $mode, int $options): bool;

    /**
     * Renames a file or directory
     */
    public function rename(string $path_from, string $path_to): bool;

    /**
     * Removes a directory
     */
    public function rmdir(string $path, int $options): bool;

    /**
     * Retrieve the underlaying resource
     *
     * @return resource
     */
    public function stream_cast(int $cast_as);

    /**
     * Close a resource
     */
    public function stream_close(): void;

    /**
     * Tests for end-of-file on a file pointer
     */
    public function stream_eof(): bool;

    /**
     * Flushes the output
     */
    public function stream_flush(): bool;

    /**
     * Advisory file locking
     */
    public function stream_lock(int $operation): bool;

    /**
     * Change stream metadata
     */
    public function stream_metadata(string $path, int $option, $value): bool;

    /**
     * Opens file or URL
     */
    public function stream_open(
        string $path,
        string $mode,
        int $options,
        ?string &$opened_path
    ): bool;

    /**
     * Read from stream
     *
     * @return string|false
     */
    public function stream_read(int $count);

    /**
     * Seeks to specific location in a stream
     */
    public function stream_seek(int $offset, int $whence = \SEEK_SET): bool;

    /**
     * Change stream options
     */
    public function stream_set_option(int $option, int $arg1, int $arg2): bool;

    /**
     * Retrieve information about a file resource
     *
     * @return array|false
     */
    public function stream_stat();

    /**
     * Retrieve the current position of a stream
     */
    public function stream_tell(): int;

    /**
     * Truncate stream
     */
    public function stream_truncate(int $new_size): bool;

    /**
     * Write to stream
     */
    public function stream_write(string $data): int;

    /**
     * Delete a file
     */
    public function unlink(string $path): bool;

    /**
     * Retrieve information about a file
     *
     * @return array|false
     */
    public function url_stat(string $path, int $flags);
}
