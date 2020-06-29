<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Component\Composer;

use Heptacom\HeptaConnect\Dataset\Base\ScalarCollection\StringCollection;

class PackageConfiguration
{
    private string $name = '';

    private StringCollection $tags;

    private array $configuration = [];

    public function __construct()
    {
        $this->tags = new StringCollection();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getTags(): StringCollection
    {
        return $this->tags;
    }

    public function setTags(StringCollection $tags): void
    {
        $this->tags = $tags;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function setConfiguration(array $configuration): void
    {
        $this->configuration = $configuration;
    }
}
