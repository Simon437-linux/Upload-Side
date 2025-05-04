<?php
// upload.php - JPEG-Konvertierung mit Imagick für alle Bildtypen
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');
error_reporting(E_ALL);

function sendJson(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function getRequestData(): array {
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($ct, 'application/json') !== false) {
        $d = json_decode(file_get_contents('php://input'), true);
        return is_array($d) ? $d : [];
    }
    return $_POST;
}

function ensureUserDir(string $user): string {
    $dir = __DIR__ . '/uploads/' . $user;
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        sendJson(['error' => 'Verzeichnis konnte nicht erstellt werden.'], 500);
    }
    return $dir;
}

function listUserFiles(string $dir): array {
    $all = array_diff(scandir($dir), ['.', '..']);
    return array_values(array_filter((array)$all, fn($f) => $f !== 'password.json'));
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        $data = getRequestData();
        $action = $data['action'] ?? '';
        $user = trim($data['username'] ?? '');
        $pwd  = $data['password'] ?? '';

        // Login / Registration
        if ($action === 'login') {
            if (!$user || !$pwd) sendJson(['error'=>'Username und Passwort erforderlich'],400);
            $dd = ensureUserDir($user);
            $passFile = "$dd/password.json";
            if (!file_exists($passFile)) {
                file_put_contents($passFile, json_encode(['password'=>password_hash($pwd,PASSWORD_DEFAULT)]));
                sendJson(['success'=>true]);
            }
            $stored = json_decode(file_get_contents($passFile), true);
            if (password_verify($pwd, $stored['password'] ?? '')) sendJson(['success'=>true]);
            sendJson(['error'=>'Falsches Passwort.'],401);
        }

        if (!$user) sendJson(['error'=>'Username is required'],400);
        $dir = ensureUserDir($user);
        $existing = listUserFiles($dir);
        $existingCount = count($existing);

        // Dateien prüfen
        if (empty($_FILES['images']['name'][0])) {
            sendJson(['error'=>'Keine Dateien zum Hochladen gefunden'],400);
        }

        $files = $_FILES['images'];
        $uploadedCount = 0;
        foreach ($files['tmp_name'] as $i => $tmp) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
            $name = $files['name'][$i];
            $base = pathinfo($name, PATHINFO_FILENAME);
            $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $target = "$dir/{$base}.jpg";

            // Nur Imagick kann HEIC und alle Formate sicher konvertieren
            if (extension_loaded('imagick')) {
                try {
                    $img = new Imagick($tmp);
                    $img->setImageFormat('jpeg');
                    $img->setImageCompressionQuality(90);
                    $img->writeImage($target);
                    $img->clear(); $img->destroy();
                    $uploadedCount++;
                } catch (Exception $e) {
                    continue;
                }
            } else {
                // GD-Fallback nur für Standard-Bildformate
                $dataBin = file_get_contents($tmp);
                if ($src = imagecreatefromstring($dataBin)) {
                    imagejpeg($src, $target, 90);
                    imagedestroy($src);
                    $uploadedCount++;
                } else {
                    sendJson(['error' => 'Server-Konfiguration benötigt Imagick für diesen Dateityp'],415);
                }
            }
        }

        if ($existingCount + $uploadedCount > 20) {
            sendJson(['error'=>'Du kannst nicht mehr als 20 Bilder hochladen.'],400);
        }

        $total = $existingCount + $uploadedCount;
        sendJson(['success'=>true,'count'=>$total]);

    case 'GET':
        $user = trim($_GET['username'] ?? '');
        if (!$user) sendJson(['error'=>'Username is required'],400);
        $dir = __DIR__ . '/uploads/' . $user;
        if (!is_dir($dir)) sendJson(['count'=>0,'files'=>[]]);
        $files = listUserFiles($dir);
        sendJson(['count'=>count($files),'files'=>$files]);

    case 'DELETE':
        parse_str(file_get_contents('php://input'), $d);
        $user = trim($d['username'] ?? '');
        $file = basename($d['file'] ?? '');
        if (!$user||!$file) sendJson(['error'=>'Parameter fehlen'],400);
        $path = __DIR__ . "/uploads/$user/$file";
        if (file_exists($path) && unlink($path)) sendJson(['success'=>true]);
        sendJson(['error'=>'File not found'],404);

    default:
        sendJson(['error'=>'Ungültige Anfrage'],405);
}
?>
