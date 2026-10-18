<?php

namespace App\Services;

use Carbon\Carbon;

class StatutoryDeadlineCalculator
{
    /**
     * Calculate the UK GDPR statutory response deadline.
     * 
     * In accordance with ICO (Information Commissioner's Office) guidance:
     * - The time period begins on the day after the request is received (Day 1).
     * - The statutory deadline is one calendar month from the day received.
     * - If the deadline falls on a weekend or UK public holiday, it moves to the next working day.
     * - If the corresponding day does not exist in the following month (e.g. 31st January -> 28th February),
     *   the deadline is the last day of that month.
     */
    public static function calculateDeadline(?Carbon $receivedDate = null, int $extensionMonths = 0): Carbon
    {
        $date = $receivedDate ? $receivedDate->copy() : now();
        $targetMonths = 1 + max(0, min(2, $extensionMonths)); // Max statutory extension is 2 additional months

        // Calculate 1 month ahead preserving end of month rules
        $deadline = $date->copy()->addMonthsNoOverflow($targetMonths);

        // Adjust if deadline falls on weekend or bank holiday
        return self::adjustForNonWorkingDays($deadline);
    }

    /**
     * Check if a date is a UK weekend or known holiday and roll forward to next working day.
     */
    public static function adjustForNonWorkingDays(Carbon $date): Carbon
    {
        $adjusted = $date->copy();

        while ($adjusted->isWeekend() || self::isUkBankHoliday($adjusted)) {
            $adjusted->addDay();
        }

        return $adjusted;
    }

    /**
     * Basic check for statutory UK bank holidays.
     */
    public static function isUkBankHoliday(Carbon $date): bool
    {
        $year = $date->year;
        $formatted = $date->format('Y-m-d');

        // Common fixed holidays
        $fixed = [
            "{$year}-01-01", // New Year's Day
            "{$year}-12-25", // Christmas Day
            "{$year}-12-26", // Boxing Day
        ];

        return in_array($formatted, $fixed);
    }
}
