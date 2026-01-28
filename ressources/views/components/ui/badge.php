<?php
/**
 * CheckMaster Premium - Badge Component
 * 
 * Composant badge avec mapping automatique des statuts.
 * Centralise toute la logique de couleurs/labels des statuts.
 * 
 * @param string $status - Statut brut (valide, rejete, en_cours, etc.)
 * @param string|null $customLabel - Label personnalisé (optionnel)
 * @param bool $dot - Afficher un point indicateur
 */

function renderBadge(string $status, ?string $customLabel = null, bool $dot = false): string {
    // Mapping centralisé des statuts -> classes CSS et labels
    $mapping = [
        // Statuts génériques
        'valide'        => ['class' => 'success', 'label' => 'Validé'],
        'valid'         => ['class' => 'success', 'label' => 'Validé'],
        'validated'     => ['class' => 'success', 'label' => 'Validé'],
        'valider'       => ['class' => 'success', 'label' => 'Validé'],
        'admis'         => ['class' => 'success', 'label' => 'Admis'],
        'accepted'      => ['class' => 'success', 'label' => 'Accepté'],
        'approve'       => ['class' => 'success', 'label' => 'Approuvé'],
        'approuve'      => ['class' => 'success', 'label' => 'Approuvé'],
        'complete'      => ['class' => 'success', 'label' => 'Terminé'],
        'termine'       => ['class' => 'success', 'label' => 'Terminé'],
        'actif'         => ['class' => 'success', 'label' => 'Actif'],
        'active'        => ['class' => 'success', 'label' => 'Actif'],
        'oui'           => ['class' => 'success', 'label' => 'Oui'],
        'yes'           => ['class' => 'success', 'label' => 'Oui'],
        'true'          => ['class' => 'success', 'label' => 'Oui'],
        '1'             => ['class' => 'success', 'label' => 'Actif'],
        
        // Statuts danger/rejet
        'rejete'        => ['class' => 'danger', 'label' => 'Rejeté'],
        'rejeter'       => ['class' => 'danger', 'label' => 'Rejeté'],
        'rejected'      => ['class' => 'danger', 'label' => 'Rejeté'],
        'refuse'        => ['class' => 'danger', 'label' => 'Refusé'],
        'ajourne'       => ['class' => 'danger', 'label' => 'Ajourné'],
        'failed'        => ['class' => 'danger', 'label' => 'Échoué'],
        'echoue'        => ['class' => 'danger', 'label' => 'Échoué'],
        'error'         => ['class' => 'danger', 'label' => 'Erreur'],
        'erreur'        => ['class' => 'danger', 'label' => 'Erreur'],
        'inactif'       => ['class' => 'danger', 'label' => 'Inactif'],
        'inactive'      => ['class' => 'danger', 'label' => 'Inactif'],
        'non'           => ['class' => 'danger', 'label' => 'Non'],
        'no'            => ['class' => 'danger', 'label' => 'Non'],
        'false'         => ['class' => 'danger', 'label' => 'Non'],
        '0'             => ['class' => 'danger', 'label' => 'Inactif'],
        
        // Statuts warning/en cours
        'en_cours'      => ['class' => 'warning', 'label' => 'En cours'],
        'encours'       => ['class' => 'warning', 'label' => 'En cours'],
        'pending'       => ['class' => 'warning', 'label' => 'En cours'],
        'in_progress'   => ['class' => 'warning', 'label' => 'En cours'],
        'processing'    => ['class' => 'warning', 'label' => 'Traitement'],
        'traitement'    => ['class' => 'warning', 'label' => 'Traitement'],
        'partiel'       => ['class' => 'warning', 'label' => 'Partiel'],
        'partial'       => ['class' => 'warning', 'label' => 'Partiel'],
        'revision'      => ['class' => 'warning', 'label' => 'En révision'],
        
        // Statuts info/attente
        'attente'       => ['class' => 'info', 'label' => 'En attente'],
        'en_attente'    => ['class' => 'info', 'label' => 'En attente'],
        'waiting'       => ['class' => 'info', 'label' => 'En attente'],
        'soumis'        => ['class' => 'info', 'label' => 'Soumis'],
        'submitted'     => ['class' => 'info', 'label' => 'Soumis'],
        'nouveau'       => ['class' => 'info', 'label' => 'Nouveau'],
        'new'           => ['class' => 'info', 'label' => 'Nouveau'],
        'draft'         => ['class' => 'info', 'label' => 'Brouillon'],
        'brouillon'     => ['class' => 'info', 'label' => 'Brouillon'],
        
        // Statuts spéciaux
        'archive'       => ['class' => 'muted', 'label' => 'Archivé'],
        'archived'      => ['class' => 'muted', 'label' => 'Archivé'],
        'expire'        => ['class' => 'muted', 'label' => 'Expiré'],
        'expired'       => ['class' => 'muted', 'label' => 'Expiré'],
        'suspendu'      => ['class' => 'muted', 'label' => 'Suspendu'],
        'suspended'     => ['class' => 'muted', 'label' => 'Suspendu'],
        
        // Mentions académiques
        'tres_bien'     => ['class' => 'success', 'label' => 'Très Bien'],
        'tres bien'     => ['class' => 'success', 'label' => 'Très Bien'],
        'bien'          => ['class' => 'primary', 'label' => 'Bien'],
        'assez_bien'    => ['class' => 'info', 'label' => 'Assez Bien'],
        'assez bien'    => ['class' => 'info', 'label' => 'Assez Bien'],
        'passable'      => ['class' => 'warning', 'label' => 'Passable'],
        
        // Priorités
        'haute'         => ['class' => 'danger', 'label' => 'Haute'],
        'high'          => ['class' => 'danger', 'label' => 'Haute'],
        'moyenne'       => ['class' => 'warning', 'label' => 'Moyenne'],
        'medium'        => ['class' => 'warning', 'label' => 'Moyenne'],
        'basse'         => ['class' => 'info', 'label' => 'Basse'],
        'low'           => ['class' => 'info', 'label' => 'Basse'],
    ];
    
    // Normalisation du statut (lowercase, trim)
    $normalizedStatus = strtolower(trim((string) $status));
    
    // Récupération des données ou valeurs par défaut
    $data = $mapping[$normalizedStatus] ?? ['class' => 'muted', 'label' => ucfirst($status)];
    
    // Utilisation du label personnalisé si fourni
    $label = $customLabel ?? $data['label'];
    
    // Construction du HTML
    $dotClass = $dot ? ' badge-dot' : '';
    
    return sprintf(
        '<span class="badge badge-%s%s">%s</span>',
        $data['class'],
        $dotClass,
        htmlspecialchars($label)
    );
}

/**
 * Badge avec compteur
 */
function renderBadgeCount(int $count, string $type = 'primary'): string {
    return sprintf(
        '<span class="badge badge-%s">%d</span>',
        htmlspecialchars($type),
        $count
    );
}

/**
 * Badge avec icône
 */
function renderBadgeIcon(string $status, string $icon, ?string $customLabel = null): string {
    $mapping = [
        'valide'    => ['class' => 'success', 'label' => 'Validé'],
        'rejete'    => ['class' => 'danger', 'label' => 'Rejeté'],
        'en_cours'  => ['class' => 'warning', 'label' => 'En cours'],
        'attente'   => ['class' => 'info', 'label' => 'En attente'],
    ];
    
    $normalizedStatus = strtolower(trim($status));
    $data = $mapping[$normalizedStatus] ?? ['class' => 'muted', 'label' => ucfirst($status)];
    $label = $customLabel ?? $data['label'];
    
    return sprintf(
        '<span class="badge badge-%s"><i class="fas %s"></i> %s</span>',
        $data['class'],
        htmlspecialchars($icon),
        htmlspecialchars($label)
    );
}
