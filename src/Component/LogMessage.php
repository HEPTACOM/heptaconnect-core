<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Component;

/**
 * @method static string EMIT_NO_THROW()
 * @method static string EMIT_NO_STACKS()
 * @method static string EMIT_NO_EMITTER_FOR_TYPE()
 * @method static string RECEIVE_NO_THROW()
 * @method static string RECEIVE_NO_STACKS()
 * @method static string RECEIVE_NO_RECEIVER_FOR_TYPE()
 * @method static string STATUS_REPORT_NO_THROW()
 * @method static string STATUS_REPORT_NO_STACKS()
 * @method static string STATUS_REPORT_NO_RECEIVER_FOR_TYPE()
 * @method static string PORTAL_LOAD_ERROR()
 * @method static string PORTAL_EXTENSION_LOAD_ERROR()
 */
abstract class LogMessage
{
    public static function __callStatic(string $name, array $arguments): string
    {
        return $name;
    }
}
