<?php

declare(strict_types=1);

namespace FastShit;

use Throwable;

/**
 * Generic, opt-in error capture. Call Register() once during bootstrap with a
 * project-specific ErrorSink; this wires up PHP's error and shutdown handlers and
 * forwards everything to the sink as a normalized ErrorReport.
 *
 * Reporting is additive: PHP's own handling still runs afterwards, so display_errors
 * and log_errors keep working as configured (errors visible in development, hidden
 * in production). Uncaught exceptions are deliberately left to PHP, which turns them
 * into a fatal error with the stack trace in the message; that is picked up by the
 * shutdown handler, so they are reported exactly once and still displayed in
 * development.
 */
class ErrorReporter
{
    private static ?ErrorSink $sink = null;

    /** Guards against recursion if the sink itself triggers an error. */
    private static bool $handling = false;

    public static function Register(ErrorSink $sink): void
    {
        self::$sink = $sink;
        set_error_handler(self::HandleError(...));
        register_shutdown_function(self::HandleShutdown(...));
    }

    public static function HandleError(int $type, string $message, string $file, int $line): bool
    {
        // Errors silenced with @ (or excluded by error_reporting) are not errors to report
        if ((error_reporting() & $type) === 0) {
            return false;
        }

        self::Dispatch(new ErrorReport($type, $message, $file, $line));
        return false; // reported; let PHP's default handling (display_errors, log_errors) run as well
    }

    /**
     * Reports an exception that was caught rather than left to PHP (the Router uses this for the 500 page). Also
     * written to error_log, so there is a trace even when no sink is registered or the sink cannot connect.
     */
    public static function ReportThrowable(Throwable $exception): void
    {
        $message = $exception::class . ': ' . $exception->getMessage() . ' in ' . $exception->getFile() . ':'
            . $exception->getLine() . "
Stack trace:
" . $exception->getTraceAsString();
        error_log('Uncaught ' . $message);
        self::Dispatch(new ErrorReport(E_ERROR, $message, $exception->getFile(), $exception->getLine()));
    }

    public static function HandleShutdown(): void
    {
        $error = error_get_last();
        if ($error === null) {
            return;
        }

        // Only fatals here; warnings/notices already went through HandleError.
        $fatalTypes = [
            E_ERROR,
            E_PARSE,
            E_CORE_ERROR,
            E_COMPILE_ERROR,
            E_USER_ERROR
        ];

        if (in_array($error['type'], $fatalTypes, true)) {
            self::Dispatch(new ErrorReport($error['type'], $error['message'], $error['file'], $error['line']));
        }
    }

    private static function Dispatch(ErrorReport $report): void
    {
        $sink = self::$sink;
        if (self::$handling || $sink === null) {
            return;
        }

        self::$handling = true;
        try {
            $sink->Report($report);
        } catch (Throwable) {
            // A sink that fails must never take the process down or recurse.
        } finally {
            self::$handling = false;
        }
    }
}
