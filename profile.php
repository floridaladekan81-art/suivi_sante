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

// Traitement du formulaire de mise à jour
if (isset($_POST["update_profile"])) {
    $first_name = trim($_POST["first_name"]);
    $last_name = trim($_POST["last_name"]);
    $date_of_birth = $_POST["date_of_birth"];
    $gender = $_POST["gender"];
    $blood_group = $_POST["blood_group"];
    $phone = trim($_POST["phone"]);
    $address = trim($_POST["address"]);
    $emergency_name = trim($_POST["emergency_contact_name"]);
    $emergency_phone = trim($_POST["emergency_contact_phone"]);

    try {
        $stmt = $pdo->prepare("
            UPDATE patients 
            SET first_name = ?, last_name = ?, date_of_birth = ?, gender = ?, 
                blood_group = ?, phone = ?, address = ?, 
                emergency_contact_name = ?, emergency_contact_phone = ?
            WHERE user_id = ?
        ");
        $stmt->execute([
            $first_name, $last_name, $date_of_birth, 
            empty($gender) ? null : $gender, 
            empty($blood_group) ? null : $blood_group, 
            $phone, $address, $emergency_name, $emergency_phone, 
            $user_id
        ]);
        $success_message = "Votre profil a été mis à jour avec succès.";
    } catch(PDOException $e) {
        $error_message = "Erreur lors de la mise à jour : " . $e->getMessage();
    }
}

// Récupération des données actuelles
$stmt = $pdo->prepare("SELECT * FROM patients WHERE user_id = ?");
$stmt->execute([$user_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

$user_name = htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - Suivi Santé</title>
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
            <h2 style="font-weight: 700; color: var(--primary-color);"><i class="fa-regular fa-id-card me-2"></i>Mon Profil</h2>
            <p class="text-muted fs-5 mb-0">Vos informations personnelles et médicales de base.</p>
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
        
        <div class="row g-4 fade-up delay-2">
            <div class="col-lg-8 mx-auto">
                <div class="glass-card p-4 p-md-5">
                    <form action="profile.php" method="post">
                        
                        <h5 class="mb-4" style="color: var(--primary-color); border-bottom: 2px solid rgba(79, 70, 229, 0.2); padding-bottom: 10px;">Informations Personnelles</h5>
                        
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label class="form-label" for="first_name">Prénom</label>
                                <input type="text" id="first_name" name="first_name" class="form-control form-control-modern" required value="<?php echo htmlspecialchars($patient['first_name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="last_name">Nom</label>
                                <input type="text" id="last_name" name="last_name" class="form-control form-control-modern" required value="<?php echo htmlspecialchars($patient['last_name'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label class="form-label" for="date_of_birth">Date de naissance</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" class="form-control form-control-modern" required value="<?php echo htmlspecialchars($patient['date_of_birth'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="gender">Sexe</label>
                                <select id="gender" name="gender" class="form-control form-control-modern">
                                    <option value="" <?php echo empty($patient['gender']) ? 'selected' : ''; ?>>Non précisé</option>
                                    <option value="male" <?php echo ($patient['gender'] === 'male') ? 'selected' : ''; ?>>Homme</option>
                                    <option value="female" <?php echo ($patient['gender'] === 'female') ? 'selected' : ''; ?>>Femme</option>
                                    <option value="other" <?php echo ($patient['gender'] === 'other') ? 'selected' : ''; ?>>Autre</option>
                                </select>
                            </div>
                        </div>

                        <h5 class="mt-5 mb-4" style="color: var(--primary-color); border-bottom: 2px solid rgba(79, 70, 229, 0.2); padding-bottom: 10px;">Données Médicales</h5>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="blood_group">Groupe Sanguin</label>
                                <select id="blood_group" name="blood_group" class="form-control form-control-modern">
                                    <option value="" <?php echo empty($patient['blood_group']) ? 'selected' : ''; ?>>Inconnu</option>
                                    <?php
                                    $bloodTypes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                    foreach ($bloodTypes as $bg) {
                                        $selected = ($patient['blood_group'] === $bg) ? 'selected' : '';
                                        echo "<option value=\"$bg\" $selected>$bg</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <h5 class="mt-5 mb-4" style="color: var(--primary-color); border-bottom: 2px solid rgba(79, 70, 229, 0.2); padding-bottom: 10px;">Coordonnées & Urgence</h5>

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label class="form-label" for="phone">Numéro de téléphone</label>
                                <input type="tel" id="phone" name="phone" class="form-control form-control-modern" value="<?php echo htmlspecialchars($patient['phone'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label" for="address">Adresse</label>
                            <textarea id="address" name="address" class="form-control form-control-modern" rows="2"><?php echo htmlspecialchars($patient['address'] ?? ''); ?></textarea>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label class="form-label" for="emergency_contact_name">Contact d'urgence (Nom)</label>
                                <input type="text" id="emergency_contact_name" name="emergency_contact_name" class="form-control form-control-modern" value="<?php echo htmlspecialchars($patient['emergency_contact_name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="emergency_contact_phone">Contact d'urgence (Tél)</label>
                                <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" class="form-control form-control-modern" value="<?php echo htmlspecialchars($patient['emergency_contact_phone'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" name="update_profile" class="btn-modern px-5">
                                <i class="fa-solid fa-floppy-disk me-2"></i> Enregistrer les modifications
                            </button>
                        </div>
                    </form>
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
