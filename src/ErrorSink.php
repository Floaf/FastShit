<?php

declare(strict_types=1);

namespace FastShit;

/**
 * Receives normalized error reports. The framework stays generic; each project
 * provides a sink that decides where reports go (DB, log, push notification, ...).
 */
interface ErrorSink
{
    public function Report(ErrorReport $report): void;
}
