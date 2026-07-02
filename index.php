<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connexion à la base de données
require_once $_SERVER['DOCUMENT_ROOT'] . '/Portfolio-Prof/config.php';

// Traitement du formulaire du Livre d'or
$statut_commentaire = "";
if (isset($_POST['submit_commentaire'])) {
    $nom_visiteur = trim($_POST['nom_visiteur']);
    $institution = trim($_POST['institution']);
    $message = trim($_POST['message']);
    
    if (!empty($nom_visiteur) && !empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO commentaires (nom_visiteur, institution, message) VALUES (?, ?, ?)");
        $stmt->execute([$nom_visiteur, $institution, $message]);
        $statut_commentaire = "success";
    } else {
        $statut_commentaire = "error_fields";
    }
}

// Récupération des données pour l'affichage public
$profil = $pdo->query("SELECT * FROM profil LIMIT 1")->fetch();
$publications = $pdo->query("SELECT * FROM publications ORDER BY annee_publication DESC, id DESC")->fetchAll();
$activites = $pdo->query("SELECT * FROM activites ORDER BY date_evenement DESC")->fetchAll();
$images = $pdo->query("SELECT * FROM galerie ORDER BY id DESC")->fetchAll();
$commentaires = $pdo->query("SELECT * FROM commentaires ORDER BY cree_le DESC")->fetchAll();

