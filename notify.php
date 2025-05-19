<?php
require __DIR__ . '/vendor/autoload.php';

use Google\Cloud\Firestore\FirestoreClient;

$projectId = 'certificatcommune';
$firestore = new FirestoreClient(['projectId' => $projectId]);

// Log the raw input for debugging
$input = file_get_contents("php://input");
file_put_contents("log.txt", "✅ Notification received:\n$input\n", FILE_APPEND);

try {
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['custom_data']['certificat_id'])) {
        throw new Exception("Données de paiement invalides ou certificat_id manquant");
    }

    $certificatId = $data['custom_data']['certificat_id'];
    $status = $data['status']; // Vérifiez la structure exacte de la réponse PayDunya

    if ($status === 'completed') {
        $docRef = $firestore->collection('certificats')->document($certificatId);
        $docRef->update([['path' => 'estPaye', 'value' => true]]);
        
        file_put_contents("log.txt", "✅ Certificat $certificatId marqué comme payé\n", FILE_APPEND);
        echo "Paiement confirmé pour certificat: $certificatId";
    } else {
        file_put_contents("log.txt", "⚠ Paiement non complet pour $certificatId. Statut: $status\n", FILE_APPEND);
        echo "Paiement non complet. Statut: $status";
    }
} catch (Exception $e) {
    file_put_contents("log.txt", "❌ Erreur: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo "Erreur: " . $e->getMessage();
}