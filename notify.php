<?php
require __DIR__ . '/vendor/autoload.php';

use Google\Cloud\Firestore\FirestoreClient;

$projectId = 'certificatcommune';
$firestore = new FirestoreClient(['projectId' => $projectId]);

$input = file_get_contents("php://input");
file_put_contents("log.txt", "✅ Notification received:\n$input\n", FILE_APPEND);

try {
    $payload = json_decode($input, true);

    if (
        !$payload || 
        !isset($payload['data']['custom_data']['certificat_id']) ||
        !isset($payload['data']['status'])
    ) {
        throw new Exception("Données de paiement invalides ou certificat_id manquant");
    }

    $certificatId = $payload['data']['custom_data']['certificat_id'];
    $status = $payload['data']['status'];

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
