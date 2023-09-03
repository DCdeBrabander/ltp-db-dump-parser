<?php

namespace App\Csv;

/**
 * Functions we want to perform on (sub)sets of our data.
 * Needs to support arrays as input and returning "simple" results.
 */
class Math
{
    final public static function average(array $input, int $precision = 3): int|float
    {
        $filtered = array_filter($input);
        return round(array_sum($filtered) / count($filtered), $precision);
    }

    final public static function min(array $input): int|float
    {
        return min(array_filter($input));
    }

    final public static function max(array $input): int|float
    {
        return max(array_filter($input));
    }
}
