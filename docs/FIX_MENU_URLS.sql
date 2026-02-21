-- Correction des URLs de fonctionnalités malformées
-- Les URLs qui manquent "page=" sont corrigées

-- Corriger les URLs qui commencent par "?=" au lieu de "?page="
UPDATE fonctionnalites 
SET url_fonctionnalite = REPLACE(url_fonctionnalite, '?=', '?page=') 
WHERE url_fonctionnalite LIKE '?=%' 
  AND url_fonctionnalite NOT LIKE '?page=%';

-- Afficher les entrées corrigées pour vérification
SELECT id_fonctionnalite, code_fonctionnalite, url_fonctionnalite 
FROM fonctionnalites 
WHERE url_fonctionnalite LIKE '?page=%';
