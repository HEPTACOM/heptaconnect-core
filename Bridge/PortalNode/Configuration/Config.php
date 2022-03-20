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

    /**
     * Uses the given configuration array as complete configuration.
     *
     * @param class-string<PortalContract>|class-string<PortalExtensionContract>|class-string|string $query
     * @param array|\Closure(\Closure(): array)                                                      $payload
     */
    public static function set(string $query, $payload): void
    {
        if (\is_array($payload)) {
            $array = $payload;
            $payload = static fn () => $array;
        }

        if ($payload instanceof \Closure) {
            self::$instructions[] = new ClosureInstructionToken($query, $payload);
        }
    }

    /**
     * Uses @see \array_replace to combine the given configuration array with the existing configuration in the chain.
     *
     * @param class-string<PortalContract>|class-string<PortalExtensionContract>|class-string|string $query
     * @param array|\Closure():array                                                                 $payload
     */
    public static function replace(string $query, $payload, bool $recursive = true): void
    {
        if (\is_array($payload)) {
            $array = $payload;
            $payload = static fn () => $array;
        }

        if ($payload instanceof \Closure) {
            $closure = $payload;

            if ($recursive) {
                $payload = static fn (\Closure $loadConfig): array => \array_replace_recursive($loadConfig(), $closure());
            } else {
                $payload = static fn (\Closure $loadConfig): array => \array_replace($loadConfig(), $closure());
            }

            self::$instructions[] = new ClosureInstructionToken($query, $payload);
        }
    }

    /**
     * Uses @see \array_merge to combine the given configuration array with the existing configuration in the chain.
     *
     * @param class-string<PortalContract>|class-string<PortalExtensionContract>|class-string|string $query
     * @param array|\Closure():array                                                                 $payload
     */
    public static function merge(string $query, $payload, bool $recursive = true): void
    {
        if (\is_array($payload)) {
            $array = $payload;
            $payload = static fn () => $array;
        }

        if ($payload instanceof \Closure) {
            $closure = $payload;

            if ($recursive) {
                $payload = static fn (\Closure $loadConfig): array => \array_merge_recursive($loadConfig(), $closure());
            } else {
                $payload = static fn (\Closure $loadConfig): array => \array_merge($loadConfig(), $closure());
            }

            self::$instructions[] = new ClosureInstructionToken($query, $payload);
        }
    }

    /**
     * Deep-unsets configuration keys from the previous chain result.
     *
     * @param class-string<PortalContract>|class-string<PortalExtensionContract>|class-string|string $query
     * @param array|\Closure():array                                                                 $payload
     */
    public static function reset(string $query, $payload): void
    {
        $deepUnset = static function (array &$array, array $unsetInstructions) use (&$deepUnset): void {
            foreach ($unsetInstructions as $parent => $key) {
                if (\is_array($key)) {
                    if (isset($array[$parent])) {
                        $deepUnset($array[$parent], $key);
                    }
                } else {
                    unset($array[$key]);
                }
            }
        };

        if (\is_array($payload)) {
            $array = $payload;
            $payload = static fn () => $array;
        }

        if ($payload instanceof \Closure) {
            self::$instructions[] = new ClosureInstructionToken($query, static function (\Closure $loadConfig) use ($deepUnset, $payload): array {
                $config = $loadConfig();

                $deepUnset($config, $payload());

                return $config;
            });
        }
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
}
