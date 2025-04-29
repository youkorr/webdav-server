<?php
include 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <h1 class="display-4 mb-4">WebDAV Explorer</h1>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h2 class="h5 mb-0">Serveur Local</h2>
                </div>
                <div class="card-body">
                    <p>Accédez à vos fichiers stockés dans Home Assistant</p>
                    <a href="explorer.php?source=local" class="btn btn-primary">
                        <i class="fas fa-folder-open me-2"></i>Explorer
                    </a>
                    <a href="/webdav/" class="btn btn-outline-secondary ms-2">
                        <i class="fas fa-external-link-alt me-2"></i>Accès WebDAV direct
                    </a>
                </div>
            </div>
            
            <?php foreach (getExternalWebDAVServers() as $index => $server): ?>
                <?php if ($server['enabled']): ?>
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h2 class="h5 mb-0"><?php echo htmlspecialchars($server['name']); ?></h2>
                    </div>
                    <div class="card-body">
                        <p>Connecté à: <?php echo htmlspecialchars($server['url']); ?></p>
                        <a href="explorer.php?source=external&id=<?php echo $index; ?>" class="btn btn-info">
                            <i class="fas fa-folder-open me-2"></i>Explorer
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
            
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h2 class="h5 mb-0">Configuration</h2>
                </div>
                <div class="card-body">
                    <p>Gérez vos connexions WebDAV externes</p>
                    <a href="config.php" class="btn btn-secondary">
                        <i class="fas fa-cog me-2"></i>Paramètres
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'includes/footer.php';
?>

