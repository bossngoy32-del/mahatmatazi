<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connexion à la base de données
require_once $_SERVER['DOCUMENT_ROOT'] . '/Portfolio-Prof/config.php';

$message = "";
$status = "";

// Définition du dossier de stockage des médias
$dossier_cible = $_SERVER['DOCUMENT_ROOT'] . '/Portfolio-Prof/uploads/';
if (!file_exists($dossier_cible)) {
    mkdir($dossier_cible, 0777, true);
}

// =========================================================================
// TRAITEMENT DE LA GALERIE MEDIAS (TRIPLE FORMAT)
// =========================================================================
if (isset($_POST['submit_galerie'])) {
    $titre_media = trim($_POST['titre_media']);
    $type_media = $_POST['type_media']; 
    
    if (!empty($titre_media)) {
        if ($type_media === 'image' && isset($_FILES['image_galerie']) && $_FILES['image_galerie']['error'] === 0) {
            $extension = strtolower(pathinfo($_FILES['image_galerie']['name'], PATHINFO_EXTENSION));
            $extensions_autorisees = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($extension, $extensions_autorisees)) {
                $nom_img = 'galerie_' . time() . '_' . uniqid() . '.' . $extension;
                if (move_uploaded_file($_FILES['image_galerie']['tmp_name'], $dossier_cible . $nom_img)) {
                    $chemin_bd = 'uploads/' . $nom_img;
                    $stmt = $pdo->prepare("INSERT INTO galerie (titre, media_url) VALUES (?, ?)");
                    $stmt->execute([$titre_media, $chemin_bd]);
                    $status = "success_galerie_add";
                }
            } else { $status = "error_extension"; }
        } 
        elseif ($type_media === 'video_local' && isset($_FILES['video_galerie_locale']) && $_FILES['video_galerie_locale']['error'] === 0) {
            $extension = strtolower(pathinfo($_FILES['video_galerie_locale']['name'], PATHINFO_EXTENSION));
            $extensions_autorisees = ['mp4', 'webm', 'ogg'];
            if (in_array($extension, $extensions_autorisees)) {
                $nom_vid = 'video_' . time() . '_' . uniqid() . '.' . $extension;
                if (move_uploaded_file($_FILES['video_galerie_locale']['tmp_name'], $dossier_cible . $nom_vid)) {
                    $chemin_bd = 'uploads/' . $nom_vid;
                    $stmt = $pdo->prepare("INSERT INTO galerie (titre, media_url) VALUES (?, ?)");
                    $stmt->execute([$titre_media, $chemin_bd]);
                    $status = "success_galerie_add";
                }
            } else { $status = "error_extension_video"; }
        }
        elseif ($type_media === 'video_youtube' && !empty($_POST['video_url'])) {
            $url = trim($_POST['video_url']);
            $video_id = "";
            
            // Analyse flexible acceptant (watch?v=, tu.be/, embed/, shorts/)
            if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?|shorts)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match)) {
                $video_id = $match[1];
            }
            
            if (!empty($video_id)) {
                $chemin_bd = "https://www.youtube.com/embed/" . $video_id;
                $stmt = $pdo->prepare("INSERT INTO galerie (titre, media_url) VALUES (?, ?)");
                $stmt->execute([$titre_media, $chemin_bd]);
                $status = "success_galerie_add";
            } else { 
                $status = "error_youtube_link"; 
            }
        }
    }
}

// Suppression Galerie
if (isset($_GET['action']) && $_GET['action'] === 'delete_img' && isset($_GET['id'])) {
    $id_img = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT media_url FROM galerie WHERE id = ?");
    $stmt->execute([$id_img]);
    $img = $stmt->fetch();
    if ($img && strpos($img['media_url'], 'uploads/') === 0) {
        $chemin_fichier_physique = $_SERVER['DOCUMENT_ROOT'] . '/Portfolio-Prof/' . $img['media_url'];
        if (file_exists($chemin_fichier_physique)) { unlink($chemin_fichier_physique); }
    }
    $delete = $pdo->prepare("DELETE FROM galerie WHERE id = ?");
    $delete->execute([$id_img]);
    $status = "success_galerie_delete";
}

