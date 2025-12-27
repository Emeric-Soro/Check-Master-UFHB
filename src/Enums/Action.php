<?php
declare(strict_types=1);

namespace App\Enums;

/**
 * Enum Action - Définit les actions possibles sur les ressources
 * 
 * Chaque case correspond à une colonne de la table permissions_actions
 * et représente une action granulaire qu'un utilisateur peut effectuer.
 */
enum Action: string {
    case Read = 'peut_lire';
    case Create = 'peut_creer';
    case Update = 'peut_modifier';
    case Delete = 'peut_supprimer';
    case Export = 'peut_exporter';
    case Validate = 'peut_valider';
    
    /**
     * Retourne le libellé français de l'action
     */
    public function label(): string {
        return match($this) {
            self::Read => 'Lire',
            self::Create => 'Créer',
            self::Update => 'Modifier',
            self::Delete => 'Supprimer',
            self::Export => 'Exporter',
            self::Validate => 'Valider',
        };
    }

    /**
     * Retourne l'initiale de l'action pour l'affichage compact
     */
    public function initial(): string {
        return match($this) {
            self::Read => 'R',
            self::Create => 'C',
            self::Update => 'M',
            self::Delete => 'S',
            self::Export => 'E',
            self::Validate => 'V',
        };
    }

    /**
     * Retourne l'icône FontAwesome correspondante
     */
    public function icon(): string {
        return match($this) {
            self::Read => 'fa-eye',
            self::Create => 'fa-plus',
            self::Update => 'fa-edit',
            self::Delete => 'fa-trash',
            self::Export => 'fa-download',
            self::Validate => 'fa-check-circle',
        };
    }

    /**
     * Retourne la classe CSS de couleur pour l'action
     */
    public function colorClass(): string {
        return match($this) {
            self::Read => 'text-blue-600',
            self::Create => 'text-green-600',
            self::Update => 'text-yellow-600',
            self::Delete => 'text-red-600',
            self::Export => 'text-purple-600',
            self::Validate => 'text-emerald-600',
        };
    }
}
