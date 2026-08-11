<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Validation commune (Phase 2).
 *
 * Centralise les validations répétées : email, dates, entiers, longueurs,
 * appartenance à une liste, etc. Retourne true/false ou lève une exception
 * selon le mode choisi.
 */
final class Validator
{
    /**
     * Valide une adresse email.
     */
    public function email(mixed $value): bool
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Valide une date au format YYYY-MM-DD (ou avec heure).
     */
    public function date(mixed $value): bool
    {
        if (!is_string($value) || $value === '') {
            return false;
        }
        $format = strlen($value) > 10 ? 'Y-m-d H:i:s' : 'Y-m-d';
        $d = \DateTime::createFromFormat($format, $value);
        return $d !== false && $d->format($format) === $value;
    }

    /**
     * Valide un entier strictement positif.
     */
    public function positiveInt(mixed $value): bool
    {
        if (is_int($value)) {
            return $value > 0;
        }
        return is_string($value) && ctype_digit($value) && (int) $value > 0;
    }

    /**
     * Valide la longueur d'une chaîne (min/max inclus).
     */
    public function length(mixed $value, int $min = 0, int $max = 255): bool
    {
        if (!is_string($value)) {
            return false;
        }
        $len = mb_strlen($value);
        return $len >= $min && $len <= $max;
    }

    /**
     * Valide qu'une valeur appartient à une liste.
     *
     * @param array<int|string,mixed> $allowed
     */
    public function inList(mixed $value, array $allowed): bool
    {
        return in_array($value, $allowed, true);
    }

    /**
     * Valide qu'une chaîne ne contient que des caractères alphanumériques
     * (utile pour les identifiants techniques).
     */
    public function alphanumeric(mixed $value): bool
    {
        return is_string($value) && preg_match('/^[a-zA-Z0-9_-]+$/', $value) === 1;
    }

    /**
     * Valide un tableau de champs requis.
     *
     * @param array<string,mixed> $data
     * @param list<string> $required
     * @return list<string> Champs manquants.
     */
    public function requiredFields(array $data, array $required): array
    {
        $missing = [];
        foreach ($required as $field) {
            $value = $data[$field] ?? null;
            if ($value === null || (is_string($value) && trim($value) === '')) {
                $missing[] = $field;
            }
        }
        return $missing;
    }
}
