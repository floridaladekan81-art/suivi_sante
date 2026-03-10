<?php
session_start();
require_once 'config/database.php';

// Vérification de l'authentification
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$user_role = $_SESSION["user_role"];

// Récupération des infos basiques selon le rôle
$user_name = "";
try {
    $pdo = getDBConnection();
    if ($user_role === 'patient') {
        $stmt = $pdo->prepare("SELECT first_name, last_name FROM patients WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $patient = $stmt->fetch();
        if ($patient) {
            $user_name = htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']);
        }
    } else if ($user_role === 'doctor') {
        $stmt = $pdo->prepare("SELECT first_name, last_name FROM doctors WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $doctor = $stmt->fetch();
        if ($doctor) {
            $user_name = "Dr. " . htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']);
        }
    } else {
        $user_name = "Administrateur";
    }
} catch(PDOException $e) {
    $user_name = "Utilisateur";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Espace - Suivi Santé</title>
    <!-- Chargement de Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
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
    <div class="container main-content mt-5">
        <div class="d-flex justify-content-between align-items-center mb-5 fade-up delay-1">
            <div>
                <h2 style="font-weight: 700; color: var(--primary-color);">Bienvenue sur votre espace</h2>
                <p class="text-muted fs-5 mb-0">Que souhaitez-vous faire aujourd'hui ?</p>
            </div>
            <div class="d-none d-md-block">
                <span class="badge badge-modern bg-success-subtle text-success border border-success-subtle px-3 py-2"><i class="fa-solid fa-check-circle me-1"></i> Système Sécurisé</span>
            </div>
        </div>
        
        <div class="row g-4">
            <?php if ($user_role === 'patient'): ?>
                <!-- Module Profil -->
                <div class="col-md-4 fade-up delay-1">
                    <div class="glass-card h-100 text-center p-4">
                        <i class="fa-regular fa-id-card dashboard-icon"></i>
                        <h4 class="mb-3">Mon Profil</h3>
                        <p class="text-muted mb-4">Gérez vos informations personnelles, groupe sanguin et contacts d'urgence.</p>
                        <a href="profile.php" class="btn-modern d-inline-block text-decoration-none w-100">
                            Gérer mon profil <i class="fa-solid fa-arrow-right ms-2"></i>
                        </a>
                    </div>
                </div>

                <!-- Module Dossier -->
                <div class="col-md-4 fade-up delay-2">
                    <div class="glass-card h-100 text-center p-4" style="border: 2px solid rgba(79, 70, 229, 0.3);">
                        <i class="fa-solid fa-file-medical dashboard-icon" style="background: linear-gradient(135deg, var(--secondary-color), #2DD4BF); -webkit-background-clip: text;"></i>
                        <h4 class="mb-3">Dossier Médical</h3>
                        <p class="text-muted mb-4">Archives de consultations, chirurgies, allergies et carnet de vaccinations.</p>
                        <a href="medical_records.php" class="btn-modern d-inline-block text-decoration-none w-100" style="background: linear-gradient(135deg, var(--secondary-color) 0%, #059669 100%);">
                            Ouvrir le dossier <i class="fa-solid fa-stethoscope ms-2"></i>
                        </a>
                    </div>
                </div>

                <!-- Module Partages -->
                <div class="col-md-4 fade-up delay-3">
                    <div class="glass-card h-100 text-center p-4">
                        <i class="fa-solid fa-user-doctor dashboard-icon" style="background: linear-gradient(135deg, #F59E0B, #EA580C); -webkit-background-clip: text;"></i>
                        <h4 class="mb-3">Mes Médecins</h3>
                        <p class="text-muted mb-4">Gérez la liste de vos médecins et les droits d'accès à votre dossier.</p>
                        <a href="doctors.php" class="btn-modern d-inline-block text-decoration-none w-100" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);">
                            Accès partagés <i class="fa-solid fa-users ms-2"></i>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Vue Médecin / Admin -->
                <div class="col-md-6 fade-up delay-1">
                    <div class="glass-card p-4 p-md-5 text-center h-100">
                        <i class="fa-solid fa-users fa-3x mb-3 text-primary"></i>
                        <h3 class="mb-3">Mes Patients</h3>
                        <p class="text-muted mb-4">Consultez les dossiers des patients qui vous ont accordé l'accès.</p>
                        <a href="patients_list.php" class="btn-modern d-inline-block text-decoration-none w-100"><i class="fa-solid fa-folder-open me-2"></i> Voir les dossiers partagés</a>
                    </div>
                </div>
                
                <div class="col-md-6 fade-up delay-2">
                    <div class="glass-card p-4 p-md-5 text-center h-100" style="border: 2px solid rgba(79, 70, 229, 0.3);">
                        <i class="fa-solid fa-globe fa-3x mb-3" style="color: var(--secondary-color);"></i>
                        <h3 class="mb-3">Annuaire Global</h3>
                        <p class="text-muted mb-4">Recherchez d'autres praticiens ou tout patient inscrit sur la plateforme.</p>
                        <a href="admin_directory.php" class="btn-modern d-inline-block text-decoration-none w-100" style="background: linear-gradient(135deg, var(--secondary-color) 0%, #059669 100%);"><i class="fa-solid fa-magnifying-glass me-2"></i> Ouvrir l'annuaire global</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Footer discret intégré au fond -->
    <footer class="text-center py-4 mt-5 fade-up delay-3" style="border-top: 1px solid rgba(255,255,255,0.3);">
        <p class="mb-0 text-muted fw-medium">&copy; <?php echo date('Y'); ?> Application Suivi Santé. Florida Bellange Olamidé LADEKAN.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
