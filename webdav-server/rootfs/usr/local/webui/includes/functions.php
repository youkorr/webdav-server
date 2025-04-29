<?php
/**
 * Récupère la configuration de l'add-on
 */
function getConfig() {
    $config_file = '/data/options.json';
    if (!file_exists($config_file)) {
        return [];
    }
    
    $config = json_decode(file_get_contents($config_file), true);
    return $config ?: [];
}

/**
 * Récupère la liste des serveurs WebDAV externes
 */
function getExternalWebDAVServers() {
    $config = getConfig();
    return $config['external_webdav'] ?? [];
}

/**
 * Sauvegarde la configuration
 */
function saveConfig($config) {
    $config_file = '/data/options.json';
    return file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
}

/**
 * Vérifie la connexion à un serveur WebDAV
 */
function checkWebDAVConnection($url, $username, $password) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PROPFIND");
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Depth: 1']);
    
    if (!empty($username)) {
        curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
    }
    
    $result = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $status >= 200 && $status < 300;
}

/**
 * Liste les fichiers d'un répertoire local
 */
function listLocalFiles($dir) {
    $base_path = getConfig()['base_path'] ?? '/config/www/partage/shared';
    $dir = rtrim($dir, '/');
    $path = $base_path . $dir;
    
    if (!is_dir($path)) {
        return ['error' => 'Directory not found'];
    }
    
    $files = [];
    $d = dir($path);
    while (false !== ($entry = $d->read())) {
        if ($entry == '.' || $entry == '..') continue;
        
        $fullpath = $path . '/' . $entry;
        $files[] = [
            'name' => $entry,
            'path' => $dir . '/' . $entry,
            'is_dir' => is_dir($fullpath),
            'size' => is_file($fullpath) ? filesize($fullpath) : 0,
            'modified' => date("Y-m-d H:i:s", filemtime($fullpath))
        ];
    }
    $d->close();
    
    return $files;
}

/**
 * Liste les fichiers d'un serveur WebDAV externe
 */
function listExternalFiles($id, $dir) {
    $servers = getExternalWebDAVServers();
    if (!isset($servers[$id])) {
        return ['error' => 'Server not found'];
    }
    
    $server = $servers[$id];
    $url = rtrim($server['url'], '/') . $dir;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PROPFIND");
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Depth: 1']);
    
    if (!empty($server['username'])) {
        curl_setopt($ch, CURLOPT_USERPWD, $server['username'] . ":" . $server['password']);
    }
    
    $result = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($status < 200 || $status >= 300) {
        return ['error' => 'Failed to list files: HTTP ' . $status];
    }
    
    // Analyser la réponse XML
    $xml = new SimpleXMLElement($result);
    $xml->registerXPathNamespace('d', 'DAV:');
    
    $files = [];
    foreach ($xml->xpath('//d:response') as $response) {
        $href = (string)$response->xpath('d:href')[0];
        $name = basename($href);
        
        // Ignorer l'entrée du répertoire courant
        if ($name === '' || $href === $dir . '/') continue;
        
        $is_dir = count($response->xpath('.//d:collection')) > 0;
        $size = $response->xpath('.//d:getcontentlength');
        $modified = $response->xpath('.//d:getlastmodified');
        
        $files[] = [
            'name' => $name,
            'path' => $href,
            'is_dir' => $is_dir,
            'size' => $size ? (int)(string)$size[0] : 0,
            'modified' => $modified ? date("Y-m-d H:i:s", strtotime((string)$modified[0])) : ''
        ];
    }
    
    return $files;
}

/**
 * Formate la taille d'un fichier pour l'affichage
 */
function formatSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}
