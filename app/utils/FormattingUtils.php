<?php

/**
 * FormattingUtils - Centralized formatting utilities
 * 
 * Provides consistent formatting methods across the application
 * for numbers, dates, and monetary values.
 */
class FormattingUtils {
    
    /**
     * Format a monetary amount in FCFA
     * Uses space as thousand separator, no decimal places
     * 
     * @param float|int $amount The amount to format
     * @return string Formatted amount (e.g., "150 000")
     */
    public static function formatMoney($amount) {
        return number_format(floatval($amount), 0, ',', ' ');
    }
    
    /**
     * Format a decimal number (e.g., grades, averages)
     * 
     * @param float $number The number to format
     * @param int $decimals Number of decimal places (default: 2)
     * @return string Formatted number (e.g., "15.50")
     */
    public static function formatDecimal($number, $decimals = 2) {
        return number_format(floatval($number), $decimals);
    }
    
    /**
     * Format a date for display
     * 
     * @param string $date Date string (MySQL format or strtotime compatible)
     * @param string $format Output format (default: 'd/m/Y' for French format)
     * @return string Formatted date (e.g., "15/10/2024")
     */
    public static function formatDate($date, $format = 'd/m/Y') {
        if (empty($date)) {
            return '';
        }
        
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        if ($timestamp === false) {
            return $date; // Return original if parsing fails
        }
        
        return date($format, $timestamp);
    }
    
    /**
     * Format a date and time for display
     * 
     * @param string $datetime DateTime string
     * @param string $format Output format (default: 'd/m/Y H:i' for French format)
     * @return string Formatted datetime (e.g., "15/10/2024 14:30")
     */
    public static function formatDateTime($datetime, $format = 'd/m/Y H:i') {
        return self::formatDate($datetime, $format);
    }
    
    /**
     * Sanitize text for safe display (prevent XSS)
     * 
     * @param string $text The text to sanitize
     * @return string Sanitized text
     */
    public static function sanitizeText($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Format a percentage
     * 
     * @param float $value The value to format as percentage
     * @param int $decimals Number of decimal places (default: 1)
     * @return string Formatted percentage (e.g., "85.5%")
     */
    public static function formatPercentage($value, $decimals = 1) {
        return number_format(floatval($value), $decimals) . '%';
    }

    /**
     * Format a promotion value
     * Converts compressed format like "22322" or "2022" to "2022-2023"
     * 
     * @param string|null $promotion The promotion string to format
     * @return string Formatted promotion (e.g., "2022-2023")
     */
    public static function formatPromotion($promotion) {
        $promotion = trim((string)$promotion);
        if ($promotion === '') {
            return '';
        }

        // Cas "2022-2023" déjà bien formaté
        if (preg_match('/^[0-9]{4}-[0-9]{4}$/', $promotion)) {
            return $promotion;
        }

        // Cas "2022" -> "2022-2023"
        if (preg_match('/^[0-9]{4}$/', $promotion)) {
            return $promotion . '-' . ((int)$promotion + 1);
        }

        // Cas "22322" -> "2022-2023" (Format compressé: 2 Y2 Y1)
        // EvaluationSoutenanceService line 255: CONCAT('20', RIGHT(P, 2), '-', '20', SUBSTRING(P, 2, 2))
        if (preg_match('/^2([0-9]{2})([0-9]{2})$/', $promotion, $matches)) {
            // matches[1] = Y2, matches[2] = Y1
            return '20' . $matches[2] . '-20' . $matches[1];
        }

        return $promotion;
    }
}
