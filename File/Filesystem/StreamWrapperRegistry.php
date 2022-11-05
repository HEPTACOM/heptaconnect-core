<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\File\Filesystem;

use Heptacom\HeptaConnect\Core\File\Filesystem\Contract\StreamWrapperInterface;

final class StreamWrapperRegistry implements StreamWrapperInterface
{
    private static array $streamWrapperByScheme = [];

    private array $variables = [];

    private ?StreamWrapperInterface $decorated = null;

    public function __construct()
    {
    }

    public function __destruct()
    {
        $this->decorated = null;
    }

    /**
     * Forwards the context resource
     */
    public function __get(string $name)
    {
        return $this->variables[$name] ?? null;
    }

    /**
     * Forwards the context resource
     */
    public function __set(string $name, $value): void
    {
        $this->variables[$name] = $value;
    }

    public static function register(string $scheme, StreamWrapperInterface $streamWrapper): void
    {
        static::deregister($scheme);

        static::$streamWrapperByScheme[$scheme] = $streamWrapper;
        \stream_wrapper_register($scheme, static::class, \STREAM_IS_URL);
    }

    public static function deregister(string $scheme): void
    {
        if (isset(static::$streamWrapperByScheme[$scheme])) {
            unset(static::$streamWrapperByScheme[$scheme]);

            \stream_wrapper_unregister($scheme);
        }
    }

    public function dir_closedir(): bool
    {
        return $this->getDecorated()->dir_closedir();
    }

    public function dir_opendir(string $path, int $options): bool
    {
        return $this->getDecoratedByPath($path)->dir_opendir($path, $options);
    }

    public function dir_readdir()
    {
        return $this->getDecorated()->dir_readdir();
    }

    public function dir_rewinddir(): bool
    {
        return $this->getDecorated()->dir_rewinddir();
    }

    public function mkdir(string $path, int $mode, int $options): bool
    {
        return $this->getDecoratedByPath($path)->mkdir($path, $mode, $options);
    }

    public function rename(string $path_from, string $path_to): bool
    {
        return $this->getDecoratedByPath($path_from)->rename($path_from, $path_to);
    }

    public function rmdir(string $path, int $options): bool
    {
        return $this->getDecoratedByPath($path)->rmdir($path, $options);
    }

    public function stream_cast(int $cast_as)
    {
        return $this->getDecorated()->stream_cast($cast_as);
    }

    public function stream_close(): void
    {
        $this->getDecorated()->stream_close();
    }

    public function stream_eof(): bool
    {
        return $this->getDecorated()->stream_eof();
    }

    public function stream_flush(): bool
    {
        return $this->getDecorated()->stream_flush();
    }

    public function stream_lock(int $operation): bool
    {
        return $this->getDecorated()->stream_lock($operation);
    }

    public function stream_metadata(string $path, int $option, $value): bool
    {
        return $this->getDecoratedByPath($path)->stream_metadata($path, $option, $value);
    }

    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        return $this->getDecoratedByPath($path)->stream_open($path, $mode, $options, $opened_path);
    }

    public function stream_read(int $count)
    {
        return $this->getDecorated()->stream_read($count);
    }

    public function stream_seek(int $offset, int $whence = \SEEK_SET): bool
    {
        return $this->getDecorated()->stream_seek($offset, $whence);
    }

    public function stream_set_option(int $option, int $arg1, int $arg2): bool
    {
        return $this->getDecorated()->stream_set_option($option, $arg1, $arg2);
    }

    public function stream_stat()
    {
        return $this->getDecorated()->stream_stat();
    }

    public function stream_tell(): int
    {
        return $this->getDecorated()->stream_tell();
    }

    public function stream_truncate(int $new_size): bool
    {
        return $this->getDecorated()->stream_truncate($new_size);
    }

    public function stream_write(string $data): int
    {
        return $this->getDecorated()->stream_write($data);
    }

    public function unlink(string $path): bool
    {
        return $this->getDecoratedByPath($path)->unlink($path);
    }

    public function url_stat(string $path, int $flags)
    {
        return $this->getDecoratedByPath($path)->url_stat($path, $flags);
    }

    private function getDecorated(): StreamWrapperInterface
    {
        $decorated = $this->decorated;

        if ($decorated === null) {
            throw new \RuntimeException('No path was detected to match the underlying stream wrapper', 1667052400);
        }

        foreach ($this->variables as $name => $value) {
            $decorated->$name = $value;
        }

        return $decorated;
    }

    private function getDecoratedByPath(string $path): StreamWrapperInterface
    {
        $decorated = $this->decorated;

        if ($decorated === null) {
            $scheme = \mb_substr($path, 0, \mb_strpos($path, ':'));

            $this->decorated = static::$streamWrapperByScheme[$scheme] ?? null;
        }

        return $this->getDecorated();
    }
}
