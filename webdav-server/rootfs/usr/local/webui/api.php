<?php
include 'includes/functions.php';

// Vérifier qu'une action est spécifiée
if (!isset($_GET['action'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Action not specified']);
    exit;
}

$action = $_GET['action'];

// Actions prises en charge
switch ($action) {
    case 'upload':
        handleUpload();
        break;
    case 'createFolder':
        handleCreateFolder();
        break;
    case 'delete':
        handleDelete();
        break;
    case 'rename':
        handleRename();
        break;
    case 'connectWebDav':
        handleConnectWebDav();
        break;
    default:
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'Invalid action']);
        exit;
}

/**
 * Gère l'upload de fichiers
 */
function handleUpload() {
    // Vérifier qu'un fichier a été envoyé
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'No file uploaded or upload error']);
        exit;
    }
    
    $path = $_POST['path'] ?? '/';
    $config = getConfig();
    $base_path = $config['base_path'] ?? '/config/www/partage/shared';
    
    // Construire le chemin complet du fichier
    $target_dir = rtrim($base_path, '/') . '/' . trim($path, '/');
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $target_file = $target_dir . '/' . basename($_FILES['file']['name']);
    
    // Déplacer le fichier téléchargé vers sa destination finale
    if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
        echo json_encode(['success' => true]);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Failed to save file']);
    }
}

/**
 * Gère la création d'un nouveau dossier
 */
function handleCreateFolder() {
    $path = $_POST['path'] ?? '/';
    $folderName = $_POST['name'] ?? '';
    
    if (empty($folderName)) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'Folder name is required']);
        exit;
    }
    
    $config = getConfig();
    $base_path = $config['base_path'] ?? '/config/www/partage/shared';
    
    // Construire le chemin complet du nouveau dossier
    $new_dir = rtrim($base_path, '/') . '/' . trim($path, '/') . '/' . $folderName;
    
    // Créer le dossier
    if (mkdir($new_dir, 0777, true)) {
        echo json_encode(['success' => true]);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Failed to create folder']);
    }
}

/**
 * Gère la suppression d'un fichier ou d'un dossier
 */
function handleDelete() {
    $path = $_POST['path'] ?? '';
    $isDir = ($_POST['isDir'] ?? 'false') === '1';
    
    if (empty($path)) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'Path is required']);
        exit;
    }
    
    $config = getConfig();
    $base_path = $config['base_path'] ?? '/config/www/partage/shared';
    
    // Construire le chemin complet
    $full_path = rtrim($base_path, '/') . '/' . ltrim($path, '/');
    
    $success = false;
    
    if ($isDir) {
        // Fonction récursive pour supprimer un dossier et son contenu
        function rrmdir($dir) {
            if (is_dir($dir)) {
                $objects = scandir($dir);
                foreach ($objects as $object) {
                    if ($object != "." && $object != "..") {
                        if (is_dir($dir . "/" . $object)) {
                            rrmdir($dir . "/" . $object);
                        } else {
                            unlink($dir . "/" . $object);
                        }
                    }
                }
                return rmdir($dir);
            }
            return false;
        }
        
        $success = rrmdir($full_path);
    } else {
        $success = unlink($full_path);
    }
    
    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Failed to delete']);
    }
}

/**
 * Gère le renommage d'un fichier ou d'un dossier
 */
function handleRename() {
    $path = $_POST['path'] ?? '';
    $newName = $_POST['newName'] ?? '';
    
    if (empty($path) || empty($newName)) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'Path and new name are required']);
        exit;
    }
    
    $config = getConfig();
    $base_path = $config['base_path'] ?? '/config/www/partage/shared';
    
    // Construire les chemins complets
    $full_path = rtrim($base_path, '/') . '/' . ltrim($path, '/');
    $dir_path = dirname($full_path);
    $new_path = $dir_path . '/' . $newName;
    
    if (rename($full_path, $new_path)) {
        echo json_encode(['success' => true]);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Failed to rename']);
    }
}

/**
 * Teste la connexion à un serveur WebDAV externe
 */
function handleConnectWebDav() {
    $url = $_POST['url'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($url)) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'URL is required']);
        exit;
    }
    
    $isConnected = checkWebDAVConnection($url, $username, $password);
    
    if ($isConnected) {
        echo json_encode(['success' => true]);
    } else {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'Failed to connect to WebDAV server']);
    }
}
