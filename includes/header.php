<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio - Professeur Mahatma Julien Tazi Kizey Tien-a-be</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .hero-section {
            position: relative;
            color: #ffffff;
            padding: 100px 0;
            overflow: hidden;
            background-color: #0f172a; /* Couleur par défaut */
        }

        /* Génération de l'animation en fonction des images de la base de données */
        <?php if (isset($images_slider) && !empty($images_slider)): ?>
        @keyframes heroSlider {
            <?php 
            $total_images = count($images_slider);
            foreach ($images_slider as $index => $img) {
                $pourcentage = round(($index / $total_images) * 100);
                $suivant = round((($index + 1) / $total_images) * 100) - 1;
                echo "    {$pourcentage}%, {$suivant}% { background-image: url('" . htmlspecialchars($img['chemin_image']) . "'); }\n";
            }
            // Retour propre à la première image pour fermer la boucle d'animation
            echo "    100% { background-image: url('" . htmlspecialchars($images_slider[0]['chemin_image']) . "'); }\n";
            ?>
        }
        <?php else: ?>
        /* Si aucune image n'est trouvée ou sur les autres pages, dégradé sombre fixe */
        @keyframes heroSlider {
            0%, 100% { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); }
        }
        <?php endif; ?>
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container">
        <a class="navbar-brand text-wrap" href="index.php" style="max-width: 300px; font-size: 0.9rem;">
            Prof. Mahatma Julien Tazi Kizey Tien-a-be
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-home"></i> Accueil</a></li>
                <li class="nav-item"><a class="nav-link" href="publications.php"><i class="fas fa-book"></i> Publications</a></li>
                <li class="nav-item"><a class="nav-link" href="activites.php"><i class="fas fa-university"></i> Enseignements & Conférences</a></li>
            </ul>
        </div>
    </div>
</nav>