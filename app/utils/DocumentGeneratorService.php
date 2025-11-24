<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Mpdf\Mpdf;

class DocumentGeneratorService
{
    /**
     * Génère un PDF à partir d'une vue HTML
     *
     * @param string $viewPath Chemin vers le fichier de vue (relatif à la racine ou absolu)
     * @param array $data Données à passer à la vue
     * @param string $filename Nom du fichier de sortie (ex: 'document.pdf')
     * @return void
     */
    public function generatePdfFromView($viewPath, $data, $filename)
    {
        // Extraction des données pour qu'elles soient accessibles dans la vue
        extract($data);

        // Démarrer la mise en mémoire tampon
        ob_start();

        // Inclure la vue
        // Si le chemin n'est pas absolu, on suppose qu'il est relatif à la racine du projet
        if (!file_exists($viewPath)) {
            // Tentative avec le chemin relatif standard
            $viewPath = __DIR__ . '/../../' . $viewPath;
        }

        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            ob_end_clean();
            throw new Exception("La vue n'a pas été trouvée : " . $viewPath);
        }

        // Récupérer le contenu généré
        $html = ob_get_clean();

        // Instancier mPDF
        try {
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P'
            ]);

            // Écrire le HTML dans le PDF
            $mpdf->WriteHTML($html);

            // Sortie du PDF (Téléchargement)
            $mpdf->Output($filename, 'D');

        } catch (\Mpdf\MpdfException $e) {
            // Gérer les erreurs mPDF
            echo "Erreur lors de la génération du PDF : " . $e->getMessage();
        }
    }
}
