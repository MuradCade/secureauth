<?php

namespace SecureAuth\Jobs;


class WorkerJob
{
    public static function run(string $jobClass, array $configuration, array $payload)
    {
        if (!class_exists($jobClass)) {
            throw new \Exception("Job class $jobClass not found.");
        }

        $job = new $jobClass();

        if (!$job instanceof JobInterface) {
            throw new \Exception("$jobClass must implement JobInterface.");
        }

        return $job->handle($configuration, $payload);
    }
}
