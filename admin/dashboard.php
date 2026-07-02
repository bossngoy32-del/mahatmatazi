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
// 1. TRAITEMENT : BIOGRAPHIE / PROFIL + CV + PHOTO DE PROFIL
// =========================================================================
if (isset($_POST['submit_profil'])) {
    $nom = trim($_POST['nom_complet']);
    $titre = trim($_POST['titre']);
    $email = trim($_POST['email']);
    $linkedin = trim($_POST['linkedin']);
    $bio = trim($_POST['biographie']);
    
    // Vérification de l'existence d'un enregistrement existant
    $check = $pdo->query("SELECT id, cv_url, photo_url FROM profil LIMIT 1")->fetch();
    $chemin_cv_bd = $check ? $check['cv_url'] : null;
    $chemin_photo_bd = $check ? $check['photo_url'] : null;

    // A. Gestion du téléversement du CV (PDF obligatoire)
    if (isset($_FILES['cv_pdf']) && $_FILES['cv_pdf']['error'] === 0) {
        $extension = strtolower(pathinfo($_FILES['cv_pdf']['name'], PATHINFO_EXTENSION));
        if ($extension === 'pdf') {
            if ($chemin_cv_bd) {
                $ancien_cv = $_SERVER['DOCUMENT_ROOT'] . '/Portfolio-Prof/' . $chemin_cv_bd;
                if (file_exists($ancien_cv)) { unlink($ancien_cv); }
            }
            $nom_cv = 'cv_professeur_' . time() . '.pdf';
            if (move_uploaded_file($_FILES['cv_pdf']['tmp_name'], $dossier_cible . $nom_cv)) {
                $chemin_cv_bd = 'uploads/' . $nom_cv;
            }
        } else { $status = "error_cv_extension"; }
    }

    // B. Gestion du téléversement de la Photo de Profil (JPG, JPEG, PNG, GIF)
    if (isset($_FILES['photo_profil']) && $_FILES['photo_profil']['error'] === 0) {
        $extension = strtolower(pathinfo($_FILES['photo_profil']['name'], PATHINFO_EXTENSION));
        $extensions_photo = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($extension, $extensions_photo)) {
            if ($chemin_photo_bd) {
                $ancienne_photo = $_SERVER['DOCUMENT_ROOT'] . '/Portfolio-Prof/' . $chemin_photo_bd;
                if (file_exists($ancienne_photo)) { unlink($ancienne_photo); }
            }
            $nom_photo = 'avatar_' . time() . '.' . $extension;
            if (move_uploaded_file($_FILES['photo_profil']['tmp_name'], $dossier_cible . $nom_photo)) {
                $chemin_photo_bd = 'uploads/' . $nom_photo;
            }
        } else { $status = "error_photo_extension"; }
    }
    
    // C. Validation et mise à jour en Base de données
    if ($status !== "error_cv_extension" && $status !== "error_photo_extension") {
        if ($check) {
            $stmt = $pdo->prepare("UPDATE profil SET nom_complet = ?, titre = ?, email = ?, linkedin = ?, biographie = ?, cv_url = ?, photo_url = ? WHERE id = ?");
            $stmt->execute([$nom, $titre, $email, $linkedin, $bio, $chemin_cv_bd, $chemin_photo_bd, $check['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO profil (nom_complet, titre, email, linkedin, biographie, cv_url, photo_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nom, $titre, $email, $linkedin, $bio, $chemin_cv_bd, $chemin_photo_bd]);
        }
        $status = "success_profil";
    }
}

// =========================================================================
// 2. TRAITEMENT : PUBLICATIONS SCIENTIFIQUES (AJOUT / MODIFICATION / SUPPRESSION)
// =========================================================================
if (isset($_POST['submit_publication'])) {
    $type = $_POST['type_pub'];
    $titre_pub = trim($_POST['titre_pub']);
    $editeur = trim($_POST['editeur_revue']);
    $annee = (int)$_POST['annee_publication'];
    $lien = trim($_POST['lien_url']);
    $desc = trim($_POST['description_pub']);
    $id_pub = isset($_POST['id_pub']) ? (int)$_POST['id_pub'] : 0;
    
    if (!empty($titre_pub)) {
        if ($id_pub > 0) {
            // Modification d'une publication existante
            $stmt = $pdo->prepare("UPDATE publications SET type = ?, titre = ?, editeur_revue = ?, annee_publication = ?, lien_url = ?, description = ? WHERE id = ?");
            $stmt->execute([$type, $titre_pub, $editeur, $annee, $lien, $desc, $id_pub]);
            $status = "success_pub_edit";
        } else {
            // Ajout classique d'une nouvelle publication
            $stmt = $pdo->prepare("INSERT INTO publications (type, titre, editeur_revue, annee_publication, lien_url, description) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$type, $titre_pub, $editeur, $annee, $lien, $desc]);
            $status = "success_pub";
        }
    }
}

// Suppression d'une publication
if (isset($_GET['action']) && $_GET['action'] === 'delete_pub' && isset($_GET['id'])) {
    $id_pub_del = (int)$_GET['id'];
    $delete = $pdo->prepare("DELETE FROM publications WHERE id = ?");
    $delete->execute([$id_pub_del]);
    $status = "success_pub_delete";
}

// =========================================================================
// 3. TRAITEMENT : ENSEIGNEMENTS / COURS (AVEC INTEGRITE SHA-256)
// =========================================================================
if (isset($_POST['submit_activite'])) {
    $type_act = $_POST['type_act'];
    $titre_act = trim($_POST['titre_act']);
    $institution = trim($_POST['institution_evenement']);
    $date_ev = $_POST['date_evenement'];
    $desc_act = trim($_POST['description_act']);
    $chemin_doc_bd = null;
    $sha256_hash = null;
    
    if (isset($_FILES['support_cours']) && $_FILES['support_cours']['error'] === 0) {
        $extension = strtolower(pathinfo($_FILES['support_cours']['name'], PATHINFO_EXTENSION));
        $extensions_autorisees = ['pdf', 'ppt', 'pptx'];
        
        if (in_array($extension, $extensions_autorisees)) {
            $nom_doc = 'support_' . time() . '_' . uniqid() . '.' . $extension;
            $chemin_complet_serveur = $dossier_cible . $nom_doc;
            if (move_uploaded_file($_FILES['support_cours']['tmp_name'], $chemin_complet_serveur)) {
                $chemin_doc_bd = 'uploads/' . $nom_doc;
                $sha256_hash = hash_file('sha256', $chemin_complet_serveur);
            }
        } else { $status = "error_doc_extension"; }
    }
    
    if ($status !== "error_doc_extension" && !empty($titre_act)) {
        $stmt = $pdo->prepare("INSERT INTO activites (type, titre, institution_evenement, date_evenement, description, document_url, document_sha256) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$type_act, $titre_act, $institution, $date_ev, $desc_act, $chemin_doc_bd, $sha256_hash]);
        $status = "success_act";
    }
}

// Suppression Enseignement
if (isset($_GET['action']) && $_GET['action'] === 'delete_act' && isset($_GET['id'])) {
    $id_act = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT document_url FROM activites WHERE id = ?");
    $stmt->execute([$id_act]);
    $act = $stmt->fetch();
    if ($act && !empty($act['document_url'])) {
        $fichier_physique = $_SERVER['DOCUMENT_ROOT'] . '/Portfolio-Prof/' . $act['document_url'];
        if (file_exists($fichier_physique)) { unlink($fichier_physique); }
    }
    $delete = $pdo->prepare("DELETE FROM activites WHERE id = ?");
    $delete->execute([$id_act]);
    $status = "success_act_delete";
}

// =========================================================================
// 4. TRAITEMENT DE LA GALERIE MEDIAS (TRIPLE FORMAT)
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
            $url = $_POST['video_url'];
            $video_id = "";
            if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match)) { $video_id = $match[1]; }
            if (!empty($video_id)) {
                $chemin_bd = "https://www.youtube.com/embed/" . $video_id;
                $stmt = $pdo->prepare("INSERT INTO galerie (titre, media_url) VALUES (?, ?)");
                $stmt->execute([$titre_media, $chemin_bd]);
                $status = "success_galerie_add";
            } else { $status = "error_youtube_link"; }
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

// Suppression Message Livre d'or
if (isset($_GET['action']) && $_GET['action'] === 'delete_com' && isset($_GET['id'])) {
    $id_com = (int)$_GET['id'];
    $pdo->prepare("DELETE FROM commentaires WHERE id = ?")->execute([$id_com]);
    $status = "success_com_delete";
}

// Récupération des données mises à jour pour l'affichage du tableau de bord
$profil = $pdo->query("SELECT * FROM profil LIMIT 1")->fetch();
$publications = $pdo->query("SELECT * FROM publications ORDER BY id DESC")->fetchAll();
$activites = $pdo->query("SELECT * FROM activites ORDER BY date_evenement DESC")->fetchAll();
$images = $pdo->query("SELECT * FROM galerie ORDER BY id DESC")->fetchAll();
$commentaires = $pdo->query("SELECT * FROM commentaires ORDER BY cree_le DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Administratif</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { background: #1e293b; min-height: 100vh; color: #fff; box-shadow: 4px 0 10px rgba(0,0,0,0.05); }
        .sidebar .nav-link { color: #94a3b8; font-weight: 500; padding: 12px 20px; border-radius: 6px; margin: 4px 10px; transition: all 0.2s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: #334155; color: #fff; }
        .sidebar .nav-link i { width: 24px; }
        .main-content { padding: 40px; }
        .card-box { background: #ffffff; border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); padding: 30px; margin-bottom: 30px; }
        .galerie-img-admin { height: 110px; object-fit: cover; width: 100%; border-radius: 6px; }
        .media-container-admin { position: relative; width: 100%; height: 110px; background: #0f172a; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #fff; }
        .admin-avatar { width: 90px; height: 90px; object-fit: cover; border-radius: 50%; border: 3px solid #0d6efd; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0 sidebar d-flex flex-column p-3 position-sticky top-0 h-100">
            <div class="text-center my-3 border-bottom pb-3 border-secondary">
                <h5 class="fw-bold mb-0 text-white"><i class="fas fa-user-shield text-info"></i> Panel Professeur</h5>
            </div>
            <ul class="nav nav-pills flex-column mb-auto" id="adminTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link w-100 text-start active" id="profil-tab" data-bs-toggle="tab" data-bs-target="#panel-profil" type="button" role="tab"><i class="fas fa-address-card"></i> Profil & CV</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link w-100 text-start" id="pub-tab" data-bs-toggle="tab" data-bs-target="#panel-pub" type="button" role="tab"><i class="fas fa-book-open"></i> Publications</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link w-100 text-start" id="cours-tab" data-bs-toggle="tab" data-bs-target="#panel-cours" type="button" role="tab"><i class="fas fa-graduation-cap"></i> Cours & Activités</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link w-100 text-start" id="galerie-tab" data-bs-toggle="tab" data-bs-target="#panel-galerie" type="button" role="tab"><i class="fas fa-images"></i> Galerie Média</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link w-100 text-start" id="messages-tab" data-bs-toggle="tab" data-bs-target="#panel-messages" type="button" role="tab"><i class="fas fa-envelope-open-text"></i> Livre d'or</button>
                </li>
            </ul>
            <hr class="text-secondary">
            <a href="../index.php" class="btn btn-outline-light btn-sm w-100 py-2"><i class="fas fa-door-open"></i> Voir le Site</a>
        </div>

        <div class="col-md-9 col-lg-10 main-content">
            <div class="tab-content" id="adminTabsContent">
                
                <div class="tab-pane fade show active" id="panel-profil" role="tabpanel">
                    <div class="card-box">
                        <h4 class="fw-bold text-dark mb-4"><i class="fas fa-user-edit text-primary"></i> Données d'identité & CV Épistémique</h4>
                        
                        <form method="POST" action="dashboard.php" enctype="multipart/form-data">
                            <div class="row g-3">
                                
                                <div class="col-12 d-flex align-items-center gap-4 mb-4 bg-light p-3 rounded border">
                                    <?php if(!empty($profil['photo_url'])): ?>
                                        <img src="../<?= htmlspecialchars($profil['photo_url']) ?>?t=<?= time() ?>" class="admin-avatar" alt="Avatar">
                                    <?php else: ?>
                                        <div class="admin-avatar bg-secondary d-flex align-items-center justify-content-center text-white"><i class="fas fa-user fa-2x"></i></div>
                                    <?php endif; ?>
                                    <div>
                                        <label class="form-label small fw-bold text-dark mb-1">Photo d'identité académique</label>
                                        <input type="file" name="photo_profil" class="form-control form-control-sm" accept="image/*">
                                        <div class="form-text extra-small" style="font-size:0.75rem;">Formats acceptés : JPG, JPEG, PNG, GIF. Rendu circulaire automatique.</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6"><label class="form-label small fw-bold">Nom complet</label><input type="text" name="nom_complet" class="form-control" value="<?= htmlspecialchars($profil['nom_complet'] ?? '') ?>" required></div>
                                <div class="col-md-6"><label class="form-label small fw-bold">Titre académique</label><input type="text" name="titre" class="form-control" value="<?= htmlspecialchars($profil['titre'] ?? '') ?>" required></div>
                                <div class="col-md-6"><label class="form-label small fw-bold">Adresse Email</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($profil['email'] ?? '') ?>"></div>
                                <div class="col-md-6"><label class="form-label small fw-bold">Lien LinkedIn</label><input type="url" name="linkedin" class="form-control" value="<?= htmlspecialchars($profil['linkedin'] ?? '') ?>"></div>
                                <div class="col-md-12">
                                    <label class="form-label small fw-bold">Document CV (Format PDF)</label>
                                    <input type="file" name="cv_pdf" class="form-control" accept=".pdf">
                                    <?php if(!empty($profil['cv_url'])): ?>
                                        <div class="form-text text-success"><i class="fas fa-check-circle"></i> Fichier actif : <a href="../<?= htmlspecialchars($profil['cv_url']) ?>" target="_blank" class="fw-bold text-decoration-underline text-success">Consulter le CV</a></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-12"><label class="form-label small fw-bold">Texte de Biographie</label><textarea name="biographie" class="form-control" rows="5" required><?= htmlspecialchars($profil['biographie'] ?? '') ?></textarea></div>
                            </div>
                            <button type="submit" name="submit_profil" class="btn btn-primary mt-4 px-4 btn-sm">Mettre à jour l'identité</button>
                        </form>
                    </div>
                </div>

                <div class="tab-pane fade" id="panel-pub" role="tabpanel">
                    <div class="card-box">
                        <h4 class="fw-bold text-dark mb-4" id="form-pub-title"><i class="fas fa-book text-success"></i> Ajouter une Production Scientifique</h4>
                        <form method="POST" action="dashboard.php" class="row g-3" id="form-publication">
                            <input type="hidden" name="id_pub" id="input_id_pub" value="0">
                            
                            <div class="col-md-3"><label class="form-label small fw-bold">Type</label><select name="type_pub" id="input_type_pub" class="form-select"><option value="ouvrage">Livre / Ouvrage</option><option value="article">Article de Revue</option></select></div>
                            <div class="col-md-6"><label class="form-label small fw-bold">Titre de la production</label><input type="text" name="titre_pub" id="input_titre_pub" class="form-control" required></div>
                            <div class="col-md-3"><label class="form-label small fw-bold">Année</label><input type="number" name="annee_publication" id="input_annee_pub" class="form-control" value="2026" required></div>
                            <div class="col-md-6"><label class="form-label small fw-bold">Éditeur ou Revue</label><input type="text" name="editeur_revue" id="input_editeur_pub" class="form-control"></div>
                            <div class="col-md-6"><label class="form-label small fw-bold">Lien URL (Détails/Achat)</label><input type="url" name="lien_url" id="input_lien_pub" class="form-control"></div>
                            <div class="col-12"><label class="form-label small fw-bold">Résumé / Description</label><textarea name="description_pub" id="input_desc_pub" class="form-control" rows="3"></textarea></div>
                            <div class="col-12 d-flex gap-2">
                                <button type="submit" name="submit_publication" id="btn-submit-pub" class="btn btn-success mt-2 btn-sm px-4">Sauvegarder la publication</button>
                                <button type="button" id="btn-cancel-edit-pub" class="btn btn-secondary mt-2 btn-sm px-3 d-none" onclick="annulerModificationPub()">Annuler</button>
                            </div>
                        </form>

                        <h5 class="fw-bold text-dark mt-5 mb-3 border-bottom pb-2"><i class="fas fa-list text-muted"></i> Liste des Publications</h5>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle small">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Type</th>
                                        <th>Titre</th>
                                        <th>Éditeur/Revue</th>
                                        <th>Année</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($publications)): ?>
                                        <tr><td colspan="5" class="text-center text-muted">Aucune publication enregistrée.</td></tr>
                                    <?php else: ?>
                                        <?php foreach($publications as $pub): ?>
                                            <tr>
                                                <td><span class="badge bg-<?= $pub['type'] === 'ouvrage' ? 'success' : 'info' ?> text-capitalize"><?= htmlspecialchars($pub['type']) ?></span></td>
                                                <td class="fw-bold"><?= htmlspecialchars($pub['titre']) ?></td>
                                                <td><?= htmlspecialchars($pub['editeur_revue'] ?: '-') ?></td>
                                                <td><?= $pub['annee_publication'] ?></td>
                                                <td class="text-center">
                                                    <div class="d-flex justify-content-center gap-2">
                                                        <button class="btn btn-outline-primary btn-sm" onclick="remplirFormulairePub(<?= htmlspecialchars(json_encode($pub)) ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-outline-danger btn-sm" onclick="confirmerSuppressionPub(<?= $pub['id'] ?>)">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="panel-cours" role="tabpanel">
                    <div class="card-box">
                        <h4 class="fw-bold text-dark mb-4"><i class="fas fa-graduation-cap text-warning"></i> Catalogue des Enseignements & Supports de Cours</h4>
                        <div class="row">
                            <div class="col-md-5 border-end pe-md-4">
                                <h6 class="fw-bold text-muted text-uppercase mb-3 small">Nouveau Support</h6>
                                <form method="POST" action="dashboard.php" enctype="multipart/form-data" class="row g-2">
                                    <div class="col-12"><label class="form-label small fw-bold">Type</label><select name="type_act" class="form-select form-select-sm"><option value="cours">Cours / Enseignement</option><option value="conference">Conférence / Colloque</option></select></div>
                                    <div class="col-12"><label class="form-label small fw-bold">Intitulé</label><input type="text" name="titre_act" class="form-control form-control-sm" required></div>
                                    <div class="col-12"><label class="form-label small fw-bold">Institution / Université</label><input type="text" name="institution_evenement" class="form-control form-control-sm" required></div>
                                    <div class="col-12"><label class="form-label small fw-bold">Date</label><input type="date" name="date_evenement" class="form-control form-control-sm" required></div>
                                    <div class="col-12"><label class="form-label small fw-bold">Fichier (PDF, PPT, PPTX)</label><input type="file" name="support_cours" class="form-control form-control-sm" accept=".pdf,.ppt,.pptx"></div>
                                    <div class="col-12"><label class="form-label small fw-bold">Description</label><textarea name="description_act" class="form-control form-control-sm" rows="2"></textarea></div>
                                    <div class="col-12 mt-3"><button type="submit" name="submit_activite" class="btn btn-warning text-dark btn-sm w-100">Ajouter l'activité & calculer SHA-256</button></div>
                                </form>
                            </div>
                            <div class="col-md-7 ps-md-4" style="max-height: 430px; overflow-y: auto;">
                                <h6 class="fw-bold text-muted text-uppercase mb-3 small">Enseignements enregistrés</h6>
                                <?php if(empty($activites)): ?><p class="text-muted small">Aucune donnée disponible.</p><?php else: ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach($activites as $act): ?>
                                            <div class="list-group-item d-flex justify-content-between align-items-start px-0 py-3">
                                                <div class="text-truncate" style="max-width: 90%;">
                                                    <div class="fw-bold small"><?= htmlspecialchars($act['titre']) ?></div>
                                                    <span class="text-muted" style="font-size:0.8rem;"><?= htmlspecialchars($act['institution_evenement']) ?> (<?= date('Y', strtotime($act['date_evenement'])) ?>)</span>
                                                    <?php if(!empty($act['document_url'])): ?>
                                                        <div class="mt-1"><a href="../<?= $act['document_url'] ?>" target="_blank" class="badge bg-secondary text-decoration-none small"><i class="fas fa-download"></i> Support lié</a></div>
                                                    <?php endif; ?>
                                                    <?php if(!empty($act['document_sha256'])): ?>
                                                        <div class="mt-1 text-muted" style="font-size:0.75rem;"><i class="fas fa-fingerprint text-info"></i> SHA-256: <code class="text-dark bg-light p-1 rounded"><?= substr($act['document_sha256'], 0, 16) ?>...</code></div>
                                                    <?php endif; ?>
                                                </div>
                                                <button onclick="confirmerSuppressionAct(<?= $act['id'] ?>)" class="btn btn-link text-danger btn-sm p-0 align-self-center"><i class="fas fa-trash-alt"></i></button>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="panel-galerie" role="tabpanel">
                    <div class="card-box">
                        <h4 class="fw-bold text-dark mb-4"><i class="fas fa-camera text-danger"></i> Album Multimédia</h4>
                        <div class="row">
                            <div class="col-md-4 border-end pe-md-4">
                                <form method="POST" action="dashboard.php" enctype="multipart/form-data">
                                    <div class="mb-3"><label class="form-label small fw-bold">Légende</label><input type="text" name="titre_media" class="form-control form-control-sm" required></div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold d-block">Type</label>
                                        <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="type_media" id="type_img" value="image" checked onclick="toggleMediaFields()"><label class="form-check-label small" for="type_img">Image</label></div>
                                        <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="type_media" id="type_vid_loc" value="video_local" onclick="toggleMediaFields()"><label class="form-check-label small" for="type_vid_loc">Vidéo Locale</label></div>
                                        <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="type_media" id="type_vid_yt" value="video_youtube" onclick="toggleMediaFields()"><label class="form-check-label small" for="type_vid_yt">YouTube</label></div>
                                    </div>
                                    <div id="field_image" class="mb-3"><input type="file" name="image_galerie" class="form-control form-control-sm" accept="image/*"></div>
                                    <div id="field_video_local" class="mb-3 d-none"><input type="file" name="video_galerie_locale" class="form-control form-control-sm" accept="video/*"></div>
                                    <div id="field_video_youtube" class="mb-3 d-none"><input type="url" name="video_url" class="form-control form-control-sm" placeholder="Lien de la vidéo"></div>
                                    <button type="submit" name="submit_galerie" class="btn btn-danger btn-sm w-100">Uploader le média</button>
                                </form>
                            </div>
                            <div class="col-md-8 ps-md-4" style="max-height: 430px; overflow-y: auto;">
                                <div class="row row-cols-1 row-cols-sm-3 g-2">
                                    <?php foreach($images as $img): ?>
                                        <div class="col">
                                            <div class="card h-100 border-0 shadow-sm bg-light">
                                                <?php $url = $img['media_url']; $ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
                                                if(strpos($url, 'http') === 0): ?><div class="media-container-admin"><i class="fab fa-youtube text-danger fa-xl"></i></div>
                                                <?php elseif(in_array($ext, ['mp4', 'webm'])): ?><div class="media-container-admin"><i class="fas fa-video text-info fa-xl"></i></div>
                                                <?php else: ?><img src="../<?= htmlspecialchars($url) ?>" class="galerie-img-admin" alt="Img"><?php endif; ?>
                                                <div class="p-2 text-center">
                                                    <p class="small text-truncate mb-1 fw-semibold"><?= htmlspecialchars($img['titre']) ?></p>
                                                    <button onclick="confirmerSuppressionImg(<?= $img['id'] ?>)" class="btn btn-link text-danger btn-sm p-0 border-0 text-decoration-none"><i class="fas fa-trash-alt"></i> Retirer</button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="panel-messages" role="tabpanel">
                    <div class="card-box">
                        <h4 class="fw-bold text-dark mb-4"><i class="fas fa-comments text-secondary"></i> Modération des témoignages</h4>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle small">
                                <thead class="table-dark"><tr><th>Auteur</th><th>Institution</th><th>Message</th><th>Action</th></tr></thead>
                                <tbody>
                                    <?php if(empty($commentaires)): ?><tr><td colspan="4" class="text-center text-muted">Aucun témoignage reçu.</td></tr><?php else: ?>
                                        <?php foreach($commentaires as $com): ?>
                                            <tr>
                                                <td class="fw-bold text-primary"><?= htmlspecialchars($com['nom_visiteur']) ?></td>
                                                <td><?= htmlspecialchars($com['institution'] ?: '-') ?></td>
                                                <td class="text-muted" style="max-width:400px;"><?= htmlspecialchars($com['message']) ?></td>
                                                <td><button onclick="confirmerSuppressionCom(<?= $com['id'] ?>)" class="btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i> Retirer</button></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Fonctions utilitaires de basculement des types médias (Galerie)
function toggleMediaFields() {
    const typeImage = document.getElementById('type_img').checked;
    const typeVidLoc = document.getElementById('type_vid_loc').checked;
    const typeVidYt = document.getElementById('type_vid_yt').checked;

    document.getElementById('field_image').classList.toggle('d-none', !typeImage);
    document.getElementById('field_video_local').classList.toggle('d-none', !typeVidLoc);
    document.getElementById('field_video_youtube').classList.toggle('d-none', !typeVidYt);
}

// Remplir le formulaire pour modifier une publication
function remplirFormulairePub(pub) {
    document.getElementById('form-pub-title').innerHTML = '<i class="fas fa-edit text-primary"></i> Modifier la Production Scientifique';
    document.getElementById('input_id_pub').value = pub.id;
    document.getElementById('input_type_pub').value = pub.type;
    document.getElementById('input_titre_pub').value = pub.titre;
    document.getElementById('input_annee_pub').value = pub.annee_publication;
    document.getElementById('input_editeur_pub').value = pub.editeur_revue;
    document.getElementById('input_lien_pub').value = pub.lien_url;
    document.getElementById('input_desc_pub').value = pub.description;
    
    const btnSubmit = document.getElementById('btn-submit-pub');
    btnSubmit.className = "btn btn-primary mt-2 btn-sm px-4";
    btnSubmit.innerText = "Mettre à jour la publication";
    
    document.getElementById('btn-cancel-edit-pub').classList.remove('d-none');
    
    // Scroller vers le formulaire automatiquement
    document.getElementById('form-pub-title').scrollIntoView({ behavior: 'smooth' });
}

// Annuler le mode édition des publications
function annulerModificationPub() {
    document.getElementById('form-pub-title').innerHTML = '<i class="fas fa-book text-success"></i> Ajouter une Production Scientifique';
    document.getElementById('form-publication').reset();
    document.getElementById('input_id_pub').value = '0';
    
    const btnSubmit = document.getElementById('btn-submit-pub');
    btnSubmit.className = "btn btn-success mt-2 btn-sm px-4";
    btnSubmit.innerText = "Sauvegarder la publication";
    
    document.getElementById('btn-cancel-edit-pub').classList.add('d-none');
}

// Boîtes de confirmation génériques via SweetAlert2
function confirmerSuppressionPub(id) {
    Swal.fire({
        title: 'Êtes-vous sûr ?',
        text: "Cette publication sera supprimée définitivement !",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Oui, supprimer !',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) { window.location.href = 'dashboard.php?action=delete_pub&id=' + id; }
    });
}

function confirmerSuppressionAct(id) {
    Swal.fire({
        title: 'Supprimer cette activité ?',
        text: "Le support de cours physique sera également retiré du serveur.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Supprimer',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) { window.location.href = 'dashboard.php?action=delete_act&id=' + id; }
    });
}

function confirmerSuppressionImg(id) {
    Swal.fire({
        title: 'Retirer ce média ?',
        text: "Cette action est irréversible.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Retirer',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) { window.location.href = 'dashboard.php?action=delete_img&id=' + id; }
    });
}

function confirmerSuppressionCom(id) {
    Swal.fire({
        title: 'Supprimer ce témoignage ?',
        text: "Il n'apparaîtra plus sur le livre d'or public.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Supprimer',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) { window.location.href = 'dashboard.php?action=delete_com&id=' + id; }
    });
}
</script>

<?php if(!empty($status)): ?>
<script>
    const stat = "<?= $status ?>";
    if(stat === 'success_profil') { Swal.fire('Profil mis à jour !', 'Les données d\'identité ont été actualisées.', 'success'); }
    else if(stat === 'success_pub') { Swal.fire('Ajouté !', 'La publication scientifique a été répertoriée.', 'success'); }
    else if(stat === 'success_pub_edit') { Swal.fire('Modifié !', 'La publication a été mise à jour avec succès.', 'success'); }
    else if(stat === 'success_pub_delete') { Swal.fire('Supprimé !', 'La publication a été effacée de la base de données.', 'success'); }
    else if(stat === 'success_act') { Swal.fire('Activité enregistrée !', 'Le catalogue des enseignements a été complété.', 'success'); }
    else if(stat === 'success_act_delete') { Swal.fire('Activité effacée !', 'L\'enseignement et son support ont été nettoyés.', 'success'); }
    else if(stat === 'success_galerie_add') { Swal.fire('Média téléversé !', 'L\'album multimédia a bien reçu l\'élément.', 'success'); }
    else if(stat === 'success_galerie_delete') { Swal.fire('Élément retiré !', 'Le média a été effacé de l\'album.', 'success'); }
    else if(stat === 'success_com_delete') { Swal.fire('Modéré !', 'Le témoignage a été retiré du livre d\'or.', 'success'); }
    else if(stat === 'error_cv_extension') { Swal.fire('Erreur CV', 'Seuls les documents au format PDF sont autorisés.', 'error'); }
    else if(stat === 'error_photo_extension') { Swal.fire('Erreur Image', 'Format de photo d\'identité non pris en compte.', 'error'); }
    else if(stat === 'error_doc_extension') { Swal.fire('Erreur Support', 'Extensions acceptées : PDF, PPT, PPTX uniquement.', 'error'); }
    else if(stat === 'error_extension') { Swal.fire('Erreur Extension', 'L\'image sélectionnée n\'est pas valide.', 'error'); }
    else if(stat === 'error_extension_video') { Swal.fire('Erreur Vidéo', 'Le format vidéo local n\'est pas supporté.', 'error'); }
    else if(stat === 'error_youtube_link') { Swal.fire('Lien non valide', 'Impossible de résoudre l\'identifiant de la vidéo YouTube.', 'error'); }
    
    // Nettoyer l'URL des variables GET pour éviter le déclenchement au rafraîchissement
    if (window.history.replaceState) { window.history.replaceState(null, null, window.location.pathname); }
</script>
<?php endif; ?>

</body>
</html>