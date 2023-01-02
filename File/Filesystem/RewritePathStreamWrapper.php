<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\File\Filesystem;

use Heptacom\HeptaConnect\Core\File\Filesystem\Contract\StreamWrapperInterface;

/**
 * Expects configuration for the registered stream wrapper protocol to be given for rewriting paths to work on to new protocols.
 *
 * Configuration:
 * [
 *     "protocol": [
 *         "set": "new-protocol",
 *         "append": "protocol suffix",
 *         "prepend": "protocol prefix"
 *     ],
 *     "path": [
 *         "set": "path replacement",
 *         "append": "path suffix",
 *         "prepend": "path prefix",
 *         "prepend_safe_separator": true // if "prepend" is handled as directory and shall be merged with the path using a directory separator
 *     ]
 * ]
 *
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class RewritePathStreamWrapper implements StreamWrapperInterface
{
    /**
     * @var resource|null
     */
    public $context = null;

    /**
     * @var resource|null
     */
    private $file = null;

    /**
     * @var resource|null
     */
    private $directory = null;

    private ?string $lastProtocol = null;

    public function __construct()
    {
    }

    public function __destruct()
    {
        if (\is_resource($this->file)) {
            \fclose($this->file);

            $this->file = null;
        }

        $this->context = null;
    }

    // BEGIN FILE STREAM OPERATIONS

    public function stream_cast(int $cast_as)
    {
        $result = $this->file;

        if (\is_resource($result)) {
            return $result;
        }

        return false;
    }

    public function stream_close(): void
    {
        try {
            if (\is_resource($this->file)) {
                \fclose($this->file);
            }
        } catch (\Throwable $throwable) {
            \trigger_error($throwable->getMessage(), \E_USER_WARNING);
        }
    }

    public function stream_eof(): bool
    {
        try {
            return \is_resource($this->file) && \feof($this->file);
        } catch (\Throwable $throwable) {
            \trigger_error($throwable->getMessage(), \E_USER_WARNING);

            return false;
        }
    }

    public function stream_flush(): bool
    {
        try {
            return \is_resource($this->file) && \fflush($this->file);
        } catch (\Throwable $throwable) {
            \trigger_error($throwable->getMessage(), \E_USER_WARNING);

            return false;
        }
    }

    public function stream_lock(int $operation): bool
    {
        try {
            return \is_resource($this->file) && \flock($this->file, $operation);
        } catch (\Throwable $throwable) {
            \trigger_error($throwable->getMessage(), \E_USER_WARNING);
        }

        return false;
    }

    public function stream_read(int $count)
    {
        try {
            if (\is_resource($this->file)) {
                return \fread($this->file, $count);
            }
        } catch (\Throwable $throwable) {
            \trigger_error($throwable->getMessage(), \E_USER_WARNING);
        }

        return false;
    }

    public function stream_seek(int $offset, int $whence = \SEEK_SET): bool
    {
        try {
            return \is_resource($this->file) && (\fseek($this->file, $offset, $whence) === 0);
        } catch (\Throwable $throwable) {
            \trigger_error($throwable->getMessage(), \E_USER_WARNING);

            return false;
        }
    }

    public function stream_set_option(int $option, int $arg1, int $arg2): bool
    {
        return false;
    }

    public function stream_stat()
    {
        try {
            if (\is_resource($this->file)) {
                return \fstat($this->file);
            }
        } catch (\Throwable $throwable) {
            \trigger_error($throwable->getMessage(), \E_USER_WARNING);
        }

        return false;
    }

    public function stream_tell(): int
    {
        try {
            if (\is_resource($this->file)) {
                $result = \ftell($this->file);

                if ($result !== false) {
                    return $result;
                }
            }
        } catch (\Throwable $throwable) {
            \trigger_error($throwable->getMessage(), \E_USER_WARNING);
        }

        return 0;
    }

    public function stream_truncate(int $new_size): bool
    {
        try {
            return \is_resource($this->file) && \ftruncate($this->file, $new_size);
        } catch (\Throwable $throwable) {
            \trigger_error($throwable->getMessage(), \E_USER_WARNING);

            return false;
        }
    }

    public function stream_write(string $data): int
    {
        try {
            if (\is_resource($this->file)) {
                $result = \fwrite($this->file, $data);

                if ($result !== false) {
                    return $result;
                }
            }
        } catch (\Throwable $throwable) {
            \trigger_error($throwable->getMessage(), \E_USER_WARNING);
        }

        return 0;
    }

    // END FILE STREAM OPERATIONS

    // START DIRECTORY RESOURCE OPERATIONS

    public function dir_closedir(): bool
    {
        try {
            if (\is_resource($this->directory)) {
                \closedir($this->directory);
                $this->directory = null;

                return true;
            }
        } catch (\Throwable $throwable) {
            \trigger_error($throwable->getMessage(), \E_USER_WARNING);
        }

        return false;
    }

    public function dir_readdir(): mixed
    {
        try {
            if (\is_resource($this->directory)) {
                $result = \readdir($this->directory);

                if (\is_string($result)) {
                    return $this->fromNewPath($result);
                }

                return $result;
            }
        } catch (\Throwable $throwable) {
            \trigger_error($throwable->getMessage(), \E_USER_WARNING);
        }

        return false;
    }

    public function dir_rewinddir(): bool
    {
        try {
            if (\is_resource($this->directory)) {
                \rewinddir($this->directory);

                return true;
            }
        } catch (\Throwable $throwable) {
            \trigger_error($throwable->getMessage(), \E_USER_WARNING);
        }

        return false;
    }

    // END DIRECTORY RESOURCE OPERATIONS

    // START NODE OPERATIONS

    public function stream_metadata(string $path, int $option, mixed $value): bool
    {
        try {
            $path = $this->toNewPath($path);

            switch ($option) {
                case \STREAM_META_TOUCH:
                    if (\is_array($value) && \array_key_exists(0, $value) && \is_numeric($value[0])) {
                        $mtime = (int) $value[0];
                    } else {
                        $mtime = \time();
                    }

                    if (\is_array($value) && \array_key_exists(1, $value) && \is_numeric($value[1])) {
                        $atime = (int) $value[1];
                    } else {
                        $atime = \time();
                    }

                    return \touch($path, $mtime, $atime);
                case \STREAM_META_OWNER_NAME:
                    if (!\is_string($value)) {
                        throw new \InvalidArgumentException('Parameter is expected to be string');
                    }

                    return \chown($path, $value);
                case \STREAM_META_OWNER:
                    if (!\is_int($value)) {
                        throw new \InvalidArgumentException('Parameter is expected to be int');
                    }

                    return \chown($path, $value);
                case \STREAM_META_GROUP_NAME:
                    if (!\is_string($value)) {
                        throw new \InvalidArgumentException('Parameter is expected to be string');
                    }

                    return \chgrp($path, $value);
                case \STREAM_META_GROUP:
                    if (!\is_int($value)) {
                        throw new \InvalidArgumentException('Parameter is expected to be int');
                    }

                    return \chgrp($path, $value);
                case \STREAM_META_ACCESS:
                    if (!\is_int($value)) {
                        throw new \InvalidArgumentException('Parameter is expected to be int');
                    }

                    return \chmod($path, $value);
            }
        } catch (\Throwable $throwable) {
            \trigger_error($throwable->getMessage(), \E_USER_WARNING);
        }

        return false;
    }

    public function stream_open(
        string $path,
        string $mode,
        int $options,
        ?string &$opened_path
    ): bool {
        try {
            $file = \fopen($this->toNewPath($path), $mode);

            if ($file === false) {
                return false;
            }

            $this->file = $file;

            return true;
        } catch (\Throwable $throwable) {
            \trigger_error($throwable->getMessage(), \E_USER_WARNING);
        }

        return false;
    }

    public function dir_opendir(string $path, int $options): bool
    {
        try {
            $directory = \opendir($this->toNewPath($path));

            if (\is_resource($directory)) {
                $this->directory = $directory;

                return true;
            }
        } catch (\Throwable $throwable) {
            \trigger_error($throwable->getMessage(), \E_USER_WARNING);
        }

        return false;
    }

    public function mkdir(string $path, int $mode, int $options): bool
    {
        try {
            $newPath = $this->toNewPath($path);
            $isRecursive = ($options & \STREAM_MKDIR_RECURSIVE) === \STREAM_MKDIR_RECURSIVE;

            if (!@\mkdir($newPath, $mode, $isRecursive) && !\is_dir($newPath)) {
                throw new \RuntimeException('Unable to create directory: ' . $path);
            }

            return true;
        } catch (\Throwable $throwable) {
            \trigger_error($throwable->getMessage(), \E_USER_WARNING);
        }

        return false;
    }

    public function rename(string $path_from, string $path_to): bool
    {
        try {
            return \file_exists($this->toNewPath($path_from)) && \rename($this->toNewPath($path_from), $this->toNewPath($path_to));
        } catch (\Throwable $throwable) {
            \trigger_error($throwable->getMessage(), \E_USER_WARNING);
        }

        return false;
    }

    public function rmdir(string $path, int $options): bool
    {
        try {
            return \file_exists($path) && \rmdir($this->toNewPath($path));
        } catch (\Throwable $throwable) {
            \trigger_error($throwable->getMessage(), \E_USER_WARNING);
        }

        return false;
    }

    public function unlink(string $path): bool
    {
        try {
            return \file_exists($this->toNewPath($path)) && \unlink($this->toNewPath($path));
        } catch (\Throwable $throwable) {
            \trigger_error($throwable->getMessage(), \E_USER_WARNING);
        }

        return false;
    }

    public function url_stat(string $path, int $flags)
    {
        try {
            if (($flags & \STREAM_URL_STAT_LINK) === \STREAM_URL_STAT_LINK) {
                $linkedPath = \readlink($this->toNewPath($path));

                if ($linkedPath !== false) {
                    $path = $linkedPath;
                }
            }

            return @\stat($this->toNewPath($path));
        } catch (\Throwable $throwable) {
            if (($flags & \STREAM_URL_STAT_QUIET) !== \STREAM_URL_STAT_QUIET) {
                \trigger_error($throwable->getMessage(), \E_USER_WARNING);
            }

            return false;
        }
    }

    private function toNewPath(string $path): string
    {
        $protocolOptions = $this->getOptions($path)['protocol'] ?? [];
        $pathOptions = $this->getOptions($path)['path'] ?? [];
        [
            'protocol' => $oldProtocol,
            'remaining' => $remaining,
        ] = $this->trimProtocol($path);

        $protocol = $oldProtocol;

        if (\is_string($set = ($protocolOptions['set'] ?? null))) {
            $protocol = $set;
        } else {
            if (\is_string($prepend = ($protocolOptions['prepend'] ?? null))) {
                $protocol = $prepend . $protocol;
            }

            if (\is_string($append = ($protocolOptions['append'] ?? null))) {
                $protocol = $protocol . $append;
            }
        }

        if (\is_string($set = ($pathOptions['set'] ?? null))) {
            $remaining = $set;
        } else {
            if (\is_string($prepend = ($pathOptions['prepend'] ?? null))) {
                if ($pathOptions['prepend_safe_separator'] ?? false) {
                    $prepend = \rtrim($prepend, '/') . '/';
                    $remaining = \ltrim($remaining, '/');
                }

                $remaining = $prepend . $remaining;
            }

            if (\is_string($append = ($pathOptions['append'] ?? null))) {
                $remaining = $remaining . $append;
            }
        }

        return $protocol . '://' . $remaining;
    }

    private function fromNewPath(string $path): string
    {
        $pathOptions = $this->getOptions(null)['path'] ?? [];

        if (\is_string($prepend = ($pathOptions['prepend'] ?? null))) {
            $prependSlash = false;

            if (($pathOptions['prepend_safe_separator'] ?? false) && \str_starts_with($path, '/')) {
                $path = \mb_substr($path, 1);
                $prependSlash = true;
            }

            $path = \mb_substr($path, \mb_strlen($prepend));

            if ($prependSlash) {
                $path = '/' . $path;
            }
        }

        if (\is_string($append = ($pathOptions['append'] ?? null)) && \str_ends_with($path, $append)) {
            $path = \mb_substr($path, 0, -\mb_strlen($append));
        }

        return $path;
    }

    /**
     * @return array{
     *     path?: array{
     *          set?: string,
     *          prepend?: string,
     *          append?: string,
     *          prepend_safe_separator?: bool
     *     },
     *     protocol?: array{
     *          set?: string,
     *          prepend?: string,
     *          append?: string
     *     }
     * }
     */
    private function getOptions(?string $path): array
    {
        $protocol = $this->lastProtocol;

        if ($path !== null) {
            $protocol = $this->trimProtocol($path)['protocol'];
            $this->lastProtocol = $protocol;
        }

        $options = \stream_context_get_options($this->getContext())[$protocol] ?? [];

        if (!\is_array($options)) {
            return [];
        }

        if (isset($options['path'])) {
            if (!\is_array($options['path'])) {
                \trigger_error(\sprintf('Option "path" is not a valid array/object for protocol: "%s"', $protocol), \E_USER_WARNING);
                unset($options['path']);
            } else {
                if (!\is_string($options['path']['set'] ?? null)) {
                    \trigger_error(\sprintf('Option "path.set" is not a valid string for protocol: "%s"', $protocol), \E_USER_WARNING);
                    unset($options['path']['set']);
                }

                if (!\is_string($options['path']['prepend'] ?? null)) {
                    \trigger_error(\sprintf('Option "path.prepend" is not a valid string for protocol: "%s"', $protocol), \E_USER_WARNING);
                    unset($options['path']['prepend']);
                }

                if (!\is_string($options['path']['append'] ?? null)) {
                    \trigger_error(\sprintf('Option "path.append" is not a valid string for protocol: "%s"', $protocol), \E_USER_WARNING);
                    unset($options['path']['append']);
                }

                if (!\is_bool($options['path']['prepend_safe_separator'] ?? null)) {
                    \trigger_error(\sprintf('Option "path.prepend_safe_separator" is not a valid bool for protocol: "%s"', $protocol), \E_USER_WARNING);
                    unset($options['path']['prepend_safe_separator']);
                }
            }
        }

        if (isset($options['protocol'])) {
            if (!\is_array($options['protocol'])) {
                \trigger_error(\sprintf('Option "protocol" is not a valid array/object for protocol: "%s"', $protocol), \E_USER_WARNING);
                unset($options['protocol']);
            } else {
                if (!\is_string($options['protocol']['set'] ?? null)) {
                    \trigger_error(\sprintf('Option "protocol.set" is not a valid string for protocol: "%s"', $protocol), \E_USER_WARNING);
                    unset($options['protocol']['set']);
                }

                if (!\is_string($options['protocol']['prepend'] ?? null)) {
                    \trigger_error(\sprintf('Option "protocol.prepend" is not a valid string for protocol: "%s"', $protocol), \E_USER_WARNING);
                    unset($options['protocol']['prepend']);
                }

                if (!\is_string($options['protocol']['append'] ?? null)) {
                    \trigger_error(\sprintf('Option "protocol.append" is not a valid string for protocol: "%s"', $protocol), \E_USER_WARNING);
                    unset($options['protocol']['append']);
                }
            }
        }

        return $options;
    }

    /**
     * @return resource
     */
    private function getContext()
    {
        return $this->context ?? \stream_context_get_default();
    }

    /**
     * @return array{protocol: ?string, remaining: string}
     */
    private function trimProtocol(string $path): array
    {
        $split = \explode('://', $path, 2);
        $result = [
            'protocol' => null,
            'remaining' => \array_pop($split),
        ];

        if ($split !== []) {
            $result['protocol'] = $split[0];
        }

        return $result;
    }
}
