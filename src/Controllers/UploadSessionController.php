<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\AuthProviderInterface;
use App\Support\View;
use PDO;

class UploadSessionController
{
    public function __construct(
        private PDO $pdo,
        private AuthProviderInterface $authProvider,
        private View $view
    ) {}

    public function create(): void
    {
        header('Content-Type: application/json');
        $user = $this->authProvider->currentUser();
        $token = bin2hex(random_bytes(16));
        $userId = $user ? (int)$user->id : null;
        $expiraEl = date('Y-m-d H:i:s', time() + 900);

        $stmt = $this->pdo->prepare("
            INSERT INTO upload_sessions (token, usuario_id, estado, expira_el) 
            VALUES (:token, :usuario_id, 'pendiente', :expira_el)
        ");
        $stmt->execute([
            ':token' => $token,
            ':usuario_id' => $userId,
            ':expira_el' => $expiraEl
        ]);

        $uploadUrl = app_url("/cargar?token={$token}", true);

        echo json_encode([
            'success' => true,
            'token' => $token,
            'upload_url' => $uploadUrl
        ]);
        exit;
    }

    public function status(): void
    {
        header('Content-Type: application/json');
        $token = trim((string)($_GET['token'] ?? ''));

        if (empty($token)) {
            echo json_encode(['status' => 'invalid']);
            exit;
        }

        $stmt = $this->pdo->prepare("
            SELECT * FROM upload_sessions WHERE token = :token LIMIT 1
        ");
        $stmt->execute([':token' => $token]);
        $session = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$session) {
            echo json_encode(['status' => 'invalid']);
            exit;
        }

        if (strtotime($session->expira_el) < time()) {
            echo json_encode(['status' => 'expired']);
            exit;
        }

        if ($session->estado === 'completado') {
            $files = !empty($session->archivos_json) ? json_decode($session->archivos_json, true) : [];
            echo json_encode([
                'status' => 'completed',
                'files' => $files
            ]);
            exit;
        }

        echo json_encode(['status' => 'pending']);
        exit;
    }

    public function showUploadPage(): void
    {
        $token = trim((string)($_GET['token'] ?? ''));
        if (empty($token)) {
            die("Token de carga no especificado.");
        }

        $stmt = $this->pdo->prepare("SELECT * FROM upload_sessions WHERE token = :token LIMIT 1");
        $stmt->execute([':token' => $token]);
        $session = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$session) {
            die("La sesión de carga es inválida.");
        }

        if (strtotime($session->expira_el) < time()) {
            die("La sesión de carga ha expirado. Por favor generá un nuevo código QR en tu pantalla.");
        }

        $alreadyCompleted = ($session->estado === 'completado');

        echo $this->view->render('upload/cargar', [
            'token' => $token,
            'alreadyCompleted' => $alreadyCompleted,
            'files' => $alreadyCompleted && !empty($session->archivos_json) ? json_decode($session->archivos_json, true) : []
        ], null);
    }

    public function handleUpload(): void
    {
        $token = trim((string)($_POST['token'] ?? ''));
        if (empty($token)) {
            flash('error', 'Token de carga no especificado.');
            redirect("/cargar?token={$token}");
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM upload_sessions WHERE token = :token LIMIT 1");
        $stmt->execute([':token' => $token]);
        $session = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$session || strtotime($session->expira_el) < time()) {
            flash('error', 'Sesión de carga inválida o expirada.');
            redirect("/cargar?token={$token}");
            return;
        }

        $files = $_FILES['archivos'] ?? $_FILES['archivo'] ?? [];
        $filesToProcess = $this->normalizeFilesArray($files);

        if (empty($filesToProcess)) {
            flash('error', 'No se seleccionó ningún archivo para subir.');
            redirect("/cargar?token={$token}");
            return;
        }

        $baseDir = dirname(dirname(__DIR__)) . '/storage/temp_uploads/' . $token;
        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0755, true);
        }

        $savedFilesMeta = [];
        $validMimes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

        foreach ($filesToProcess as $file) {
            $mime = mime_content_type($file['tmp_name']) ?: $file['type'];
            if (!in_array($mime, $validMimes, true)) {
                continue;
            }

            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $hashName = bin2hex(random_bytes(8)) . ($ext ? ".{$ext}" : '');
            $destPath = $baseDir . '/' . $hashName;

            if (move_uploaded_file($file['tmp_name'], $destPath)) {
                $savedFilesMeta[] = [
                    'original_name' => $file['name'],
                    'temp_path' => 'storage/temp_uploads/' . $token . '/' . $hashName,
                    'mime_type' => $mime,
                    'size_bytes' => $file['size']
                ];
            }
        }

        if (empty($savedFilesMeta)) {
            flash('error', 'Ninguno de los archivos subidos es válido (Formatos permitidos: JPG, PNG, WebP, PDF).');
            redirect("/cargar?token={$token}");
            return;
        }

        $existingFiles = !empty($session->archivos_json) ? json_decode($session->archivos_json, true) : [];
        if (!is_array($existingFiles)) {
            $existingFiles = [];
        }

        $allFiles = array_merge($existingFiles, $savedFilesMeta);

        $stmt = $this->pdo->prepare("
            UPDATE upload_sessions 
            SET estado = 'completado', archivos_json = :json 
            WHERE token = :token
        ");
        $stmt->execute([
            ':json' => json_encode($allFiles),
            ':token' => $token
        ]);

        $totalCount = count($allFiles);
        flash('success', "¡Archivo(s) subidos con éxito! Total recibidos en la PC: {$totalCount}. Podés seguir subiendo más si lo deseás.");
        redirect("/cargar?token={$token}");
    }

    private function normalizeFilesArray(array $files): array
    {
        $normalized = [];
        if (isset($files['name']) && is_string($files['name'])) {
            if (($files['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_NO_FILE && !empty($files['tmp_name'])) {
                $normalized[] = $files;
            }
            return $normalized;
        }

        if (isset($files['name']) && is_array($files['name'])) {
            foreach ($files['name'] as $idx => $name) {
                if (($files['error'][$idx] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_NO_FILE && !empty($files['tmp_name'][$idx])) {
                    $normalized[] = [
                        'name' => $files['name'][$idx],
                        'type' => $files['type'][$idx],
                        'tmp_name' => $files['tmp_name'][$idx],
                        'error' => $files['error'][$idx],
                        'size' => $files['size'][$idx],
                    ];
                }
            }
        }
        return $normalized;
    }
}
