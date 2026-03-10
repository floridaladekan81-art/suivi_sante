<?php
session_start();
require_once 'config/database.php';

$success_message = "";
$error_message = "";

if (isset($_POST["submit"])) {
    $fullname = trim($_POST["fullname"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $role = isset($_POST["role"]) ? $_POST["role"] : "patient";

    $errors = array();

    if (empty($fullname) || empty($email) || empty($password)) {
        array_push($errors, "Tous les champs sont obligatoires.");
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        array_push($errors, "L'adresse email n'est pas valide.");
    }
    if (strlen($password) < 6) {
        array_push($errors, "Le mot de passe doit contenir au moins 6 caractères.");
    }

    // Vérifier si l'email existe déjà
    try {
        $pdo = getDBConnection();
        $stmtEmail = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmtEmail->execute([$email]);
        if ($stmtEmail->rowCount() > 0) {
            array_push($errors, "L'email existe déjà.");
        }
    } catch(PDOException $e) {
        array_push($errors, "Erreur de connexion à la base de données.");
    }

    if (count($errors) > 0) {
        $error_message = '<div class="alert alert-danger">' . implode("<br>", $errors) . '</div>';
    } else {
        // Enregistrement
        try {
            // Hachage du mot de passe
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            // Séparation Nom / Prénom (simplifiée pour l'exemple)
            $nameParts = explode(" ", $fullname, 2);
            $firstName = $nameParts[0];
            $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
            // Si pas de nom de famille précisé, on met une valeur par défaut vide pour la BDD
            
            $pdo->beginTransaction();

            // 1. Créer l'utilisateur
            $stmtUser = $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)");
            $stmtUser->execute([$email, $passwordHash, $role]);
            $userId = $pdo->lastInsertId();

            // 2. Créer le profil associé
            if ($role === 'patient') {
                $stmtPatient = $pdo->prepare("INSERT INTO patients (user_id, first_name, last_name, date_of_birth) VALUES (?, ?, ?, '1900-01-01')");
                $stmtPatient->execute([$userId, $firstName, $lastName]);
            } else if ($role === 'doctor') {
                $stmtDoc = $pdo->prepare("INSERT INTO doctors (user_id, first_name, last_name, specialty) VALUES (?, ?, ?, 'Généraliste')");
                $stmtDoc->execute([$userId, $firstName, $lastName]);
            }

            $pdo->commit();

            $success_message = '<div class="alert alert-success">Inscription réussie ! Vous pouvez maintenant vous connecter.</div>';
            
            // Nettoyer POST
            $_POST = array();
            
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_message = '<div class="alert alert-danger">Une erreur est survenue lors de l\'enregistrement.</div>'; // . $e->getMessage() pour debug
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un compte - Suivi Santé</title>
    <!-- Chargement de Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chargement de notre CSS personnalisé -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="animated-bg">
    <div class="auth-wrapper py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-7 col-lg-6 auth-container glass-card p-4 p-md-5 fade-up delay-1">
                    
                    <div class="text-center mb-4">
                        <div class="d-inline-block p-3 rounded-circle mb-3" style="background: rgba(16, 185, 129, 0.1);">
                            <i class="fa-solid fa-user-plus fa-2x" style="color: var(--secondary-color);"></i>
                        </div>
                        <h2 style="color: var(--primary-color); font-weight: 700;">Rejoignez-nous</h2>
                        <p class="text-muted">Prenez le contrôle de votre suivi médical</p>
                    </div>
                    
                    <?php 
                    if (!empty($error_message)) {
                        echo str_replace('alert alert-danger', 'alert-modern alert-danger-modern', $error_message);
                    }
                    if (!empty($success_message)) {
                        echo str_replace('alert alert-success', 'alert-modern alert-success-modern', $success_message);
                    }
                    ?>

                    <form action="registration.php" method="post" class="mt-4">
                        <div class="form-group mb-3">
                            <label class="form-label" for="fullname">Nom complet</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background: rgba(255,255,255,0.6); border: 1px solid rgba(203, 213, 225, 0.6); border-right: none;"><i class="fa-regular fa-user text-muted"></i></span>
                                <input type="text" id="fullname" class="form-control form-control-modern border-start-0 ps-0" name="fullname" placeholder="Prénom et Nom" required value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>">         
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label" for="email">Adresse E-mail</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background: rgba(255,255,255,0.6); border: 1px solid rgba(203, 213, 225, 0.6); border-right: none;"><i class="fa-regular fa-envelope text-muted"></i></span>
                                <input type="email" id="email" class="form-control form-control-modern border-start-0 ps-0" name="email" placeholder="votre@email.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-group mb-4">
                            <label class="form-label" for="password">Mot de passe</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background: rgba(255,255,255,0.6); border: 1px solid rgba(203, 213, 225, 0.6); border-right: none;"><i class="fa-solid fa-lock text-muted"></i></span>
                                <input type="password" id="password" class="form-control form-control-modern border-start-0 border-end-0 ps-0" name="password" placeholder="Min. 6 caractères" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button" style="background: rgba(255,255,255,0.6); border: 1px solid rgba(203, 213, 225, 0.6); border-left: none;">
                                    <i class="fa-regular fa-eye text-muted"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label class="form-label d-block">Je suis un(e) :</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="role" id="rolePatient" value="patient" checked>
                                    <label class="form-check-label" for="rolePatient">
                                        Patient
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="role" id="roleDoctor" value="doctor">
                                    <label class="form-check-label" for="roleDoctor">
                                        Professionnel de Santé
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group text-center mt-2">
                            <button type="submit" class="btn-modern w-100" name="submit">
                                Créer mon compte <i class="fa-solid fa-check ms-2"></i>
                            </button>
                        </div>
                        
                        <div class="mt-4 text-center">
                            <span class="text-muted" style="font-size: 0.95rem;">Déjà membre ? <a href="login.php" style="font-weight: 600;">Se connecter</a></span>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.querySelector('.toggle-password');
            const password = document.querySelector('#password');

            if(togglePassword && password) {
                togglePassword.addEventListener('click', function () {
                    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                    password.setAttribute('type', type);
                    this.querySelector('i').classList.toggle('fa-eye');
                    this.querySelector('i').classList.toggle('fa-eye-slash');
                });
            }
        });
    </script>
</body>
</html>