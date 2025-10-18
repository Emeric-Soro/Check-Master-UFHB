#!/bin/bash
# ===========================================
# Script de Vérification Docker
# Check Master UFHB
# ===========================================
# Ce script vérifie que la configuration Docker est correcte
# pour les environnements de développement et de production

set -e

echo "================================================"
echo "Vérification de la Configuration Docker"
echo "================================================"
echo ""

# Couleurs pour l'output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Fonction pour afficher un succès
success() {
    echo -e "${GREEN}✓${NC} $1"
}

# Fonction pour afficher une erreur
error() {
    echo -e "${RED}✗${NC} $1"
}

# Fonction pour afficher un avertissement
warning() {
    echo -e "${YELLOW}!${NC} $1"
}

echo "=== 1. Vérification de l'Environnement de Développement ==="
echo ""

# Vérifier que les 3 services sont définis
dev_services=$(docker compose config 2>/dev/null | grep -E "^  [a-z]+:" | wc -l)
if [ "$dev_services" -eq 3 ]; then
    success "3 services définis en développement (web, db, phpmyadmin)"
else
    error "Nombre de services incorrect en développement: $dev_services (attendu: 3)"
fi

# Vérifier que phpMyAdmin est présent
if docker compose config 2>/dev/null | grep -q "phpmyadmin:"; then
    success "phpMyAdmin est activé en développement"
else
    error "phpMyAdmin n'est pas présent en développement"
fi

# Vérifier les ports exposés en développement
dev_ports=$(docker compose config 2>/dev/null | grep "published:" | wc -l)
if [ "$dev_ports" -ge 3 ]; then
    success "Ports exposés en développement: $dev_ports (web, db, phpmyadmin)"
else
    warning "Nombre de ports exposés: $dev_ports (attendu: au moins 3)"
fi

echo ""
echo "=== 2. Vérification de l'Environnement de Production ==="
echo ""

# Vérifier que seulement 2 services sont définis en production
prod_services=$(docker compose -f docker-compose.yml -f docker-compose.prod.yml config 2>/dev/null | grep -E "^  [a-z]+:" | wc -l)
if [ "$prod_services" -eq 2 ]; then
    success "2 services définis en production (web, db uniquement)"
else
    error "Nombre de services incorrect en production: $prod_services (attendu: 2)"
fi

# Vérifier que phpMyAdmin n'est PAS présent
if ! docker compose -f docker-compose.yml -f docker-compose.prod.yml config 2>/dev/null | grep -q "phpmyadmin:"; then
    success "phpMyAdmin est désactivé en production (sécurité)"
else
    error "phpMyAdmin est encore présent en production!"
fi

# Vérifier que le port de la base de données n'est pas exposé
if docker compose -f docker-compose.yml -f docker-compose.prod.yml config 2>/dev/null | grep -A 2 "db:" | grep -q "published.*3306"; then
    error "Le port MySQL (3306) est exposé en production!"
else
    success "Le port MySQL (3306) n'est PAS exposé en production (sécurité)"
fi

# Vérifier que le build target est 'production'
if docker compose -f docker-compose.yml -f docker-compose.prod.yml config 2>/dev/null | grep -A 5 "web:" | grep -q "target: production"; then
    success "L'image production utilise le target 'production' (multi-stage build)"
else
    warning "Le target de build n'est pas défini sur 'production'"
fi

echo ""
echo "=== 3. Vérification du Dockerfile ==="
echo ""

# Vérifier la présence de OPcache
if grep -q "opcache.enable=1" docker/php/Dockerfile; then
    success "OPcache est configuré dans le Dockerfile"
else
    error "OPcache n'est pas configuré"
fi

# Vérifier la désactivation de display_errors en production
if grep -q "display_errors=Off" docker/php/Dockerfile; then
    success "display_errors est désactivé en production (sécurité)"
else
    warning "display_errors n'est pas explicitement désactivé"
fi

# Vérifier la désactivation du listing de répertoires
if grep -q "Options -Indexes" docker/php/Dockerfile; then
    success "Le listing de répertoires est désactivé (sécurité)"
else
    warning "Le listing de répertoires n'est pas désactivé"
fi

# Vérifier la présence de stages multiples
if grep -q "FROM.*AS base" docker/php/Dockerfile && grep -q "FROM.*AS production" docker/php/Dockerfile; then
    success "Multi-stage build configuré (optimisation)"
else
    warning "Multi-stage build non détecté"
fi

echo ""
echo "=== 4. Vérification des Fichiers de Configuration ==="
echo ""

# Vérifier la présence de .env.example
if [ -f .env.example ]; then
    success ".env.example existe"
else
    error ".env.example est manquant"
fi

# Vérifier la présence de .env.prod.example
if [ -f .env.prod.example ]; then
    success ".env.prod.example existe"
else
    error ".env.prod.example est manquant"
fi

# Vérifier que .env est dans .gitignore
if grep -q "^\.env$" .gitignore; then
    success ".env est dans .gitignore (sécurité)"
else
    error ".env n'est pas dans .gitignore"
fi

echo ""
echo "================================================"
echo "Vérification Terminée"
echo "================================================"
