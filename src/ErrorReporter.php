<?php

declare(strict_types=1);

namespace FastShit;

use Throwable;

/**
 * Generic, opt-in error capture. Call Register() once during bootstrap with a
 * project-specific ErrorSink; this wires up PHP's error, exception and shutdown
 * handlers and forwards everything to the sink as a normalized ErrorReport.
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
        set_exception_handler(self::HandleException(...));
        register_shutdown_function(self::HandleShutdown(...));
    }

    public static function HandleError(int $type, string $message, string $file, int $line): bool
    {
        self::Dispatch(new ErrorReport($type, $message, $file, $line));
        return true; // reported; suppress PHP's default output (display_errors)
    }

    public static function HandleException(Throwable $exception): void
    {
        self::Dispatch(new ErrorReport(
            E_ERROR,
            $exception::class . ': ' . $exception->getMessage() . "\n" . $exception->getTraceAsString(),
            $exception->getFile(),
            $exception->getLine()
        ));
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
