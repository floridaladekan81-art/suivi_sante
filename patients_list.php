<?php
session_start();
require_once 'config/database.php';

// Réservé aux médecins
if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== 'doctor') {
    header("Location: dashboard.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$pdo = getDBConnection();

// Récupérer les infos du médecin
$stmt = $pdo->prepare("SELECT id, first_name, last_name, specialty FROM doctors WHERE user_id = ?");
$stmt->execute([$user_id]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doc) {
    die("Erreur : Profil médecin introuvable.");
}
$doctor_id = $doc['id'];
$user_name = "Dr. " . htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']);

// Récupérer les patients qui ont partagé leur dossier avec ce médecin
$stmtP = $pdo->prepare("
    SELECT p.id, p.first_name, p.last_name, p.date_of_birth, p.blood_group, p.phone, p.emergency_contact_name, p.emergency_contact_phone, a.granted_at
    FROM patients p
    JOIN patient_doctor_access a ON p.id = a.patient_id
    WHERE a.doctor_id = ?
    ORDER BY p.last_name ASC
");
$stmtP->execute([$doctor_id]);
$patients = $stmtP->fetchAll(PDO::FETCH_ASSOC);

function calculateAge($dob) {
    if (!$dob || $dob === '1900-01-01') return 'N/A';
    $birthDate = new DateTime($dob);
    $today = new DateTime('today');
    return $birthDate->diff($today)->y . ' ans';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Patients - Suivi Santé</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .patient-card {
            transition: var(--transition-smooth);
            border-left: 4px solid transparent;
        }
        .patient-card:hover {
            border-left: 4px solid var(--primary-color);
            background: rgba(255,255,255,0.9);
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
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
        <div class="mb-4 fade-up delay-1">
            <a href="dashboard.php" class="text-decoration-none text-muted mb-3 d-inline-block"><i class="fa-solid fa-arrow-left me-2"></i>Retour au tableau de bord</a>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2 style="font-weight: 700; color: var(--primary-color);"><i class="fa-solid fa-users me-2"></i>Dossiers Patients</h2>
                    <p class="text-muted fs-5 mb-0">Consultez les dossiers des patients qui ont autorisé votre accès.</p>
                </div>
                <div>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fs-6 rounded-pill">
                        <i class="fa-solid fa-user-check me-2"></i> <?php echo count($patients); ?> patient(s) partagé(s)
                    </span>
                </div>
            </div>
        </div>

        <div class="row g-4 fade-up delay-2">
            <?php if (count($patients) > 0): ?>
                <?php foreach ($patients as $pat): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="glass-card patient-card p-4 h-100 position-relative">
                            
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($pat['first_name'] . ' ' . $pat['last_name']); ?>&background=random&color=fff&rounded=true&size=60" alt="Avatar" class="shadow-sm">
                                <div>
                                    <h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($pat['first_name'] . ' ' . $pat['last_name']); ?></h5>
                                    <div class="d-flex align-items-center gap-2 mt-1">
                                        <span class="text-muted small"><?php echo calculateAge($pat['date_of_birth']); ?></span>
                                        <?php if (!empty($pat['blood_group'])): ?>
                                            <span class="badge bg-danger-subtle text-danger rounded-pill px-2" style="font-size: 0.70rem;"><?php echo htmlspecialchars($pat['blood_group']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <hr style="opacity: 0.1;">
                            
                            <ul class="list-unstyled mb-4">
                                <li class="mb-2 text-muted"><i class="fa-solid fa-phone me-2 text-primary" style="width: 20px;"></i> <?php echo htmlspecialchars($pat['phone'] ?? 'Non renseigné'); ?></li>
                                <?php if (!empty($pat['emergency_contact_name'])): ?>
                                <li class="text-muted small mt-2 p-2 rounded" style="background: rgba(244, 63, 94, 0.05); border-left: 3px solid #F43F5E;">
                                    <i class="fa-solid fa-truck-medical text-danger me-1"></i> <strong>Urgence:</strong> <?php echo htmlspecialchars($pat['emergency_contact_name']); ?> <br>
                                    <span class="ms-4"><?php echo htmlspecialchars($pat['emergency_contact_phone']); ?></span>
                                </li>
                                <?php endif; ?>
                            </ul>
                            
                            <div class="mt-auto pt-3 text-center">
                                <p class="text-muted small mb-3"><i class="fa-regular fa-clock me-1"></i> Accès accordé le <?php echo date('d/m/Y', strtotime($pat['granted_at'])); ?></p>
                                <button class="btn btn-primary w-100" style="border-radius: 10px; font-weight: 500; background: var(--primary-color); border-color: var(--primary-color);" onclick="alert('L\'accès au dossier spécifique n\'est pas encore développé pour la vue médecin.')">
                                    <i class="fa-solid fa-folder-open me-2"></i> Ouvrir le Dossier
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 mt-4 text-center">
                    <div class="glass-card p-5">
                        <i class="fa-solid fa-user-lock fa-4x text-muted mb-3" style="opacity: 0.3;"></i>
                        <h4 class="text-muted">Aucun dossier partagé.</h4>
                        <p class="text-muted mb-0">Vos patients doivent vous accorder l'accès depuis leur espace "Mes Médecins".</p>
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
