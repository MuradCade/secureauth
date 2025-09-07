<?php

namespace SecureAuth\Security;

use SecureAuth\Repository\BaseRepository;

class RateLimiter
{
    private $limit;  // Max attempts allowed in interval
    private $interval; // Interval length in seconds
    private $baserepo;

    public function __construct(BaseRepository $baserepo, int $limit = 5, int $interval = 60)
    {
        $this->limit = $limit;
        $this->interval = $interval;
        $this->baserepo = $baserepo;
    }

    public function tooManyAttempts(string $ip, ?string $email = null)
    {
        $windowStart = date('Y-m-d H:i:s', time() - $this->interval);

        $result = $this->baserepo->query(
            'SELECT COUNT(*) AS attempts FROM login_attempts WHERE ip = ? AND attempt_time >= ? AND email = ? ',
            'sss',
            $ip,
            $windowStart,
            $email
        )->fetchOne();
        $attempts = isset($result['attempts']) ? (int)$result['attempts'] : 0;
        // return true if the attemp exceeds the limit / or return false if not
        return $attempts >= $this->limit;
    }
    /**
     * Returns seconds left to wait until next allowed attempt.
     *
     * @param string $ip User IP address
     * @param string|null $email Optional user email
     * @return int Seconds to wait, 0 if allowed immediately
     */
    public function getRetryAfterSeconds(string $ip, ?string $email = null): string
    {
        $windowStart = date('Y-m-d H:i:s', time() - $this->interval);
        $result = $this->baserepo->query(
            'SELECT MIN(attempt_time) as time FROM login_attempts  WHERE ip = ?  AND attempt_time >= ? AND (email = ? OR ? IS NULL)',
            'ssss',
            $ip,
            $windowStart,
            $email,
            $email
        )->fetchOne();


        // No recent attempts → no wait needed
        if (!$result || empty($result['time'])) {
            return 0;
        }

        $oldestTimestamp = strtotime($result['time']);

        // Fallback in case strtotime still fails (invalid format)
        if ($oldestTimestamp === false) {
            return 0;
        }

        $retryAfter = ($oldestTimestamp + $this->interval) - time();

        $timetowait =  $retryAfter > 0 ? $retryAfter : 0;
        /* the variable $timewait only returns the timetowite in simple integer means like this 50983 and this number is not understandable for human began we need to tell the user remainig time is 50 munite and 11 seconnds so we convert it to readabel*/
        // Convert to readable time
        $minutes = floor($retryAfter / 60);
        $seconds = $retryAfter % 60;
        $timeString = '';

        if ($minutes > 0) {
            $timeString .= $minutes . ' minute' . ($minutes > 1 ? 's' : '');
        }
        if ($minutes > 0 && $seconds > 0) {
            $timeString .= ' and ';
        }
        if ($seconds > 0) {
            $timeString .= $seconds . ' second' . ($seconds > 1 ? 's' : '');
        }

        return $timeString;
    }
}
