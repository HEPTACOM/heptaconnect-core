<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\ConfigurationContract;

final class PortalConfiguration extends ConfigurationContract
{
    private ?array $flat = null;

    public function __construct(private array $configuration)
    {
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
        return \array_keys($this->flattened());
    }

    private function &flattened(): array
    {
        if ($this->flat === null) {
            $this->flat = [];

            $unwrappables = ['' => $this->configuration];

            do {
                $newUnwrappables = [];

                foreach ($unwrappables as $prefix => $unwrappable) {
                    if (\is_array($unwrappable)) {
                        foreach ($unwrappable as $key => $value) {
                            $newUnwrappables[$prefix . '.' . $key] = $value;
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
