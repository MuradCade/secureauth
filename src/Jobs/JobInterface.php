<?php

namespace SecureAuth\Jobs;

interface JobInterface
{
    /**
     * Handle the job logic.
     *
     * @param array $configuration System configuration (DB, mail, etc.)
     * @param array $payload Job-specific data
     * @return bool|string Returns true on success, or error message on failure
     */
    public function handle(array $configuration, array $payload);
}
