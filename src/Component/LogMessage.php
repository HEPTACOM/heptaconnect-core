<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Component;

/**
 * @method static string EMIT_NO_THROW()
 * @method static string EMIT_NO_EMITTER_FOR_TYPE()
 */
abstract class LogMessage
{
    public static function __callStatic(string $name, array $arguments): string
    {
        return $name;
    }
}
