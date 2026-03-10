<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== 'patient') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$success_message = "";
$error_message = "";

$pdo = getDBConnection();

// Récupérer l'ID du patient et son nom
$stmt = $pdo->prepare("SELECT id, first_name, last_name FROM patients WHERE user_id = ?");
$stmt->execute([$user_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    die("Erreur : Profil patient introuvable.");
}
$patient_id = $patient['id'];
$user_name = htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']);

// Ajouter une nouvelle métrique
if (isset($_POST["add_metric"])) {
    $metric_type = $_POST["metric_type"];
    $metric_value = trim($_POST["metric_value"]);
    $measured_at = $_POST["measured_at"];
    $notes = trim($_POST["notes"]);
    
    // Déterminer l'unité selon le type
    $units = [
        'weight' => 'kg',
        'height' => 'cm',
        'blood_pressure' => 'mmHg',
        'heart_rate' => 'bpm',
        'temperature' => '°C'
    ];
    $metric_unit = $units[$metric_type] ?? '';

    if (!empty($metric_value) && !empty($measured_at)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO patient_metrics (patient_id, metric_type, metric_value, metric_unit, measured_at, notes) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$patient_id, $metric_type, $metric_value, $metric_unit, $measured_at, $notes]);
            $success_message = "Donnée ajoutée avec succès.";
        } catch(PDOException $e) {
            $error_message = "Erreur lors de l'ajout : " . $e->getMessage();
        }
    } else {
        $error_message = "Veuillez remplir la valeur et la date.";
    }
}

// Récupérer l'historique des métriques
$stmtM = $pdo->prepare("SELECT * FROM patient_metrics WHERE patient_id = ? ORDER BY measured_at DESC LIMIT 20");
$stmtM->execute([$patient_id]);
$metrics = $stmtM->fetchAll(PDO::FETCH_ASSOC);

// Traduction des types
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

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dossier Médical - Suivi Santé</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
            <h2 style="font-weight: 700; color: var(--primary-color);"><i class="fa-solid fa-file-medical me-2"></i>Mon Dossier Médical</h2>
            <p class="text-muted fs-5 mb-0">Suivez vos constantes de santé au quotidien.</p>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert-modern alert-success-modern mb-4 fade-up delay-1">
                <i class="fa-solid fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert-modern alert-danger-modern mb-4 fade-up delay-1">
                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <div class="row g-4">
            <!-- Formulaire d'ajout -->
            <div class="col-lg-4 mb-4 fade-up delay-2">
                <div class="glass-card p-4 h-100">
                    <h5 class="mb-4" style="color: var(--primary-color);">Nouvelle Constante</h5>
                    <form action="medical_records.php" method="post">
                        
                        <div class="mb-3">
                            <label class="form-label" for="metric_type">Type de mesure</label>
                            <select id="metric_type" name="metric_type" class="form-control form-control-modern" required>
                                <option value="weight">Poids (kg)</option>
                                <option value="blood_pressure">Tension artérielle (mmHg)</option>
                                <option value="heart_rate">Rythme cardiaque (bpm)</option>
                                <option value="temperature">Température (°C)</option>
                                <option value="height">Taille (cm)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="metric_value">Valeur mesurée</label>
                            <input type="text" id="metric_value" name="metric_value" class="form-control form-control-modern" placeholder="Ex: 75.5 ou 120/80" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="measured_at">Date et Heure</label>
                            <input type="datetime-local" id="measured_at" name="measured_at" class="form-control form-control-modern" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="notes">Notes (Optionnel)</label>
                            <textarea id="notes" name="notes" class="form-control form-control-modern" rows="2" placeholder="Sensation, repas précédent..."></textarea>
                        </div>

                        <button type="submit" name="add_metric" class="btn-modern w-100">
                            Ajouter <i class="fa-solid fa-plus ms-2"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Historique -->
            <div class="col-lg-8 fade-up delay-3">
                <div class="glass-card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0" style="color: var(--primary-color);">Historique Récent</h5>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light text-muted" style="font-family: var(--font-heading); font-size: 0.9rem;">
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Valeur</th>
                                    <th>Notes</th>
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
                                                <span class="d-flex align-items-center gap-2">
                                                    <?php echo $metric_icons[$metric['metric_type']] ?? ''; ?>
                                                    <?php echo $metric_labels[$metric['metric_type']] ?? $metric['metric_type']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary-subtle border border-primary-subtle text-primary fw-bold px-2 py-1 fs-6 rounded-3">
                                                    <?php echo htmlspecialchars($metric['metric_value']) . ' ' . $metric['metric_unit']; ?>
                                                </span>
                                            </td>
                                            <td class="text-muted text-truncate" style="max-width: 150px;">
                                                <?php echo htmlspecialchars($metric['notes'] ?? '-'); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <i class="fa-regular fa-folder-open fa-3x mb-3" style="opacity: 0.5;"></i>
                                            <p>Aucune donnée enregistrée pour le moment.</p>
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
    
    <footer class="text-center py-4 mt-5" style="border-top: 1px solid rgba(255,255,255,0.3);">
        <p class="mb-0 text-muted fw-medium">&copy; <?php echo date('Y'); ?> Application Suivi Santé. Florida Bellange Olamidé LADEKAN.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
