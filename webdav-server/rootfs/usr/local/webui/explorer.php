<?php
include 'includes/header.php';

$source = $_GET['source'] ?? 'local';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$current_dir = $_GET['dir'] ?? '/';

// Normaliser le chemin du répertoire courant
$current_dir = '/' . trim($current_dir, '/');
if ($current_dir !== '/') {
    $current_dir .= '/';
}

// Déterminer le titre en fonction de la source
$title = ($source === 'local') ? 'Serveur Local' : '';
if ($source === 'external') {
    $servers = getExternalWebDAVServers();
    $title = isset($servers[$id]) ? $servers[$id]['name'] : 'Serveur Externe';
}

// Récupérer la liste des fichiers
$files = [];
if ($source === 'local') {
    $files = listLocalFiles($current_dir);
} else if ($source === 'external') {
    $files = listExternalFiles($id, $current_dir);
}

// Trier les fichiers (répertoires d'abord, puis par nom)
usort($files, function($a, $b) {
    if ($a['is_dir'] !== $b['is_dir']) {
        return $b['is_dir'] ? 1 : -1;
    }
    return strcasecmp($a['name'], $b['name']);
});

// Générer les éléments du fil d'Ariane
$breadcrumb_items = [];
$path_parts = explode('/', trim($current_dir, '/'));
$build_path = '';

$breadcrumb_items[] = [
    'name' => 'Root',
    'path' => '/'
];

foreach ($path_parts as $part) {
    if (empty($part)) continue;
    $build_path .= '/' . $part;
    $breadcrumb_items[] = [
        'name' => $part,
        'path' => $build_path
    ];
}
?>

