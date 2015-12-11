<?php

namespace Screamz\SecureDownloadBundle\Core\Classes;

/**
 * Class ErrorCode
 *
 * Add constants for error code handling in download request.
 *
 * @author Andréas HANSS <ahanss@kaliop.com>
 */
abstract class ErrorCode
{
    // Document error
    CONST UNKNOWN_ERROR = 0;
    CONST DOCUMENT_EXPIRED = 1;
    CONST INVALID_STORED_DOCUMENT_TYPE = 2;
    CONST INVALID_ACCESS_KEY = 3;
    CONST INVALID_FILEPATH = 4;
}
