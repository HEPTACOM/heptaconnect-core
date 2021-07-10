<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\ConfigurationContract;

class PortalConfiguration extends ConfigurationContract
{
    private array $configuration;

    private ?array $flat = null;

    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
    }

    public function get(string $name)
    {
        return $this->flattened()[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return isset($this->flattened()[$name]);
    }

    public function keys(): array
    {
        return \array_keys(\array_filter($this->flattened(), static fn ($value): bool => !\is_array($value)));
    }

    private function &flattened(): array
    {
        if (\is_null($this->flat)) {
            $this->flat = [];

            $unwrappables = ['' => $this->configuration];

            do {
                $newUnwrappables = [];

                foreach ($unwrappables as $prefix => $unwrappable) {
                    if (\is_array($unwrappable)) {
                        foreach ($unwrappable as $key => $value) {
                            $newUnwrappables[$prefix.'.'.$key] = $value;
                        }
                    }

                    $this->flat[\ltrim($prefix, '.')] = $unwrappable;
                }

                $unwrappables = $newUnwrappables;
            } while ($unwrappables !== []);
        }

        return $this->flat;
    }
}
