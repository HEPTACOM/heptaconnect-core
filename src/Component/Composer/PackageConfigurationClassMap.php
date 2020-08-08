<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Component\Composer;

use Heptacom\HeptaConnect\Dataset\Base\ScalarCollection\StringCollection;

class PackageConfigurationClassMap
{
    /**
     * @psalm-var array<class-string, string>
     *
     * @var array|string[]
     */
    private array $map = [];

    /**
     * @psalm-param class-string $fqcn
     */
    public function addClass(string $fqcn, string $filePath): void
    {
        $this->map[$fqcn] = $filePath;
    }

    public function count(): int
    {
        return \count($this->map);
    }

    public function clear(): void
    {
        $this->map = [];
    }

    /**
     * @psalm-param class-string $fqcn
     */
    public function getFileForClass(string $fqcn): ?string
    {
        return $this->map[$fqcn] ?? null;
    }

    /**
     * @psalm-return class-string|null
     */
    public function getClassForFile(string $filePath): ?string
    {
        return \array_search($filePath, $this->map, true) ?: null;
    }

    public function getClasses(): StringCollection
    {
        return new StringCollection(\array_keys($this->map));
    }

    public function getFiles(): StringCollection
    {
        return new StringCollection(\array_values($this->map));
    }
}
