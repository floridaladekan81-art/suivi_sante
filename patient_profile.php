<?php
session_start();
require_once 'config/database.php';

// Access control: User must be logged in as a doctor or admin
if (!isset($_SESSION["user_id"]) || ($_SESSION["user_role"] !== 'doctor' && $_SESSION["user_role"] !== 'admin')) {
    header("Location: dashboard.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$user_role = $_SESSION["user_role"];
$pdo = getDBConnection();

$patient_id_to_view = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($patient_id_to_view === 0) {
    die("ID du patient non spécifié.");
}

$has_access = false;

// If the user is a doctor, verify they have been granted access
if ($user_role === 'doctor') {
    $stmtDoctor = $pdo->prepare("SELECT id, first_name, last_name FROM doctors WHERE user_id = ?");
    $stmtDoctor->execute([$user_id]);
    $doc = $stmtDoctor->fetch(PDO::FETCH_ASSOC);

    if ($doc) {
        $doctor_id = $doc['id'];
        $user_name = "Dr. " . htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']);

        // Check the patient_doctor_access table
        $stmtAccess = $pdo->prepare("SELECT id FROM patient_doctor_access WHERE doctor_id = ? AND patient_id = ?");
        $stmtAccess->execute([$doctor_id, $patient_id_to_view]);
        if ($stmtAccess->fetch()) {
            $has_access = true;
        }
    }
} else if ($user_role === 'admin') {
    // Admins have access to everything
    $has_access = true;
    $user_name = "Administrateur";
}

if (!$has_access) {
    die("
    <div style='font-family: system-ui; text-align: center; margin-top: 50px; color: #dc3545;'>
        <h2>Accès Refusé</h2>
        <p>Vous n'avez pas l'autorisation de consulter le dossier de ce patient.</p>
        <p>Le patient doit d'abord vous accorder l'accès depuis son compte.</p>
        <a href='dashboard.php' style='display: inline-block; padding: 10px 20px; background: #6366f1; color: white; text-decoration: none; border-radius: 8px;'>Retour</a>
    </div>
    ");
}

// 1. Fetch Patient Data
$stmtPatient = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
$stmtPatient->execute([$patient_id_to_view]);
$patient = $stmtPatient->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    die("Patient introuvable.");
}

// 2. Fetch Health Metrics
$stmtMetrics = $pdo->prepare("SELECT * FROM patient_metrics WHERE patient_id = ? ORDER BY measured_at DESC LIMIT 20");
$stmtMetrics->execute([$patient_id_to_view]);
$metrics = $stmtMetrics->fetchAll(PDO::FETCH_ASSOC);

// 3. Fetch Medical Records (Antecedents)
$stmtRecords = $pdo->prepare("SELECT * FROM medical_records WHERE patient_id = ? ORDER BY date_recorded DESC, created_at DESC");
$stmtRecords->execute([$patient_id_to_view]);
$records = $stmtRecords->fetchAll(PDO::FETCH_ASSOC);


// Utility Functions & Labels
function calculateAge($dob) {
    if (!$dob || $dob === '1900-01-01') return 'N/A';
    $birthDate = new DateTime($dob);
    $today = new DateTime('today');
    return $birthDate->diff($today)->y . ' ans';
}

$metric_labels = [
    'weight' => 'Poids',
    'height' => 'Taille',
    'blood_pressure' => 'Tension artérielle',
    'heart_rate' => 'Rythme cardiaque',
    'temperature' => 'Température'
];
$metric_icons = [
    'weight' => '<i class="fa-solid fa-weight-scale text-primary"></i>',
    'height' => '<i class="fa-solid fa-ruler-vertical text-info"></i>',
    'blood_pressure' => '<i class="fa-solid fa-droplet text-danger"></i>',
    'heart_rate' => '<i class="fa-solid fa-heart-pulse text-danger"></i>',
    'temperature' => '<i class="fa-solid fa-temperature-half text-warning"></i>'
];

$record_labels = [
    'disease' => 'Maladie',
    'allergy' => 'Allergie',
    'surgery' => 'Chirurgie',
    'treatment' => 'Traitement',
    'vaccine' => 'Vaccin',
    'test_result' => 'Résultat d\'examen'
];
$record_icons = [
    'disease' => '<i class="fa-solid fa-virus text-danger"></i>',
    'allergy' => '<i class="fa-solid fa-hand-dots text-warning"></i>',
    'surgery' => '<i class="fa-solid fa-scissors text-secondary"></i>',
    'treatment' => '<i class="fa-solid fa-pills text-primary"></i>',
    'vaccine' => '<i class="fa-solid fa-syringe text-success"></i>',
    'test_result' => '<i class="fa-solid fa-microscope text-info"></i>'
];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dossier de <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?> - Suivi Santé</title>
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
        .info-label {
            font-size: 0.85rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .info-value {
            font-weight: 600;
            color: #1e293b;
        }
    </style>
</head>
<body class="animated-bg text-dark">
    
    <!-- Navbar Premium -->
    <nav class="navbar navbar-expand-lg navbar-modern shadow-sm">
        <div class="container py-2">
            <a class="navbar-brand-modern text-decoration-none" href="dashboard.php">
                <i class="fa-solid fa-shield-heart"></i> SuiviSanté Professionnel
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="fa-solid fa-bars text-primary"></i>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center gap-3">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link"><i class="fa-solid fa-house"></i> Tableau de bord</a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link fw-medium" style="color: var(--text-main);">
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
            <a href="patients_list.php" class="text-decoration-none text-muted mb-3 d-inline-block"><i class="fa-solid fa-arrow-left me-2"></i>Retour à la liste des patients</a>
            
            <div class="d-flex align-items-center gap-4 mb-4">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($patient['first_name'] . ' ' . $patient['last_name']); ?>&background=random&color=fff&rounded=true&size=100" alt="Avatar" class="shadow rounded-circle border border-4 border-white">
                <div>
                    <h2 style="font-weight: 700; color: var(--primary-color); margin-bottom: 5px;">
                        <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                    </h2>
                    <div class="d-flex gap-3 align-items-center flex-wrap">
                        <span class="badge bg-secondary-subtle text-secondary px-3 py-2 rounded-pill fs-6"><i class="fa-regular fa-calendar me-1"></i> <?php echo calculateAge($patient['date_of_birth']); ?></span>
                        
                        <?php if (!empty($patient['blood_group'])): ?>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill fs-6"><i class="fa-solid fa-droplet me-1"></i> Groupe <?php echo htmlspecialchars($patient['blood_group']); ?></span>
                        <?php endif; ?>
                        
                        <span class="text-success fw-medium"><i class="fa-solid fa-shield-check me-1"></i> Dossier Partagé</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Sidebar: Informations Personnelles -->
            <div class="col-lg-4 mb-4 fade-up delay-2">
                <div class="glass-card p-4 h-100">
                    <h5 class="mb-4 border-bottom pb-2" style="color: var(--primary-color);"><i class="fa-regular fa-address-card me-2"></i>Infos Personnelles</h5>
                    
                    <div class="mb-3">
                        <div class="info-label">Contact</div>
                        <div class="info-value"><i class="fa-solid fa-phone me-2 text-muted"></i> <?php echo htmlspecialchars($patient['phone'] ?? 'Non renseigné'); ?></div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="info-label">Adresse</div>
                        <div class="info-value"><i class="fa-solid fa-location-dot me-2 text-muted"></i> <?php echo htmlspecialchars($patient['address'] ?? 'Non renseignée'); ?></div>
                    </div>

                    <?php if (!empty($patient['emergency_contact_name'])): ?>
                    <h5 class="mb-3 mt-4 border-bottom pb-2" style="color: #F43F5E;"><i class="fa-solid fa-truck-medical me-2"></i>Contact d'Urgence</h5>
                    <div class="p-3 rounded" style="background: rgba(244, 63, 94, 0.05); border-left: 3px solid #F43F5E;">
                        <div class="fw-bold mb-1"><?php echo htmlspecialchars($patient['emergency_contact_name']); ?></div>
                        <div><i class="fa-solid fa-phone me-2 text-muted"></i> <?php echo htmlspecialchars($patient['emergency_contact_phone']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mt-4 pt-3 border-top text-center">
                        <button class="btn btn-outline-primary w-100 mb-2" onclick="window.print()"><i class="fa-solid fa-print me-2"></i>Imprimer le dossier</button>
                    </div>
                </div>
            </div>

            <!-- Main Content: Medical Info -->
            <div class="col-lg-8 fade-up delay-3">
                <div class="glass-card p-0 h-100 overflow-hidden">
                    
                    <!-- Navigation Onglets -->
                    <div class="px-4 pt-3" style="background: rgba(255,255,255,0.5); border-bottom: 1px solid rgba(203, 213, 225, 0.5);">
                        <ul class="nav nav-tabs nav-tabs-modern" id="medicalTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="records-tab" data-bs-toggle="tab" data-bs-target="#records" type="button" role="tab" aria-controls="records" aria-selected="true"><i class="fa-solid fa-notes-medical me-2"></i>Antécédents & Dossier</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="metrics-tab" data-bs-toggle="tab" data-bs-target="#metrics" type="button" role="tab" aria-controls="metrics" aria-selected="false"><i class="fa-solid fa-chart-line me-2"></i>Constantes de Santé</button>
                            </li>
                        </ul>
                    </div>

                    <div class="tab-content p-4" id="medicalTabsContent">
                        
                        <!-- Onglet Antécédents -->
                        <div class="tab-pane fade show active" id="records" role="tabpanel" aria-labelledby="records-tab">
                            <h5 class="mb-4" style="color: var(--primary-color);">Historique Médical du Patient</h5>
                            <div class="row g-3">
                                <?php if (count($records) > 0): ?>
                                    <?php foreach ($records as $record): ?>
                                        <div class="col-md-12">
                                            <div class="p-3 rounded-3" style="background: rgba(255,255,255,0.8); border: 1px solid rgba(203, 213, 225, 0.6); box-shadow: 0 2px 5px rgba(0,0,0,0.02);">
                                                <div class="d-flex align-items-start gap-3">
                                                    <div class="p-2 rounded-circle d-flex align-items-center justify-content-center" style="background: white; width: 45px; height: 45px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                                                        <?php echo $record_icons[$record['record_type']] ?? ''; ?>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                                            <h6 class="mb-0 fw-bold text-dark fs-5"><?php echo htmlspecialchars($record['title']); ?></h6>
                                                            <small class="text-muted"><i class="fa-regular fa-calendar me-1"></i><?php echo $record['date_recorded'] ? date('d/m/Y', strtotime($record['date_recorded'])) : 'Date non précisée'; ?></small>
                                                        </div>
                                                        <div class="d-flex align-items-center gap-2 mb-2">
                                                            <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.75rem;"><?php echo $record_labels[$record['record_type']] ?? $record['record_type']; ?></span>
                                                        </div>
                                                        <?php if (!empty($record['description'])): ?>
                                                            <p class="text-muted mb-0 mt-2 p-2 rounded" style="background: rgba(248, 250, 252, 0.8); font-size: 0.95rem; line-height: 1.5; border-left: 3px solid var(--primary-color);">
                                                                <?php echo nl2br(htmlspecialchars($record['description'])); ?>
                                                            </p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="col-12 text-center py-5 text-muted">
                                        <i class="fa-regular fa-folder-open fa-3x mb-3" style="opacity: 0.5;"></i>
                                        <p>Aucun antécédent ou document enregistré par le patient pour le moment.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Onglet Constantes -->
                        <div class="tab-pane fade" id="metrics" role="tabpanel" aria-labelledby="metrics-tab">
                            <h5 class="mb-4" style="color: var(--primary-color);">Dernières Mesures Enregistrées</h5>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light text-muted" style="font-family: var(--font-heading); font-size: 0.9rem;">
                                        <tr>
                                            <th>Date & Heure</th>
                                            <th>Type de Constante</th>
                                            <th>Valeur Mesurée</th>
                                            <th>Notes / Contexte</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($metrics) > 0): ?>
                                            <?php foreach ($metrics as $metric): ?>
                                                <tr>
                                                    <td>
                                                        <div class="fw-medium text-dark"><?php echo date('d/m/Y', strtotime($metric['measured_at'])); ?></div>
                                                        <small class="text-muted"><?php echo date('H:i', strtotime($metric['measured_at'])); ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="d-flex align-items-center gap-2 fw-medium">
                                                            <?php echo $metric_icons[$metric['metric_type']] ?? ''; ?>
                                                            <?php echo $metric_labels[$metric['metric_type']] ?? $metric['metric_type']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary-subtle border border-primary-subtle text-primary fw-bold px-3 py-2 fs-6 rounded-3">
                                                            <?php echo htmlspecialchars($metric['metric_value']) . ' ' . $metric['metric_unit']; ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-muted" style="max-width: 200px;">
                                                        <?php echo htmlspecialchars($metric['notes'] ?? '-'); ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-5 text-muted">
                                                    <i class="fa-solid fa-chart-line fa-3x mb-3" style="opacity: 0.3;"></i>
                                                    <p>Aucune constante de santé enregistrée récemment par ce patient.</p>
                                                </td>
                                            </tr>
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
    
    <footer class="text-center py-4 mt-5" style="border-top: 1px solid rgba(255,255,255,0.3);">
        <p class="mb-0 text-muted fw-medium">&copy; <?php echo date('Y'); ?> Application Suivi Santé. Florida Bellange Olamidé LADEKAN.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
