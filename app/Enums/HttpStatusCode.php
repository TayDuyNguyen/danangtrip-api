<?php

namespace App\Enums;

/**
 * Enum HttpStatusCode
 * Standard HTTP response codes used throughout the application.
 */
enum HttpStatusCode: int
{
    case SUCCESS = 200;
    case CREATED = 201;
    case ACCEPTED = 202;
    case NO_CONTENT = 204;

    case BAD_REQUEST = 400;
    case UNAUTHORIZED = 401;
    case FORBIDDEN = 403;
    case NOT_FOUND = 404;
    case METHOD_NOT_ALLOWED = 405;
    case CONFLICT = 409;
    case VALIDATION_ERROR = 422;
    case TOO_MANY_REQUESTS = 429;

    case INTERNAL_SERVER_ERROR = 500;
    case SERVICE_UNAVAILABLE = 503;
}
