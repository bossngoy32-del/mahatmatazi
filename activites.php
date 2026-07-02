<?php 
require_once 'config.php';
include 'includes/header.php';
?>

<div class="container my-5">
    <h1 class="text-center mb-5 text-primary fw-bold">Enseignements & Interventions</h1>

    <div class="row">
        <div class="col-md-6 mb-4">
            <h2 class="mb-4 border-bottom pb-2"><i class="fas fa-graduation-cap text-warning"></i> Cours dispensés</h2>
            <div class="list-group shadow-sm">
                <?php
                $stmt = $pdo->query("SELECT * FROM activites WHERE type='cours' ORDER BY date_evenement DESC");
                $coursList = $stmt->fetchAll();
                
                if (empty($coursList)): ?>
                    <div class="list-group-item text-muted">Aucun cours répertorié.</div>
                <?php else: 
                    foreach ($coursList as $cours): ?>
                        <div class="list-group-item list-group-item-action p-3">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1 fw-bold text-dark"><?= htmlspecialchars($cours['titre']) ?></h5>
                                <small class="text-muted"><i class="fas fa-calendar-alt"></i> <?= date('Y', strtotime($cours['date_evenement'])) ?></small>
                            </div>
                            <p class="mb-1 text-secondary"><strong>Institution :</strong> <?= htmlspecialchars($cours['institution_evenement']) ?></p>
                            <small class="text-muted"><?= htmlspecialchars($cours['description']) ?></small>
                        </div>
                    <?php endforeach; 
                endif; ?>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <h2 class="mb-4 border-bottom pb-2"><i class="fas fa-microphone text-danger"></i> Conférences & Colloques</h2>
            <div class="list-group shadow-sm">
                <?php
                $stmt = $pdo->query("SELECT * FROM activites WHERE type='conference' ORDER BY date_evenement DESC");
                $conferences = $stmt->fetchAll();
                
                if (empty($conferences)): ?>
                    <div class="list-group-item text-muted">Aucune conférence répertoriée.</div>
                <?php else: 
                    foreach ($conferences as $conf): ?>
                        <div class="list-group-item list-group-item-action p-3">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1 fw-bold text-dark"><?= htmlspecialchars($conf['titre']) ?></h5>
                                <small class="badge bg-danger align-self-start"><?= date('d/m/Y', strtotime($conf['date_evenement'])) ?></small>
                            </div>
                            <p class="mb-1 text-secondary"><strong>Événement :</strong> <?= htmlspecialchars($conf['institution_evenement']) ?></p>
                            <p class="mb-1 small text-muted"><?= htmlspecialchars($conf['description']) ?></p>
                        </div>
                    <?php endforeach; 
                endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>