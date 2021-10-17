<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Component;

/**
 * @method static string EMIT_NO_THROW()
 * @method static string EMIT_NO_EMITTER_FOR_TYPE()
 * @method static string EXPLORE_NO_THROW()
 * @method static string EXPLORE_NO_EXPLORER_FOR_TYPE()
 * @method static string RECEIVE_NO_THROW()
 * @method static string RECEIVE_NO_RECEIVER_FOR_TYPE()
 * @method static string STATUS_REPORT_NO_THROW()
 * @method static string STATUS_REPORT_NO_STATUS_REPORTER_FOR_TYPE()
 * @method static string PORTAL_LOAD_ERROR()
 * @method static string PORTAL_EXTENSION_LOAD_ERROR()
 * @method static string PORTAL_NODE_CONFIGURATION_INVALID()
 * @method static string MARK_AS_FAILED_ENTITY_IS_UNMAPPED()
 */
abstract class LogMessage
{
    public static function __callStatic(string $name, array $arguments): string
    {
        return $name;
    }
}
