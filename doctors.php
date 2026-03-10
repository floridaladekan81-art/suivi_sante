<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== 'patient') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$pdo = getDBConnection();

$stmt = $pdo->prepare("SELECT first_name, last_name FROM patients WHERE user_id = ?");
$stmt->execute([$user_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);
$user_name = htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']);

// Récupérer la liste des médecins pour l'annuaire
$stmtD = $pdo->query("SELECT id, first_name, last_name, specialty, office_address, phone FROM doctors");
$doctors = $stmtD->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Médecins - Suivi Santé</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .doctor-card {
            transition: var(--transition-smooth);
            border-left: 4px solid transparent;
        }
        .doctor-card:hover {
            border-left: 4px solid var(--primary-color);
            background: rgba(255,255,255,0.9);
        }
    </style>
</head>
<body class="animated-bg text-dark">
    
    <!-- Navbar Premium -->
    <nav class="navbar navbar-expand-lg navbar-modern shadow-sm">
        <div class="container py-2">
            <a class="navbar-brand-modern text-decoration-none" href="dashboard.php">
                <i class="fa-solid fa-shield-heart"></i> SuiviSanté
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" style="background: rgba(79, 70, 229, 0.1);">
                <i class="fa-solid fa-bars text-primary"></i>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center gap-3">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link"><i class="fa-solid fa-house"></i> Tableau de bord</a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link fw-medium" style="color: var(--text-main);">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=4F46E5&color=fff&rounded=true&size=36" alt="Avatar" class="me-2 shadow-sm"> 
                            <?php echo $user_name; ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="btn-modern px-3 py-2" href="logout.php" style="font-size: 0.9rem;">Déconnexion <i class="fa-solid fa-right-from-bracket ms-1"></i></a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contenu Principal -->
    <div class="container main-content mt-5 mb-5">
        <div class="mb-5 fade-up delay-1">
            <a href="dashboard.php" class="text-decoration-none text-muted mb-3 d-inline-block"><i class="fa-solid fa-arrow-left me-2"></i>Retour au tableau de bord</a>
            <h2 style="font-weight: 700; color: var(--primary-color);"><i class="fa-solid fa-user-doctor me-2"></i>Annuaire des Médecins</h2>
            <p class="text-muted fs-5 mb-0">Retrouvez les professionnels de santé inscrits sur la plateforme et partagez-leur l'accès à vos données.</p>
        </div>

        <div class="row g-4 fade-up delay-2">
            <?php if (count($doctors) > 0): ?>
                <?php foreach ($doctors as $doc): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="glass-card doctor-card p-4 h-100">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background: rgba(79, 70, 229, 0.1);">
                                    <i class="fa-solid fa-user-md fa-2x text-primary"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0 fw-bold">Dr. <?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']); ?></h5>
                                    <span class="badge bg-primary rounded-pill text-white mt-1"><?php echo htmlspecialchars($doc['specialty'] ?? 'Généraliste'); ?></span>
                                </div>
                            </div>
                            
                            <hr style="opacity: 0.1;">
                            
                            <ul class="list-unstyled mb-4">
                                <li class="mb-2 text-muted"><i class="fa-solid fa-location-dot me-2 text-primary" style="width: 20px;"></i> <?php echo htmlspecialchars($doc['office_address'] ?? 'Non renseignée'); ?></li>
                                <li class="text-muted"><i class="fa-solid fa-phone me-2 text-primary" style="width: 20px;"></i> <?php echo htmlspecialchars($doc['phone'] ?? 'Non renseigné'); ?></li>
                            </ul>
                            
                            <div class="mt-auto">
                                <button class="btn btn-outline-primary w-100" style="border-radius: 10px; font-weight: 500;" onclick="alert('Fonction de partage bientôt disponible.')">
                                    <i class="fa-solid fa-share-nodes me-2"></i> Partager mon dossier
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 mt-4 text-center">
                    <div class="glass-card p-5">
                        <i class="fa-solid fa-stethoscope fa-4x text-muted mb-3" style="opacity: 0.3;"></i>
                        <h4 class="text-muted">Aucun médecin inscrit pour le moment.</h4>
                        <p class="text-muted mb-0">Revenez plus tard lorsque des praticiens auront rejoint la plateforme.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="text-center py-4 mt-5" style="border-top: 1px solid rgba(255,255,255,0.3);">
        <p class="mb-0 text-muted fw-medium">&copy; <?php echo date('Y'); ?> Application Suivi Santé. Florida Bellange Olamidé LADEKAN.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
