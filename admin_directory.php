<?php
session_start();
require_once 'config/database.php';

// Limité aux médecins et admins pour l'instant
if (!isset($_SESSION["user_id"]) || ($_SESSION["user_role"] !== 'doctor' && $_SESSION["user_role"] !== 'admin')) {
    header("Location: dashboard.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$pdo = getDBConnection();

// Récupérer le nom de l'utilisateur connecté
if ($_SESSION["user_role"] === 'doctor') {
    $stmt = $pdo->prepare("SELECT first_name, last_name FROM doctors WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $doc = $stmt->fetch();
    $user_name = "Dr. " . htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']);
} else {
    $user_name = "Administrateur";
}

// Récupérer tous les patients
$stmtP = $pdo->query("SELECT id, first_name, last_name, date_of_birth, blood_group, address, phone FROM patients");
$all_patients = $stmtP->fetchAll(PDO::FETCH_ASSOC);

// Récupérer tous les médecins
$stmtD = $pdo->query("SELECT id, first_name, last_name, specialty, office_address, phone FROM doctors");
$all_doctors = $stmtD->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Annuaire Global - Suivi Santé</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .nav-tabs-modern .nav-link {
            color: var(--text-muted);
            border: none;
            border-bottom: 3px solid transparent;
            font-weight: 500;
            padding: 10px 20px;
            transition: var(--transition-smooth);
        }
        .nav-tabs-modern .nav-link:hover {
            color: var(--primary-color);
            border-color: rgba(79, 70, 229, 0.3);
        }
        .nav-tabs-modern .nav-link.active {
            color: var(--primary-color) !important;
            border-bottom: 3px solid var(--primary-color);
            background: transparent;
        }
        .user-row {
            transition: background 0.2s ease;
        }
        .user-row:hover {
            background: rgba(255, 255, 255, 0.9) !important;
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
            <h2 style="font-weight: 700; color: var(--secondary-color);"><i class="fa-solid fa-globe me-2"></i>Annuaire Global</h2>
            <p class="text-muted fs-5 mb-0">Vue globale sur les membres inscrits sur la plateforme.</p>
        </div>
        
        <!-- Navigation Onglets -->
        <ul class="nav nav-tabs nav-tabs-modern mb-4 fade-up delay-2" id="directoryTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="patients-tab" data-bs-toggle="tab" data-bs-target="#patients" type="button" role="tab" aria-controls="patients" aria-selected="true"><i class="fa-solid fa-hospital-user me-2"></i>Liste des Patients (<?php echo count($all_patients); ?>)</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="doctors-tab" data-bs-toggle="tab" data-bs-target="#doctors" type="button" role="tab" aria-controls="doctors" aria-selected="false"><i class="fa-solid fa-user-doctor me-2"></i>Liste des Praticiens (<?php echo count($all_doctors); ?>)</button>
            </li>
        </ul>

        <div class="tab-content fade-up delay-3" id="directoryTabsContent">
            <!-- Onglet Patients -->
            <div class="tab-pane fade show active" id="patients" role="tabpanel" aria-labelledby="patients-tab">
                <div class="glass-card p-4">
                    <div class="table-responsive">
                        <table class="table table-borderless align-middle mb-0">
                            <thead class="text-muted" style="border-bottom: 2px solid rgba(203, 213, 225, 0.4); font-family: var(--font-heading);">
                                <tr>
                                    <th class="py-3">Patient</th>
                                    <th class="py-3">Âge</th>
                                    <th class="py-3">Groupe Sanguin</th>
                                    <th class="py-3">Contact</th>
                                    <th class="py-3 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($all_patients) > 0): ?>
                                    <?php foreach ($all_patients as $pat): ?>
                                        <tr class="user-row" style="border-bottom: 1px solid rgba(203, 213, 225, 0.2);">
                                            <td class="py-3">
                                                <div class="d-flex align-items-center gap-3">
                                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($pat['first_name'] . ' ' . $pat['last_name']); ?>&background=random&color=fff&rounded=true&size=40" alt="Avatar">
                                                    <div>
                                                        <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($pat['first_name'] . ' ' . $pat['last_name']); ?></h6>
                                                        <small class="text-muted">ID: #<?php echo str_pad($pat['id'], 4, '0', STR_PAD_LEFT); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-3 text-muted"><?php echo calculateAge($pat['date_of_birth']); ?></td>
                                            <td class="py-3">
                                                <?php if (!empty($pat['blood_group'])): ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2"><?php echo htmlspecialchars($pat['blood_group']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted small">Non renseigné</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3 text-muted small">
                                                <i class="fa-solid fa-phone me-1"></i> <?php echo htmlspecialchars($pat['phone'] ?? '-'); ?>
                                            </td>
                                            <td class="py-3 text-end">
                                                <a href="patient_profile.php?id=<?php echo $pat['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">Voir profil</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">Aucun patient inscrit.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Onglet Médecins -->
            <div class="tab-pane fade" id="doctors" role="tabpanel" aria-labelledby="doctors-tab">
                <div class="glass-card p-4">
                    <div class="table-responsive">
                        <table class="table table-borderless align-middle mb-0">
                            <thead class="text-muted" style="border-bottom: 2px solid rgba(203, 213, 225, 0.4); font-family: var(--font-heading);">
                                <tr>
                                    <th class="py-3">Praticien</th>
                                    <th class="py-3">Spécialité</th>
                                    <th class="py-3">Cabinet</th>
                                    <th class="py-3">Contact</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($all_doctors) > 0): ?>
                                    <?php foreach ($all_doctors as $doc): ?>
                                        <tr class="user-row" style="border-bottom: 1px solid rgba(203, 213, 225, 0.2);">
                                            <td class="py-3">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: rgba(79, 70, 229, 0.1);">
                                                        <i class="fa-solid fa-user-md text-primary"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0 fw-bold">Dr. <?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']); ?></h6>
                                                        <small class="text-muted">ID: #<?php echo str_pad($doc['id'], 4, '0', STR_PAD_LEFT); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-3">
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3"><?php echo htmlspecialchars($doc['specialty'] ?? 'Généraliste'); ?></span>
                                            </td>
                                            <td class="py-3 text-muted small" style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                <i class="fa-solid fa-location-dot me-1"></i> <?php echo htmlspecialchars($doc['office_address'] ?? 'Non renseignée'); ?>
                                            </td>
                                            <td class="py-3 text-muted small">
                                                <i class="fa-solid fa-phone me-1"></i> <?php echo htmlspecialchars($doc['phone'] ?? '-'); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">Aucun médecin inscrit.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="text-center py-4 mt-5" style="border-top: 1px solid rgba(255,255,255,0.3);">
        <p class="mb-0 text-muted fw-medium">&copy; <?php echo date('Y'); ?> Application Suivi Santé. Florida Bellange Olamidé LADEKAN.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
