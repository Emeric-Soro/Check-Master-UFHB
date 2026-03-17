<?php
if (isset($_GET['page']) && in_array($_GET['page'], ['verification_candidatures_soutenance', 'verification_candidatures'], true)) {

    require_once __DIR__ . '/../../app/controllers/VerificationRapportsController.php';
    require_once __DIR__ . '/../../app/models/Approuver.php';
    $controller = new VerificationRapportsController();

    // Gérer les actions PHP (formulaires)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['valider']) && isset($_POST['id_rapport'])) {
            $_POST['commentaire'] = $_POST['commentaire'] ?? '';
            $_POST['id_rapport'] = (int) $_POST['id_rapport'];
            $result = $controller->validerRapport();
            $_SESSION['message'] = $result['message'] ?? '';
            $_SESSION['message_type'] = $result['success'] ? 'success' : 'error';
            header('Location: ?page=verification_candidatures_soutenance');
            exit;
        }
        if (isset($_POST['rejeter']) && isset($_POST['id_rapport'])) {
            $_POST['commentaire'] = $_POST['commentaire'] ?? '';
            $_POST['id_rapport'] = (int) $_POST['id_rapport'];
            $result = $controller->rejeterRapport();
            $_SESSION['message'] = $result['message'] ?? '';
            $_SESSION['message_type'] = $result['success'] ? 'success' : 'error';
            header('Location: ?page=verification_candidatures_soutenance');
            exit;
        }
    }

    // Gérer les actions AJAX
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'valider':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $id_rapport = $_POST['id_rapport'] ?? 0;
                    $commentaire = $_POST['commentaire'] ?? '';

                    header('Content-Type: application/json');
                    if ($id_rapport && $commentaire) {
                        $_POST['id_rapport'] = (int) $id_rapport;
                        $result = $controller->validerRapport();
                        if ($result['success']) {
                            http_response_code(200);
                        } else {
                            http_response_code(400);
                        }
                        echo json_encode($result);
                    } else {
                        http_response_code(422);
                        echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
                    }
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
                }
                exit;

            case 'rejeter':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $id_rapport = $_POST['id_rapport'] ?? 0;
                    $commentaire = $_POST['commentaire'] ?? '';

                    header('Content-Type: application/json');
                    if ($id_rapport && $commentaire) {
                        $_POST['id_rapport'] = (int) $id_rapport;
                        $result = $controller->rejeterRapport();
                        if ($result['success']) {
                            http_response_code(200);
                        } else {
                            http_response_code(400);
                        }
                        echo json_encode($result);
                    } else {
                        http_response_code(422);
                        echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
                    }
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
                }
                exit;

            case 'detail':
                if (!canView('verification_candidatures_soutenance')) {
                    http_response_code(403);
                    echo '<div class="text-red-500 text-center py-8">Accès refusé.</div>';
                    exit;
                }
                $id = $_GET['id'] ?? 0;
                if ($id) {
                    $rapport = $controller->getRapportDetail($id);
                    if ($rapport) {
                        echo '<div class="space-y-6">';
                        echo '<div class="grid grid-cols-2 gap-4">';
                        echo '<div><strong>Étudiant:</strong> ' . htmlspecialchars($rapport->nom_etu . ' ' . $rapport->prenom_etu) . '</div>';
                        echo '<div><strong>Email:</strong> ' . htmlspecialchars($rapport->email_etu) . '</div>';
                        echo '<div><strong>Nom du rapport:</strong> ' . htmlspecialchars($rapport->nom_rapport) . '</div>';
                        echo '<div><strong>Thème:</strong> ' . htmlspecialchars($rapport->theme_rapport) . '</div>';
                        echo '<div><strong>Date de dépôt:</strong> ' . ($rapport->date_depot ? date('d/m/Y H:i', strtotime($rapport->date_depot)) : 'Non déposé') . '</div>';
                        echo '<div><strong>Statut:</strong> <span class="font-semibold ' . ($rapport->statut_rapport === 'valider' ? 'text-green-600' : ($rapport->statut_rapport === 'rejeter' ? 'text-red-600' : ($rapport->statut_rapport === 'en_cours' ? 'text-blue-600' : 'text-yellow-600'))) . '">' . ($rapport->statut_rapport === 'valider' ? 'Validé' : ($rapport->statut_rapport === 'rejeter' ? 'Rejeté' : ($rapport->statut_rapport === 'en_cours' ? 'En cours' : 'En attente'))) . '</span></div>';
                        echo '</div>';

                        // Bouton de téléchargement PDF
                        echo '<div class="mt-4">';
                        echo '<a href="?page=verification_candidatures_soutenance&action=telecharger_pdf&id=' . $rapport->id_rapport . '" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">';
                        echo '<i class="fas fa-download mr-2"></i> Télécharger le rapport en PDF';
                        echo '</a>';
                        echo '</div>';

                        // Afficher le contenu du rapport s'il existe
                        $chemin = $rapport->chemin_fichier;
                        if (empty($chemin)) {
                            $chemin = 'rapport_' . $rapport->id_rapport . '.html';
                        }
                        $fichierContenu = __DIR__ . "/../uploads/rapports/" . $chemin;
                        if (file_exists($fichierContenu)) {
                            echo '<div class="mt-6">';
                            echo '<h4 class="font-bold text-lg mb-3 text-gray-800">Contenu du rapport:</h4>';
                            echo '<div class="border border-gray-200 rounded-lg p-4 max-h-96 overflow-y-auto bg-gray-50">';
                            echo file_get_contents($fichierContenu);
                            echo '</div>';
                            echo '</div>';
                        } else {
                            echo '<div class="mt-6 text-gray-500 bg-gray-100 p-4 rounded-lg">Aucun contenu disponible pour ce rapport.</div>';
                        }

                        // Section de décision - d'abord vérifier s'il y a déjà une approbation formelle
                        $approbations = Approuver::getByRapport($rapport->id_rapport);
                        if (!empty($approbations)) {
                            // Prendre la dernière approbation (ordre asc dans la requête, donc last = plus récente)
                            $lastApprob = end($approbations);
                            $decision = isset($lastApprob['decision']) ? $lastApprob['decision'] : ($lastApprob->decision ?? null);
                            if ($decision === 'approuve') {
                                echo '<div class="mt-8 pt-6 border-t border-gray-200">';
                                echo '<h4 class="font-bold text-lg mb-4 text-gray-800">Décision</h4>';
                                echo '<div class="p-4 bg-green-50 text-green-700 rounded-lg inline-block font-semibold">';
                                echo '<i class="fas fa-check mr-2"></i> Rapport approuvé';
                                echo '</div>';
                                echo '</div>';
                            } elseif ($decision === 'desapprouve' || $decision === 'rejete') {
                                echo '<div class="mt-8 pt-6 border-t border-gray-200">';
                                echo '<h4 class="font-bold text-lg mb-4 text-gray-800">Décision</h4>';
                                echo '<div class="p-4 bg-red-50 text-red-700 rounded-lg inline-block font-semibold">';
                                echo '<i class="fas fa-times mr-2"></i> Rapport désapprouvé';
                                echo '</div>';
                                echo '</div>';
                            } else {
                                // Si décision non reconnue, afficher un résumé générique
                                echo '<div class="mt-8 pt-6 border-t border-gray-200">';
                                echo '<h4 class="font-bold text-lg mb-4 text-gray-800">Décision</h4>';
                                echo '<div class="p-4 bg-gray-50 text-gray-700 rounded-lg inline-block font-semibold">';
                                echo '<i class="fas fa-info-circle mr-2"></i> Décision enregistrée: ' . htmlspecialchars($decision);
                                echo '</div>';
                                echo '</div>';
                            }
                        } else {
                            // Pas d'approbation formelle -> afficher évaluations si présentes, sinon proposer les boutons
                            $decisions = $controller->getDecisionsEvaluation($rapport->id_rapport);
                            if (empty($decisions)) {
                                echo '<div class="mt-8 pt-6 border-t border-gray-200">';
                                echo '<h4 class="font-bold text-lg mb-4 text-gray-800">Décision d\'évaluation</h4>';
                                echo '<div class="flex gap-4">';
                                echo '<button onclick="validerRapport(' . $rapport->id_rapport . ')" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium flex items-center">';
                                echo '<i class="fas fa-check mr-2"></i> Approuver le rapport';
                                echo '</button>';
                                echo '<button onclick="rejeterRapport(' . $rapport->id_rapport . ')" class="px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium flex items-center">';
                                echo '<i class="fas fa-times mr-2"></i> Désapprouver le rapport';
                                echo '</button>';
                                echo '</div>';
                                echo '</div>';
                            } else {
                                echo '<div class="mt-8 pt-6 border-t border-gray-200">';
                                echo '<h4 class="font-bold text-lg mb-4 text-gray-800">Évaluations du rapport</h4>';
                                foreach ($decisions as $decision) {
                                    $decisionClass = 'text-blue-600';
                                    $decisionIcon = 'fa-comment';
                                    $decisionLabel = 'Évalué';
                                    echo '<div class="mb-3 p-3 bg-gray-50 rounded-lg">';
                                    echo '<div class="flex items-center gap-2 mb-2">';
                                    echo '<i class="fas ' . $decisionIcon . ' ' . $decisionClass . '"></i>';
                                    echo '<span class="font-semibold ' . $decisionClass . '">' . $decisionLabel . '</span>';
                                    echo '<span class="text-sm text-gray-500">par ' . htmlspecialchars($decision->nom_evaluateur . ' ' . $decision->prenom_evaluateur) . ' (' . $decision->fonction_evaluateur . ')</span>';
                                    echo '<span class="text-sm text-gray-500">le ' . date('d/m/Y H:i', strtotime($decision->date_evaluation)) . '</span>';
                                    echo '</div>';
                                    if (!empty($decision->commentaire)) {
                                        echo '<p class="text-gray-700 text-sm">' . htmlspecialchars($decision->commentaire) . '</p>';
                                    }
                                    if (!empty($decision->note)) {
                                        echo '<p class="text-sm text-gray-600 mt-1"><strong>Note:</strong> ' . $decision->note . '/20</p>';
                                    }
                                    echo '</div>';
                                }
                                echo '</div>';
                            }
                        }

                        echo '</div>';
                    } else {
                        echo '<div class="text-red-500 text-center py-8">Rapport non trouvé.</div>';
                    }
                } else {
                    echo '<div class="text-red-500 text-center py-8">ID du rapport manquant.</div>';
                }
                exit;

            case 'telecharger_pdf':
                if (!canView('verification_candidatures_soutenance')) {
                    http_response_code(403);
                    echo '<div style="text-align:center;padding:50px;font-family:Arial,sans-serif;"><h2 style="color:#e74c3c;">Accès refusé</h2><p>Vous n\'avez pas l\'autorisation d\'accéder à ce fichier.</p></div>';
                    exit;
                }
                $id = $_GET['id'] ?? 0;
                if ($id) {
                    $rapport = $controller->getRapportDetail($id);
                    if ($rapport) {
                        // Récupérer le contenu du rapport
                        $chemin = $rapport->chemin_fichier;
                        if (empty($chemin)) {
                            $chemin = 'rapport_' . $rapport->id_rapport . '.html';
                        }
                        $fichierContenu = __DIR__ . "/../uploads/rapports/" . $chemin;

                        if (file_exists($fichierContenu)) {
                            $contenu = file_get_contents($fichierContenu);

                            // PdfGeneratorService : autoloadé par Composer PSR-4
                            $pdfGen = new \App\Services\Document\PdfGeneratorService(
                                __DIR__ . '/../../storage',
                                __DIR__ . '/../../public/assets/img/logo.png'
                            );
                            $pdf = $pdfGen->createDocument('P', 'A4', 'Rapport - ' . htmlspecialchars($rapport->nom_rapport));
                            $pdf->AddPage();
                            $pdfGen->writeHtml($pdf, $html);

                            // Générer le nom du fichier
                            $nomFichier = 'rapport_' . $rapport->nom_rapport . '_' . date('Y-m-d_H-i-s') . '.pdf';

                            // Envoyer le PDF
                            $pdf->Output($nomFichier, 'D');
                        } else {
                            header('Content-Type: text/html; charset=utf-8');
                            echo '<div style="text-align: center; padding: 50px; font-family: Arial, sans-serif;">';
                            echo '<h2 style="color: #e74c3c;">Erreur</h2>';
                            echo '<p>Le fichier du rapport n\'existe pas.</p>';
                            echo '<a href="javascript:history.back()" style="color: #3498db; text-decoration: none;">← Retour</a>';
                            echo '</div>';
                        }
                    } else {
                        header('Content-Type: text/html; charset=utf-8');
                        echo '<div style="text-align: center; padding: 50px; font-family: Arial, sans-serif;">';
                        echo '<h2 style="color: #e74c3c;">Erreur</h2>';
                        echo '<p>Rapport non trouvé.</p>';
                        echo '<a href="javascript:history.back()" style="color: #3498db; text-decoration: none;">← Retour</a>';
                        echo '</div>';
                    }
                } else {
                    header('Content-Type: text/html; charset=utf-8');
                    echo '<div style="text-align: center; padding: 50px; font-family: Arial, sans-serif;">';
                    echo '<h2 style="color: #e74c3c;">Erreur</h2>';
                    echo '<p>ID du rapport manquant.</p>';
                    echo '<a href="javascript:history.back()" style="color: #3498db; text-decoration: none;">← Retour</a>';
                    echo '</div>';
                }
                exit;
        }
    }

    // Action par défaut : afficher la liste
    $controller->index();
}
