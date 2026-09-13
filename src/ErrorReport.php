<?php

declare(strict_types=1);

namespace FastShit;

/** A single error/exception/fatal, normalized so any sink can persist or forward it. */
readonly class ErrorReport
{
    public int $type;
    public string $message;
    public string $file;
    public int $line;

    public function __construct(
        int $type,
        string $message,
        string $file,
        int $line,
    ) {
        $this->type = $type;
        $this->message = $message;
        $this->file = $file;
        $this->line = $line;
    }
}