// AJOUT EXCLUSIF : Récupération des 4 dernières images pour l'animation du slider d'arrière-plan géré par l'admin
$images_slider = $pdo->query("SELECT media_url FROM galerie ORDER BY id DESC LIMIT 4")->fetchAll();
$total_images = count($images_slider);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($profil['nom_complet'] ?? 'Portfolio Académique') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #334155; scroll-behavior: smooth; }
        .navbar { background: #1e293b !important; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .section-padding { padding: 60px 0; }
        .section-title { font-weight: 700; position: relative; margin-bottom: 40px; padding-bottom: 15px; }
        .section-title::after { content: ''; position: absolute; bottom: 0; left: 0; width: 60px; height: 4px; background-color: #0d6efd; }
        .pub-card, .act-card { border-left: 4px solid #0d6efd; transition: transform 0.2s; }
        .pub-card:hover, .act-card:hover { transform: translateY(-2px); }
        .galerie-img { height: 220px; object-fit: cover; width: 100%; border-radius: 8px; cursor: pointer; transition: opacity 0.2s; }
        .galerie-img:hover { opacity: 0.9; }
        .media-box { height: 220px; background: #0f172a; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; }
        footer { background: #0f172a; color: #94a3b8; padding: 30px 0; }

        /* =========================================================================
            STYLE MODIFIÉ EXCLUSIVEMENT POUR LA PHOTO (CONFORME À VOTRE CAPTURE)
           ========================================================================= */
        .img-profile-main {
            width: 280px;
            height: 280px;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid #475569; /* Bordure grise de la capture d'écran */
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            transition: transform 0.3s ease;
            position: relative;
            z-index: 3;
        }
        .img-profile-main:hover {
            transform: scale(1.02);
        }

        /* =========================================================================
            AJOUT : CONFIGURATION DU SLIDER DYNAMIQUE DE L'ARRIÈRE-PLAN
           ========================================================================= */
        .hero-section { 
            position: relative; 
            color: white; 
            padding: 80px 0; 
            overflow: hidden;
            background-color: #0f172a;
        }
        
        .hero-section::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            z-index: 1;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            animation: heroSlider 20s infinite ease-in-out; 
        }

        .hero-section::after {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            z-index: 2;
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.85) 0%, rgba(15, 23, 42, 0.92) 100%);
        }

        .hero-section .container {
            position: relative;
            z-index: 3;
        }

        /* Génération automatique des étapes CSS du slider à partir de la BDD admin */
        <?php if ($total_images > 0): ?>
        @keyframes heroSlider {
            <?php 
            foreach ($images_slider as $index => $img) {
                $pourcentage = round(($index / $total_images) * 100);
                $suivant = round((($index + 1) / $total_images) * 100) - 1;
                echo "    {$pourcentage}%, {$suivant}% { background-image: url('" . htmlspecialchars($img['media_url']) . "'); }\n";
            }
            echo "    100% { background-image: url('" . htmlspecialchars($images_slider[0]['media_url']) . "'); }\n";
            ?>
        }
        <?php else: ?>
        @keyframes heroSlider {
            0%, 100% { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); }
        }
        <?php endif; ?>
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#"><i class="fas fa-university me-2"></i>Espace Universitaire</a>
            <button class="navbar-expand-lg navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#biographie">Biographie</a></li>
                    <li class="nav-item"><a class="nav-link" href="#publications">Publications</a></li>
                    <li class="nav-item"><a class="nav-link" href="#enseignements">Enseignements</a></li>
                    <li class="nav-item"><a class="nav-link" href="#galerie">Médiathèque</a></li>
                    <li class="nav-item"><a class="nav-link" href="#livredor">Livre d'or</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-4 text-center mb-4 mb-md-0">
                    <?php if (!empty($profil['photo_url']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/Portfolio-Prof/' . $profil['photo_url'])): ?>
                        <img src="<?= htmlspecialchars($profil['photo_url']) ?>?t=<?= time() ?>" class="img-profile-main" alt="Professeur">
                    <?php else: ?>
                        <div class="img-profile-main bg-secondary d-flex align-items-center justify-content-center text-white m-auto">
                            <i class="fas fa-user-graduate fa-5x"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-md-5 text-md-start text-center">
                    <h1 class="display-5 fw-bold mb-2"><?= htmlspecialchars($profil['nom_complet'] ?? 'Nom du Professeur') ?></h1>
                    <p class="lead text-info fw-semibold mb-4"><?= htmlspecialchars($profil['titre'] ?? 'Enseignant - Chercheur') ?></p>
                    
                    <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-start">
                        <?php if(!empty($profil['email'])): ?>
                            <a href="mailto:<?= htmlspecialchars($profil['email']) ?>" class="btn btn-outline-light btn-sm px-3 py-2"><i class="fas fa-envelope me-2"></i>Contact Mail</a>
                        <?php endif; ?>
                        <?php if(!empty($profil['linkedin'])): ?>
                            <a href="<?= htmlspecialchars($profil['linkedin']) ?>" target="_blank" class="btn btn-outline-info btn-sm px-3 py-2"><i class="fab fa-linkedin text-info me-2"></i>Profil LinkedIn</a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-3 text-center text-md-end mt-4 mt-md-0">
                    <?php if(!empty($profil['cv_url'])): ?>
                        <a href="<?= htmlspecialchars($profil['cv_url']) ?>" class="btn btn-info text-dark btn-md fw-bold px-4" target="_blank">
                            <i class="fas fa-file-pdf me-2"></i> Télécharger le CV
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <section id="biographie" class="container section-padding">
        <h2 class="section-title">Notice Biographie</h2>
        <div class="card border-0 shadow-sm p-4 bg-light">
            <p class="lh-lg fs-5 text-secondary" style="white-space: pre-line;">
                <?= htmlspecialchars($profil['biographie'] ?? 'La biographie n\'a pas encore été rédigée.') ?>
            </p>
        </div>
    </section>

    <section id="publications" class="bg-light section-padding">
        <div class="container">
            <h2 class="section-title">Travaux & Productions Scientifiques</h2>
            <?php if(empty($publications)): ?>
                <p class="text-muted">Aucune publication répertoriée pour le moment.</p>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach($publications as $pub): ?>
                        <div class="col-12">
                            <div class="card pub-card border-0 shadow-sm p-4 bg-white">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-secondary text-uppercase"><?= htmlspecialchars($pub['type']) ?></span>
                                    <span class="fw-bold text-primary"><i class="far fa-calendar-alt"></i> <?= htmlspecialchars($pub['annee_publication']) ?></span>
                                </div>
                                <h4 class="fw-bold text-dark mb-2"><?= htmlspecialchars($pub['titre']) ?></h4>
                                <?php if(!empty($pub['editeur_revue'])): ?>
                                    <p class="text-muted small fw-semibold mb-2"><i class="fas fa-print"></i> Éditeur/Revue : <?= htmlspecialchars($pub['editeur_revue']) ?></p>
                                <?php endif; ?>
                                <?php if(!empty($pub['description'])): ?>
                                    <p class="text-secondary mb-3 small"><?= htmlspecialchars($pub['description']) ?></p>
                                <?php endif; ?>
                                <?php if(!empty($pub['lien_url'])): ?>
                                    <a href="<?= htmlspecialchars($pub['lien_url']) ?>" target="_blank" class="btn btn-outline-primary btn-sm align-self-start"><i class="fas fa-external-link-alt"></i> Accéder à la ressource</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section id="enseignements" class="container section-padding">
        <h2 class="section-title">Enseignements & Supports Pédagogiques</h2>
        <?php if(empty($activites)): ?>
            <p class="text-muted">Aucun cours disponible.</p>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach($activites as $act): ?>
                    <div class="col-md-6">
                        <div class="card act-card border-0 shadow-sm p-4 bg-light h-100 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-warning text-dark text-uppercase small fw-bold"><?= htmlspecialchars($act['type']) ?></span>
                                    <small class="text-muted fw-bold"><?= date('d/m/Y', strtotime($act['date_evenement'])) ?></small>
                                </div>
                                <h5 class="fw-bold text-dark"><?= htmlspecialchars($act['titre']) ?></h5>
                                <p class="text-primary fw-semibold small mb-2"><i class="fas fa-school"></i> <?= htmlspecialchars($act['institution_evenement']) ?></p>
                                <?php if(!empty($act['description'])): ?>
                                    <p class="text-muted small"><?= htmlspecialchars($act['description']) ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <?php if(!empty($act['document_url'])): 
                                $ext = pathinfo($act['document_url'], PATHINFO_EXTENSION);
                                $isPdf = ($ext === 'pdf');
                            ?>
                                <div class="mt-3 border-top pt-3">
                                    <a href="<?= htmlspecialchars($act['document_url']) ?>" class="btn <?= $isPdf ? 'btn-danger' : 'btn-warning text-dark' ?> btn-sm w-100 fw-bold mb-2" target="_blank">
                                        <i class="fas <?= $isPdf ? 'fa-file-pdf' : 'fa-file-powerpoint' ?> me-2"></i>
                                        Télécharger le support (<?= strtoupper($ext) ?>)
                                    </a>
                                    <?php if(!empty($act['document_sha256'])): ?>
                                        <div class="text-muted text-center" style="font-size: 0.72rem; word-break: break-all;">
                                            <i class="fas fa-fingerprint text-info"></i> Integrity SHA-256 : <code><?= htmlspecialchars($act['document_sha256']) ?></code>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section id="galerie" class="bg-light section-padding">
        <div class="container">
            <h2 class="section-title">Médiathèque de Recherche & Séminaires</h2>
            <?php if(empty($images)): ?>
                <p class="text-muted">Aucun contenu multimédia en ligne.</p>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach($images as $img): 
                        $url = $img['media_url'];
                        $ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
                    ?>
                        <div class="col-sm-6 col-md-4 col-lg-3">
                            <div class="card border-0 shadow-sm h-100 overflow-hidden bg-white">
                                <?php if(strpos($url, 'http') === 0): ?>
                                    <div class="ratio ratio-16x9">
                                        <iframe src="<?= htmlspecialchars($url) ?>" allowfullscreen class="rounded-top"></iframe>
                                    </div>
                                <?php elseif(in_array($ext, ['mp4', 'webm'])): ?>
                                    <div class="ratio ratio-16x9">
                                        <video controls class="rounded-top"><source src="<?= htmlspecialchars($url) ?>" type="video/<?= $ext ?>"></video>
                                    </div>
                                <?php else: ?>
                                    <img src="<?= htmlspecialchars($url) ?>" class="galerie-img" alt="Illustration" data-bs-toggle="modal" data-bs-target="#imgModal<?= $img['id'] ?>">
                                <?php endif; ?>
                                <div class="p-2 text-center">
                                    <small class="fw-semibold text-dark text-truncate d-block"><?= htmlspecialchars($img['titre']) ?></small>
                                </div>
                            </div>
                        </div>

                        <?php if(strpos($url, 'http') !== 0 && !in_array($ext, ['mp4', 'webm'])): ?>
                            <div class="modal fade" id="imgModal<?= $img['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                    <div class="modal-content bg-transparent border-0 text-end">
                                        <button type="button" class="btn-close btn-close-white ms-auto mb-2" data-bs-toggle="modal"></button>
                                        <img src="<?= htmlspecialchars($url) ?>" class="img-fluid rounded shadow" alt="Zoom">
                                        <p class="text-white text-center mt-2 small fw-bold"><?= htmlspecialchars($img['titre']) ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section id="livredor" class="container section-padding">
        <h2 class="section-title">Livre d'or Académique</h2>
        <div class="row g-5">
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm p-4 bg-light">
                    <h5 class="fw-bold text-dark mb-3"><i class="fas fa-pen-fancy"></i> Laisser un témoignage</h5>
                    <form method="POST" action="#livredor">
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Votre nom complet</label>
                            <input type="text" name="nom_visiteur" class="form-control" required placeholder="Ex: Boss Ngoy">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Institution / Université (Optionnel)</label>
                            <input type="text" name="institution" class="form-control" placeholder="Ex: Université de Kinshasa">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Votre message / Note</label>
                            <textarea name="message" class="form-control" rows="4" required placeholder="Saisissez votre note de recommandation ici..."></textarea>
                        </div>
                        <button type="submit" name="submit_commentaire" class="btn btn-primary w-100 fw-bold">Envoyer le message</button>
                    </form>
                </div>
            </div>
            
            <div class="col-lg-7" style="max-height: 480px; overflow-y: auto;">
                <h5 class="fw-bold mb-3 text-secondary">Témoignages récents</h5>
                <?php if(empty($commentaires)): ?>
                    <p class="text-muted small">Aucun message pour le moment. Soyez le premier à laisser une note.</p>
                <?php else: ?>
                    <?php foreach($commentaires as $com): ?>
                        <div class="card border-0 shadow-sm p-3 mb-3 bg-white border-start border-3 border-secondary">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-primary small"><?= htmlspecialchars($com['nom_visiteur']) ?></span>
                                <span class="text-muted" style="font-size: 0.75rem;"><i class="far fa-clock"></i> Le <?= date('d/m/Y', strtotime($com['cree_le'])) ?></span>
                            </div>
                            <?php if(!empty($com['institution'])): ?>
                                <small class="text-muted d-block mb-2 style-italic fw-semibold" style="font-size: 0.8rem;"><i class="fas fa-landmark"></i> <?= htmlspecialchars($com['institution']) ?></small>
                            <?php endif; ?>
                            <p class="text-secondary small mb-0 lh-base">« <?= htmlspecialchars($com['message']) ?> »</p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <footer class="text-center">
        <div class="container">
            <p class="mb-1 small">&copy; 2026 - Tous droits réservés | Plateforme Portfolio</p>
            <p class="mb-0 extra-small style-italic" style="font-size:0.75rem; color:#64748b;">Conçu pour l'excellence et l'intégrité de la recherche scientifique.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <?php if($statut_commentaire === 'success'): ?>
        <script>Swal.fire({ title: 'Témoignage envoyé !', text: 'Merci pour votre contribution au livre d\'or.', icon: 'success' });</script>
    <?php elseif($statut_commentaire === 'error_fields'): ?>
        <script>Swal.fire({ title: 'Erreur', text: 'Veuillez remplir tous les champs obligatoires.', icon: 'error' });</script>
    <?php endif; ?>

</body>
</html>