// Récupération des images
$images = $pdo->query("SELECT * FROM galerie ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de la Galerie - Album Multimédia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card-box { background: #ffffff; border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); padding: 30px; margin-top: 30px; }
        .galerie-img-admin { height: 110px; object-fit: cover; width: 100%; border-radius: 6px; }
        .media-container-admin { position: relative; width: 100%; height: 110px; background: #0f172a; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #fff; }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark mb-0"><i class="fas fa-camera text-danger"></i> Album Multimédia Autonome</h2>
        <div class="d-flex gap-2">
            <a href="index.php" class="btn btn-primary btn-sm"><i class="fas fa-home"></i> Voir le site (Accueil)</a>
            <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left"></i> Retour Dashboard</a>
        </div>
    </div>

    <div class="card-box">
        <div class="row">
            <div class="col-md-4 border-end pe-md-4">
                <h5 class="fw-bold text-dark mb-3">Ajouter un nouveau média</h5>
                <form method="POST" action="admin_galerie.php" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Légende / Titre</label>
                        <input type="text" name="titre_media" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold d-block">Type de ressource</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="type_media" id="type_img" value="image" checked onclick="toggleFields()">
                            <label class="form-check-label small" for="type_img">Image</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="type_media" id="type_vid_loc" value="video_local" onclick="toggleFields()">
                            <label class="form-check-label small" for="type_vid_loc">Vidéo Locale</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="type_media" id="type_vid_yt" value="video_youtube" onclick="toggleFields()">
                            <label class="form-check-label small" for="type_vid_yt">YouTube</label>
                        </div>
                    </div>
                    
                    <div id="field_image" class="mb-3">
                        <label class="form-label small">Fichier Image</label>
                        <input type="file" name="image_galerie" class="form-control form-control-sm" accept="image/*">
                    </div>
                    <div id="field_video_local" class="mb-3 d-none">
                        <label class="form-label small">Fichier Vidéo (MP4, WebM)</label>
                        <input type="file" name="video_galerie_locale" class="form-control form-control-sm" accept="video/*">
                    </div>
                    <div id="field_video_youtube" class="mb-3 d-none">
                        <label class="form-label small">Lien URL YouTube (N'importe quel format)</label>
                        <input type="text" name="video_url" class="form-control form-control-sm" placeholder="Collez le lien de la vidéo ici...">
                    </div>
                    
                    <button type="submit" name="submit_galerie" class="btn btn-danger btn-sm w-100 mt-2">Uploader le média</button>
                </form>
            </div>
            
            <div class="col-md-8 ps-md-4" style="max-height: 500px; overflow-y: auto;">
                <h5 class="fw-bold text-dark mb-3">Médias enregistrés</h5>
                <div class="row row-cols-1 row-cols-sm-3 g-2">
                    <?php if(empty($images)): ?>
                        <div class="col-12 w-100 text-center py-4 text-muted">Aucun média dans l'album.</div>
                    <?php else: ?>
                        <?php foreach($images as $img): ?>
                            <div class="col">
                                <div class="card h-100 border-0 shadow-sm bg-light">
                                    <?php 
                                    $url = $img['media_url']; 
                                    $ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
                                    if(strpos($url, 'http') === 0): ?>
                                        <div class="media-container-admin"><i class="fab fa-youtube text-danger fa-xl"></i></div>
                                    <?php elseif(in_array($ext, ['mp4', 'webm', 'ogg'])): ?>
                                        <div class="media-container-admin"><i class="fas fa-video text-info fa-xl"></i></div>
                                    <?php else: ?>
                                        <img src="<?= htmlspecialchars($url) ?>" class="galerie-img-admin" alt="Img">
                                    <?php endif; ?>
                                    <div class="p-2 text-center">
                                        <p class="small text-truncate mb-1 fw-semibold"><?= htmlspecialchars($img['titre']) ?></p>
                                        <button onclick="confirmerSuppressionImg(<?= $img['id'] ?>)" class="btn btn-link text-danger btn-sm p-0 border-0 text-decoration-none"><i class="fas fa-trash-alt"></i> Retirer</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function toggleFields() {
    const typeImage = document.getElementById('type_img').checked;
    const typeVidLoc = document.getElementById('type_vid_loc').checked;
    const typeVidYt = document.getElementById('type_vid_yt').checked;

    document.getElementById('field_image').classList.toggle('d-none', !typeImage);
    document.getElementById('field_video_local').classList.toggle('d-none', !typeVidLoc);
    document.getElementById('field_video_youtube').classList.toggle('d-none', !typeVidYt);
}

function confirmerSuppressionImg(id) {
    Swal.fire({
        title: 'Retirer ce média ?',
        text: "Le fichier physique et l'enregistrement en base de données seront effacés.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Oui, retirer',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) { 
            window.location.href = 'admin_galerie.php?action=delete_img&id=' + id; 
        }
    });
}
</script>

<?php if(!empty($status)): ?>
<script>
    const stat = "<?= $status ?>";
    if(stat === 'success_galerie_add') { Swal.fire('Média téléversé !', 'L\'élément a bien été intégré à l\'album.', 'success'); }
    else if(stat === 'success_galerie_delete') { Swal.fire('Élément retiré !', 'Le média a été nettoyé avec succès.', 'success'); }
    else if(stat === 'error_extension') { Swal.fire('Erreur Extension', 'Format image non valide (JPG, JPEG, PNG, GIF requis).', 'error'); }
    else if(stat === 'error_extension_video') { Swal.fire('Erreur Vidéo', 'Seuls les formats MP4, WebM et OGG sont acceptés.', 'error'); }
    else if(stat === 'error_youtube_link') { Swal.fire('Lien non valide', 'Impossible de valider l\'identifiant de la vidéo YouTube. Vérifiez le format.', 'error'); }
    
    if (window.history.replaceState) { window.history.replaceState(null, null, window.location.pathname); }
</script>
<?php endif; ?>

</body>
</html>