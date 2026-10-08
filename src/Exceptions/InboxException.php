<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\Exceptions;

use RuntimeException;

/**
 * Base class for the inbox's own exceptions, so callers can catch them all.
 */
abstract class InboxException extends RuntimeException {}