<div class="container mt-4">
    <h1><?php echo htmlspecialchars($title); ?></h1>
    
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <?php foreach ($breadcrumb_items as $index => $item): ?>
                <?php if ($index === count($breadcrumb_items) - 1): ?>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($item['name']); ?></li>
                <?php else: ?>
                    <li class="breadcrumb-item">
                        <a href="?source=<?php echo $source; ?>&id=<?php echo $id; ?>&dir=<?php echo urlencode($item['path']); ?>">
                            <?php echo htmlspecialchars($item['name']); ?>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ol>
    </nav>
    
    <?php if ($source === 'local'): ?>
    <div class="mb-4">
        <div class="row">
            <div class="col-md-6">
                <button id="uploadFilesBtn" class="btn btn-primary">
                    <i class="fas fa-upload me-2"></i>Upload de fichiers
                </button>
                <button id="createFolderBtn" class="btn btn-success ms-2">
                    <i class="fas fa-folder-plus me-2"></i>Nouveau dossier
                </button>
            </div>
        </div>
        
        <div id="uploadArea" class="upload-area mt-3" style="display: none;">
            <div class="upload-message">
                <i class="fas fa-cloud-upload-alt"></i> 
                Glissez-déposez vos fichiers ici ou cliquez pour sélectionner
            </div>
            <input type="file" id="fileInput" multiple style="display: none;">
            <button id="browseFilesBtn" class="btn btn-outline-primary">Parcourir</button>
            
            <div id="uploadProgress" class="mt-3" style="display: none;">
                <div class="progress">
                    <div id="progressBar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                </div>
                <div id="uploadStatus" class="text-center"></div>
            </div>
        </div>
        
        <div id="createFolderForm" class="mt-3" style="display: none;">
            <div class="input-group">
                <input type="text" id="folderNameInput" class="form-control" placeholder="Nom du nouveau dossier">
                <button id="confirmCreateFolder" class="btn btn-success">Créer</button>
                <button id="cancelCreateFolder" class="btn btn-outline-secondary">Annuler</button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (isset($files['error'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($files['error']); ?></div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Taille</th>
                        <th>Modifié le</th>
                        <?php if ($source === 'local'): ?>
                        <th class="actions-column">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($current_dir != '/'): ?>
                    <tr>
                        <td>
                            <?php 
                            // Calculer le répertoire parent
                            $parent_dir = dirname($current_dir);
                            if ($parent_dir === '/' || $parent_dir === '\\' || $parent_dir === '.') {
                                $parent_dir = '/';
                            }
                            ?>
                            <a href="?source=<?php echo $source; ?>&id=<?php echo $id; ?>&dir=<?php echo urlencode($parent_dir); ?>">
                                <i class="fas fa-level-up-alt file-icon"></i> ..
                            </a>
                        </td>
                        <td>-</td>
                        <td>-</td>
                        <?php if ($source === 'local'): ?>
                        <td>-</td>
                        <?php endif; ?>
                    </tr>
                    <?php endif; ?>
                    
                    <?php foreach ($files as $file): ?>
                    <tr>
                        <td>
                            <?php if ($file['is_dir']): ?>
                                <a href="?source=<?php echo $source; ?>&id=<?php echo $id; ?>&dir=<?php echo urlencode($file['path']); ?>">
                                    <i class="fas fa-folder file-icon folder-icon"></i>
                                    <?php echo htmlspecialchars($file['name']); ?>
                                </a>
                            <?php else: ?>
                                <?php 
                                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                                $icon_class = 'file-icon-default';
                                $icon = 'file';
                                
                                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'svg'])) {
                                    $icon_class = 'file-icon-image';
                                    $icon = 'file-image';
                                } else if (in_array($ext, ['doc', 'docx', 'pdf', 'txt', 'rtf'])) {
                                    $icon_class = 'file-icon-document';
                                    $icon = 'file-alt';
                                } else if (in_array($ext, ['mp4', 'avi', 'mov', 'wmv'])) {
                                    $icon_class = 'file-icon-video';
                                    $icon = 'file-video';
                                } else if (in_array($ext, ['mp3', 'wav', 'ogg'])) {
                                    $icon_class = 'file-icon-audio';
                                    $icon = 'file-audio';
                                } else if (in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz'])) {
                                    $icon_class = 'file-icon-archive';
                                    $icon = 'file-archive';
                                } else if (in_array($ext, ['html', 'css', 'js', 'php', 'py', 'java'])) {
                                    $icon_class = 'file-icon-code';
                                    $icon = 'file-code';
                                }
                                
                                $download_url = '';
                                if ($source === 'local') {
                                    $download_url = '/webdav' . $file['path'];
                                } else if ($source === 'external') {
                                    $servers = getExternalWebDAVServers();
                                    if (isset($servers[$id])) {
                                        $download_url = $servers[$id]['url'] . $file['path'];
                                    }
                                }
                                ?>
                                <a href="<?php echo htmlspecialchars($download_url); ?>" target="_blank">
                                    <i class="fas fa-<?php echo $icon; ?> file-icon <?php echo $icon_class; ?>"></i>
                                    <?php echo htmlspecialchars($file['name']); ?>
                                </a>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $file['is_dir'] ? '-' : formatSize($file['size']); ?></td>
                        <td><?php echo htmlspecialchars($file['modified']); ?></td>
                        <?php if ($source === 'local'): ?>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-danger action-button delete-file" 
                                    data-file="<?php echo htmlspecialchars($file['path']); ?>"
                                    data-is-dir="<?php echo $file['is_dir'] ? '1' : '0'; ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-primary action-button rename-file"
                                    data-file="<?php echo htmlspecialchars($file['path']); ?>"
                                    data-name="<?php echo htmlspecialchars($file['name']); ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Modales -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Êtes-vous sûr de vouloir supprimer <span id="deleteFileName"></span> ?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Supprimer</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="renameModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Renommer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="newFileName" class="form-label">Nouveau nom :</label>
                    <input type="text" class="form-control" id="newFileName">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="confirmRename">Renommer</button>
            </div>
        </div>
    </div>
</div>

<script>
// Variables globales pour les opérations sur les fichiers
var currentPath = "<?php echo $current_dir; ?>";
var currentSource = "<?php echo $source; ?>";
var currentId = <?php echo $id; ?>;

// Stockage temporaire pour les opérations modales
var fileToDelete = "";
var fileToDeleteIsDir = false;
var fileToRename = "";
var fileNewName = "";
</script>

<?php
include 'includes/footer.php';
?>
