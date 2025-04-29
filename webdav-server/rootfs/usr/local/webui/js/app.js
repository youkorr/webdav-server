$(document).ready(function() {
    // Gestion de la section d'upload
    $('#uploadFilesBtn').click(function() {
        $('#uploadArea').toggle();
        $('#createFolderForm').hide();
    });
    
    $('#browseFilesBtn').click(function() {
        $('#fileInput').click();
    });
    
    // Gestion du glisser-déposer
    $('#uploadArea').on('dragover', function(e) {
        e.preventDefault();
        $(this).addClass('active');
    });
    
    $('#uploadArea').on('dragleave', function(e) {
        e.preventDefault();
        $(this).removeClass('active');
    });
    
    $('#uploadArea').on('drop', function(e) {
        e.preventDefault();
        $(this).removeClass('active');
        
        var files = e.originalEvent.dataTransfer.files;
        handleFiles(files);
    });
    
    $('#fileInput').change(function() {
        handleFiles(this.files);
    });
    
    function handleFiles(files) {
        if (files.length === 0) return;
        
        $('#uploadProgress').show();
        $('#progressBar').css('width', '0%');
        $('#uploadStatus').text('Préparation...');
        
        uploadFiles(files, 0);
    }
    
    function uploadFiles(files, index) {
        if (index >= files.length) {
            $('#uploadStatus').text('Upload terminé!');
            setTimeout(function() {
                window.location.reload();
            }, 1000);
            return;
        }
        
        var file = files[index];
        var formData = new FormData();
        formData.append('file', file);
        formData.append('path', currentPath);
        
        $.ajax({
            url: 'api.php?action=upload',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        var percent = (e.loaded / e.total) * 100;
                        $('#progressBar').css('width', percent + '%');
                        $('#uploadStatus').text('Uploading ' + file.name + ' (' + Math.round(percent) + '%)');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                // Passer au fichier suivant
                uploadFiles(files, index + 1);
            },
            error: function(xhr, status, error) {
                $('#uploadStatus').text('Erreur: ' + error);
            }
        });
    }
    
    // Gestion de la création de dossier
    $('#createFolderBtn').click(function() {
        $('#createFolderForm').toggle();
        $('#uploadArea').hide();
    });
    
    $('#cancelCreateFolder').click(function() {
        $('#createFolderForm').hide();
        $('#folderNameInput').val('');
    });
    
    $('#confirmCreateFolder').click(function() {
        var folderName = $('#folderNameInput').val().trim();
        if (folderName === '') return;
        
        $.ajax({
            url: 'api.php?action=createFolder',
            type: 'POST',
            data: {
                path: currentPath,
                name: folderName
            },
            success: function(response) {
                window.location.reload();
            },
            error: function(xhr, status, error) {
                alert('Erreur: ' + error);
            }
        });
    });
    
    // Gestion de la suppression de fichiers/dossiers
    $('.delete-file').click(function() {
        fileToDelete = $(this).data('file');
        fileToDeleteIsDir = $(this).data('is-dir') === 1;
        
        var fileName = fileToDelete.split('/').pop();
        $('#deleteFileName').text(fileName + (fileToDeleteIsDir ? ' (dossier)' : ''));
        
        var modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
        modal.show();
    });
    
    $('#confirmDelete').click(function() {
        $.ajax({
            url: 'api.php?action=delete',
            type: 'POST',
            data: {
                path: fileToDelete,
                isDir: fileToDeleteIsDir
            },
            success: function(response) {
                window.location.reload();
            },
            error: function(xhr, status, error) {
                alert('Erreur: ' + error);
            }
        });
    });
    
    // Gestion du renommage de fichiers/dossiers
    $('.rename-file').click(function() {
        fileToRename = $(this).data('file');
        var currentName = $(this).data('name');
        
        $('#newFileName').val(currentName);
        
        var modal = new bootstrap.Modal(document.getElementById('renameModal'));
        modal.show();
    });
    
    $('#confirmRename').click(function() {
        var newName = $('#newFileName').val().trim();
        if (newName === '') return;
        
        $.ajax({
            url: 'api.php?action=rename',
            type: 'POST',
            data: {
                path: fileToRename,
                newName: newName
            },
            success: function(response) {
                window.location.reload();
            },
            error: function(xhr, status, error) {
                alert('Erreur: ' + error);
            }
        });
    });
});
