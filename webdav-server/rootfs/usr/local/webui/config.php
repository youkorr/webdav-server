<?php
include 'includes/header.php';

// Traiter les modifications de configuration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_server'])) {
        $server_id = (int)$_POST['server_id'];
        $name = $_POST['name'];
        $url = $_POST['url'];
        $username = $_POST['username'];
        $password = $_POST['password'];
        $enabled = isset($_POST['enabled']);
        
        $config = getConfig();
        
        // Mettre à jour le serveur existant ou en ajouter un nouveau
        if ($server_id >= 0 && isset($config['external_webdav'][$server_id])) {
            $config['external_webdav'][$server_id] = [
                'name' => $name,
                'url' => $url,
                'username' => $username,
                'password' => $password,
                'enabled' => $enabled
            ];
        } else {
            $config['external_webdav'][] = [
                'name' => $name,
                'url' => $url,
                'username' => $username,
                'password' => $password,
                'enabled' => $enabled
            ];
        }
        
        // Sauvegarder la configuration
        saveConfig($config);
        
        $message = "Serveur WebDAV mis à jour avec succès";
    } else if (isset($_POST['delete_server'])) {
        $server_id = (int)$_POST['server_id'];
        
        $config = getConfig();
        
        // Supprimer le serveur
        if (isset($config['external_webdav'][$server_id])) {
            array_splice($config['external_webdav'], $server_id, 1);
            saveConfig($config);
            $message = "Serveur WebDAV supprimé avec succès";
        }
    }
}

$config = getConfig();
$external_webdav = $config['external_webdav'] ?? [];
?>

<div class="container mt-4">
    <h1>Configuration</h1>
    
    <?php if (isset($message)): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>
    
    <div class="row mt-4">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h5 mb-0">Serveur Local</h2>
                </div>
                <div class="card-body">
                    <p><strong>Chemin: </strong><?php echo htmlspecialchars($config['base_path'] ?? '/config/www/partage/shared'); ?></p>
                    <p><strong>Accès direct: </strong><a href="/webdav/" target="_blank">/webdav/</a></p>
                    <p><strong>Authentification: </strong><?php echo ($config['auth_required'] ?? true) ? 'Requise' : 'Non requise'; ?></p>
                    <p><strong>Mode: </strong><?php echo ($config['read_only'] ?? false) ? 'Lecture seule' : 'Lecture/écriture'; ?></p>
                    <p class="text-muted">Pour modifier ces paramètres, utilisez la configuration de l'add-on dans Home Assistant.</p>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0">Serveurs WebDAV Externes</h2>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#serverModal" data-server-id="-1">
                        <i class="fas fa-plus"></i> Ajouter
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($external_webdav)): ?>
                    <p class="text-muted">Aucun serveur externe configuré.</p>
                    <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($external_webdav as $index => $server): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-1"><?php echo htmlspecialchars($server['name']); ?></h5>
                                <div>
                                    <button class="btn btn-sm btn-outline-primary edit-server" 
                                            data-server-id="<?php echo $index; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger delete-server" 
                                            data-server-id="<?php echo $index; ?>"
                                            data-server-name="<?php echo htmlspecialchars($server['name']); ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <p class="mb-1"><?php echo htmlspecialchars($server['url']); ?></p>
                            <small class="text-muted">
                                Statut: <?php echo $server['enabled'] ? 'Activé' : 'Désactivé'; ?> | 
                                Authentification: <?php echo !empty($server['username']) ? 'Oui' : 'Non'; ?>
                            </small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour ajouter/éditer un serveur WebDAV -->
<div class="modal fade" id="serverModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="serverModalTitle">Ajouter un serveur WebDAV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="server_id" id="serverId" value="-1">
                    
                    <div class="mb-3">
                        <label for="serverName" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="serverName" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="serverUrl" class="form-label">URL</label>
                        <input type="url" class="form-control" id="serverUrl" name="url" required
                               placeholder="http://192.168.1.x:80">
                        <div class="form-text">URL complète du serveur WebDAV, incluant le protocole et le port si nécessaire</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="serverUsername" class="form-label">Nom d'utilisateur</label>
                        <input type="text" class="form-control" id="serverUsername" name="username">
                    </div>
                    
                    <div class="mb-3">
                        <label for="serverPassword" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="serverPassword" name="password">
                    </div>
                    
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="serverEnabled" name="enabled" checked>
                        <label class="form-check-label" for="serverEnabled">Activer ce serveur</label>
                    </div>
                    
                    <div class="mt-3">
                        <button type="button" id="testConnection" class="btn btn-outline-primary">
                            <i class="fas fa-plug"></i> Tester la connexion
                        </button>
                        <span id="connectionStatus" class="ms-2"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" name="update_server">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteServerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Êtes-vous sûr de vouloir supprimer le serveur <span id="deleteServerName"></span> ?
            </div>
            <div class="modal-footer">
                <form method="post">
                    <input type="hidden" name="server_id" id="deleteServerId" value="">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="delete_server" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Charger les données du serveur pour l'édition
    $('.edit-server').click(function() {
        var serverId = $(this).data('server-id');
        var servers = <?php echo json_encode($external_webdav); ?>;
        var server = servers[serverId];
        
        $('#serverModalTitle').text('Modifier le serveur WebDAV');
        $('#serverId').val(serverId);
        $('#serverName').val(server.name);
        $('#serverUrl').val(server.url);
        $('#serverUsername').val(server.username);
        $('#serverPassword').val(server.password);
        $('#serverEnabled').prop('checked', server.enabled);
        
        var modal = new bootstrap.Modal(document.getElementById('serverModal'));
        modal.show();
    });
    
    // Ouvrir le modal de suppression
    $('.delete-server').click(function() {
        var serverId = $(this).data('server-id');
        var serverName = $(this).data('server-name');
        
        $('#deleteServerId').val(serverId);
        $('#deleteServerName').text(serverName);
        
        var modal = new bootstrap.Modal(document.getElementById('deleteServerModal'));
        modal.show();
    });
    
    // Tester la connexion WebDAV
    $('#testConnection').click(function() {
        var url = $('#serverUrl').val();
        var username = $('#serverUsername').val();
        var password = $('#serverPassword').val();
        
        $('#connectionStatus').html('<i class="fas fa-spinner fa-spin"></i> Test en cours...');
        
        $.ajax({
            url: 'api.php?action=connectWebDav',
            type: 'POST',
            data: {
                url: url,
                username: username,
                password: password
            },
            success: function(response) {
                $('#connectionStatus').html('<span class="text-success"><i class="fas fa-check"></i> Connexion réussie</span>');
            },
            error: function(xhr, status, error) {
                $('#connectionStatus').html('<span class="text-danger"><i class="fas fa-times"></i> Échec de la connexion</span>');
            }
        });
    });
});
</script>

<?php
include 'includes/footer.php';
?>
