<div class="receipt-box">
        <div class="header">
            <div>
                <!-- <img src="/images/FHB.png" alt="Logo Université"> -->
            </div>
            <div class="university-info">
                <h2>UNIVERSITE FELIX HOUPHOUET-BOIGNY</h2>
                <p>FILIERES PROFESSIONNALISEES, UFR MI</p>
                <p>22 B.P. 582 Abidjan 22</p>
                <p>Tél. (Fax): 27 22 41 05 74 / 27 22 48 01 80</p>
                <p>Cel: 07 07 89 94 26 / 07 07 69 15 04</p>
            </div>
            <div>
                <!-- <img src="/images/logo_mathInfo_fond_blanc.png" alt="Logo MathInfo"> -->
            </div>
        </div>

        <h3 style="text-align: center; margin-bottom: 20px;">REÇU <span class="receipt-number">Nº
                <?php echo $numeroRecu; ?></span></h3>

        <table class="info-table">
            <tr>
                <td>Reçu de M/Mme :</td>
                <td><?php echo htmlspecialchars($nomEtudiant . ' ' . $prenomEtudiant); ?></td>
            </tr>
            <tr>
                <td>La somme de :</td>
                <td><?php echo htmlspecialchars(number_format($montant, 0, ',', ' ')); ?> FCFA</td>
            </tr>
            <tr>
                <td colspan="2" class="amount-text">(en toutes lettres :
                    <?php echo ReceiptUtils::numberToWords($montant); ?> FCFA)</td>
            </tr>
            <tr>
                <td>En règlement de :</td>
                <td>Scolarité Année Académique <?php echo htmlspecialchars($anneeAcademique); ?> -
                    <?php echo htmlspecialchars($nomNiveau); ?></td>
            </tr>
            <tr>
                <td>Année d'Études :</td>
                <td><?php echo htmlspecialchars($nomNiveau); ?></td>
            </tr>
        </table>

        <table class="footer-table">
            <tr>
                <td>Méthode de paiement: <?php echo htmlspecialchars($methodePaiement); ?></td>
                <td>Date: <?php echo htmlspecialchars(date('d/m/Y', strtotime($dateVersement))); ?></td>
            </tr>
            <tr>
                <td>Reste à payer :</td>
                <td><?php echo htmlspecialchars(number_format($resteAPayer, 0, ',', ' ')); ?> FCFA</td>
            </tr>
            <tr>
                <td>Montant total scolarité :</td>
                <td><?php echo htmlspecialchars(number_format($montantScolarite, 0, ',', ' ')); ?> FCFA
                </td>
            </tr>
            <tr>
                <td>Montant total payé :</td>
                <td><?php echo htmlspecialchars(number_format($montantPaye, 0, ',', ' ')); ?> FCFA</td>
            </tr>
        </table>

        <div class="signature">
            <p>Signature et cachet</p>
        </div>

        <p class="note">N.B.: Aucun remboursement n'est possible après versement</p>
    </div>