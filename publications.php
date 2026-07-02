<?php 
require_once 'config.php';
include 'includes/header.php';
?>

<div class="container my-5">
    <h1 class="text-center mb-5 text-primary fw-bold">Ouvrages & Articles de Recherche</h1>

    <h2 class="mb-4 border-bottom pb-2"><i class="fas fa-book text-success"></i> Ouvrages Épistémiques</h2>
    <div class="row row-cols-1 row-cols-md-2 g-4 mb-5">
        <?php
        $stmt = $pdo->query("SELECT * FROM publications WHERE type='ouvrage' ORDER BY annee_publication DESC");
        $ouvrages = $stmt->fetchAll();
        
        if (empty($ouvrages)): ?>
            <div class="col-12"><p class="text-muted">Aucun ouvrage enregistré pour le moment.</p></div>
        <?php else: 
            foreach ($ouvrages as $ouvrage): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm border-start border-success border-4">
                        <div class="card-body">
                            <h5 class="card-title fw-bold"><?= htmlspecialchars($ouvrage['titre']) ?></h5>
                            <h6 class="card-subtitle mb-2 text-muted">Éditeur : <?= htmlspecialchars($ouvrage['editeur_revue']) ?> (<?= $ouvrage['annee_publication'] ?>)</h6>
                            <p class="card-text"><?= nl2br(htmlspecialchars($ouvrage['description'])) ?></p>
                        </div>
                        <div class="card-footer bg-transparent border-0 text-end">
                            <button class="btn btn-sm btn-outline-success btn-details" data-titre="<?= htmlspecialchars($ouvrage['titre']) ?>">
                                <i class="fas fa-info-circle"></i> Consulter l'aperçu
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; 
        endif; ?>
    </div>

    <h2 class="mb-4 border-bottom pb-2"><i class="fas fa-file-alt text-info"></i> Articles & Revues Scientifiques</h2>
    <div class="row row-cols-1 row-cols-md-2 g-4">
        <?php
        $stmt = $pdo->query("SELECT * FROM publications WHERE type='article' ORDER BY annee_publication DESC");
        $articles = $stmt->fetchAll();
        
        if (empty($articles)): ?>
            <div class="col-12"><p class="text-muted">Aucun article publié pour le moment.</p></div>
        <?php else: 
            foreach ($articles as $article): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm border-start border-info border-4">
                        <div class="card-body">
                            <h5 class="card-title fw-bold"><?= htmlspecialchars($article['titre']) ?></h5>
                            <h6 class="card-subtitle mb-2 text-muted">Revue : <?= htmlspecialchars($article['editeur_revue']) ?> (<?= $article['annee_publication'] ?>)</h6>
                            <p class="card-text"><?= nl2br(htmlspecialchars($article['description'])) ?></p>
                        </div>
                        <div class="card-footer bg-transparent border-0 text-end">
                            <?php if (!empty($article['lien_telechargement'])): ?>
                                <a href="<?= htmlspecialchars($article['lien_telechargement']) ?>" target="_blank" class="btn btn-sm btn-info text-white">
                                    <i class="fas fa-download"></i> Télécharger le PDF
                                </a>
                            <?php else: ?>
                                <button class="btn btn-sm btn-outline-secondary btn-details" data-titre="<?= htmlspecialchars($article['titre']) ?>">
                                    <i class="fas fa-eye"></i> Demander l'accès
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; 
        endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>