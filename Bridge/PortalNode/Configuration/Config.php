<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Bridge\PortalNode\Configuration;

use Heptacom\HeptaConnect\Core\Bridge\PortalNode\Configuration\Contract\InstructionTokenContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract;

/**
 * Facade to supply portal node configuration changes in a short-notation integration configuration file.
 */
class Config
{
    private static ?PortalNodeConfigurationHelper $configHelper = null;

    /**
     * @var InstructionTokenContract[]
     */
    private static array $instructions = [];

    public function __construct()
    {
        self::$instructions = [];
    }

    /**
     * Uses the given configuration array as complete configuration.
     *
     * @param class-string<PortalContract>|class-string<PortalExtensionContract>|class-string|string $query
     * @param array|\Closure(\Closure(): array): array                                               $payload
     */
    public static function set(string $query, array|\Closure $payload): void
    {
        if (\is_array($payload)) {
            $array = $payload;
            $payload = static fn () => $array;
        }

        self::$instructions[] = new ClosureInstructionToken($query, $payload);
    }

    /**
     * Uses @see \array_replace to combine the given configuration array with the existing configuration in the chain.
     *
     * @param class-string<PortalContract>|class-string<PortalExtensionContract>|class-string|string $query
     * @param array|\Closure():array                                                                 $payload
     */
    public static function replace(string $query, array|\Closure $payload, bool $recursive = true): void
    {
        if (\is_array($payload)) {
            $array = $payload;
            $payload = static fn () => $array;
        }

        $closure = $payload;

        if ($recursive) {
            $payload = static fn (\Closure $loadConfig): array => \array_replace_recursive($loadConfig(), $closure());
        } else {
            $payload = static fn (\Closure $loadConfig): array => \array_replace($loadConfig(), $closure());
        }

        self::$instructions[] = new ClosureInstructionToken($query, $payload);
    }

    /**
     * Uses @see \array_merge to combine the given configuration array with the existing configuration in the chain.
     *
     * @param class-string<PortalContract>|class-string<PortalExtensionContract>|class-string|string $query
     * @param array|\Closure():array                                                                 $payload
     */
    public static function merge(string $query, array|\Closure $payload, bool $recursive = true): void
    {
        if (\is_array($payload)) {
            $array = $payload;
            $payload = static fn () => $array;
        }

        $closure = $payload;

        if ($recursive) {
            $payload = static function (\Closure $loadConfig) use ($closure): array {
                /** @var array $config */
                $config = $loadConfig();

                return \array_merge_recursive($config, $closure());
            };
        } else {
            $payload = static function (\Closure $loadConfig) use ($closure): array {
                /** @var array $config */
                $config = $loadConfig();

                return \array_merge($config, $closure());
            };
        }

        self::$instructions[] = new ClosureInstructionToken($query, $payload);
    }

    /**
     * Deep-unsets configuration keys from the previous chain result.
     *
     * @param class-string<PortalContract>|class-string<PortalExtensionContract>|class-string|string $query
     * @param array|\Closure():array                                                                 $payload
     */
    public static function reset(string $query, array|\Closure $payload): void
    {
        if (\is_array($payload)) {
            $array = $payload;
            $payload = static fn () => $array;
        }

        self::$instructions[] = new ClosureInstructionToken($query, static function (\Closure $loadConfig) use ($payload): array {
            /** @var array $config */
            $config = $loadConfig();

            self::unsetArrayByKeys($config, $payload());

            return $config;
        });
    }

    /**
     * Access the configuration helper.
     */
    public static function helper(): PortalNodeConfigurationHelper
    {
        return self::$configHelper ??= new PortalNodeConfigurationHelper();
    }

    /**
     * Returns all configuration instructions and clears the buffer.
     *
     * @return InstructionTokenContract[]
     */
    public function buildInstructions(): array
    {
        $result = self::$instructions;
        self::$instructions = [];

        return $result;
    }

    private static function unsetArrayByKeys(array &$array, array $unsetInstructions): void
    {
        foreach ($unsetInstructions as $parent => $key) {
            if (\is_array($key)) {
                if (isset($array[$parent]) && \is_array($array[$parent])) {
                    self::unsetArrayByKeys($array[$parent], $key);
                }
            } else if (\is_scalar($key)) {
                unset($array[(string) $key]);
            }
        }
    }
}
