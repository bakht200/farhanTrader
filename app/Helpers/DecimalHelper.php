<?php

if (!function_exists('roundDecimal')) {
    /**
     * Round decimal to 2 decimal places
     * 
     * @param float|string|null $value
     * @return float
     */
    function roundDecimal($value)
    {
        if ($value === null || $value === '') {
            return 0.00;
        }
        return round((float) $value, 2);
    }
}

if (!function_exists('formatDecimal')) {
    /**
     * Format decimal to 2 decimal places for display
     * 
     * @param float|string|null $value
     * @return string
     */
    function formatDecimal($value)
    {
        return number_format(roundDecimal($value), 2);
    }
}

