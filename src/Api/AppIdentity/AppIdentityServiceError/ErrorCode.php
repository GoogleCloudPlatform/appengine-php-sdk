<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: app_identity_service.proto

namespace Google\AppEngine\Api\AppIdentity\AppIdentityServiceError;

use UnexpectedValueException;

/**
 * The rpc calls may return an application error which may have a value
 * from error code in gaiamint response or AppIdentityServiceError. The goal
 * of returning backend error is to make debugging easier.
 * See gaiamintservice.proto for more errors.
 *
 * Protobuf type <code>google.appEngine.api.appIdentity.AppIdentityServiceError.ErrorCode</code>
 */
class ErrorCode
{
    /**
     * Errors with low numbers are from gaiamintservice.proto.
     * Only a few are copied here as this is visible externally.
     *
     * Generated from protobuf enum <code>SUCCESS = 0;</code>
     */
    const SUCCESS = 0;
    /**
     * Generated from protobuf enum <code>UNKNOWN_SCOPE = 9;</code>
     */
    const UNKNOWN_SCOPE = 9;
    /**
     * Errors 1000 and higher are unique to this service.
     *
     * Generated from protobuf enum <code>BLOB_TOO_LARGE = 1000;</code>
     */
    const BLOB_TOO_LARGE = 1000;
    /**
     * Generated from protobuf enum <code>DEADLINE_EXCEEDED = 1001;</code>
     */
    const DEADLINE_EXCEEDED = 1001;
    /**
     * Generated from protobuf enum <code>NOT_A_VALID_APP = 1002;</code>
     */
    const NOT_A_VALID_APP = 1002;
    /**
     * Generated from protobuf enum <code>UNKNOWN_ERROR = 1003;</code>
     */
    const UNKNOWN_ERROR = 1003;
    /**
     * Generated from protobuf enum <code>GAIAMINT_NOT_INITIAILIZED = 1004;</code>
     */
    const GAIAMINT_NOT_INITIAILIZED = 1004;
    /**
     * Generated from protobuf enum <code>NOT_ALLOWED = 1005;</code>
     */
    const NOT_ALLOWED = 1005;
    /**
     * Generated from protobuf enum <code>NOT_IMPLEMENTED = 1006;</code>
     */
    const NOT_IMPLEMENTED = 1006;

    private static $valueToName = [
        self::SUCCESS => 'SUCCESS',
        self::UNKNOWN_SCOPE => 'UNKNOWN_SCOPE',
        self::BLOB_TOO_LARGE => 'BLOB_TOO_LARGE',
        self::DEADLINE_EXCEEDED => 'DEADLINE_EXCEEDED',
        self::NOT_A_VALID_APP => 'NOT_A_VALID_APP',
        self::UNKNOWN_ERROR => 'UNKNOWN_ERROR',
        self::GAIAMINT_NOT_INITIAILIZED => 'GAIAMINT_NOT_INITIAILIZED',
        self::NOT_ALLOWED => 'NOT_ALLOWED',
        self::NOT_IMPLEMENTED => 'NOT_IMPLEMENTED',
    ];

    public static function name($value)
    {
        if (!isset(self::$valueToName[$value])) {
            throw new UnexpectedValueException(sprintf(
                    'Enum %s has no name defined for value %s', __CLASS__, $value));
        }
        return self::$valueToName[$value];
    }


    public static function value($name)
    {
        $const = __CLASS__ . '::' . strtoupper($name);
        if (!defined($const)) {
            throw new UnexpectedValueException(sprintf(
                    'Enum %s has no value defined for name %s', __CLASS__, $name));
        }
        return constant($const);
    }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ErrorCode::class, \Google\AppEngine\Api\AppIdentity\AppIdentityServiceError_ErrorCode::class);

