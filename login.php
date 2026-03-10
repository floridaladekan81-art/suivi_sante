<?php
session_start();
require_once 'config/database.php';

// Si l'utilisateur est déjà connecté, on le redirige
if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit();
}

$error_message = "";

if (isset($_POST["login"])) {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (empty($email) || empty($password)) {
        $error_message = '<div class="alert alert-danger">Veuillez remplir tous les champs.</div>';
    } else {
        try {
            $pdo = getDBConnection();
            // On récupère l'utilisateur par son email
            $stmt = $pdo->prepare("SELECT id, email, password_hash, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Vérifier le mot de passe
                if (password_verify($password, $user['password_hash'])) {
                    // Mot de passe correct, on démarre la session
                    $_SESSION["user_id"] = $user['id'];
                    $_SESSION["user_role"] = $user['role'];
                    $_SESSION["user_email"] = $user['email'];
                    
                    // Selon le rôle, on pourrait rediriger vers des pages différentes
                    // Pour le moment tout le monde va sur le dashboard
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error_message = '<div class="alert alert-danger">Mot de passe incorrect.</div>';
                }
            } else {
                $error_message = '<div class="alert alert-danger">Aucun compte trouvé avec cet email.</div>';
            }
        } catch(PDOException $e) {
            $error_message = '<div class="alert alert-danger">Erreur de connexion à la base de données.</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Suivi Santé</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome pour de jolies icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="animated-bg">
    <div class="auth-wrapper">
        <div class="auth-container glass-card p-5 fade-up delay-1">
            <div class="text-center mb-5">
                <div class="d-inline-block p-3 rounded-circle mb-3" style="background: rgba(79, 70, 229, 0.1);">
                    <i class="fa-solid fa-heart-pulse fa-2x" style="color: var(--primary-color);"></i>
                </div>
                <h2 style="color: var(--primary-color); font-weight: 700;">Content de vous revoir</h2>
                <p class="text-muted">Connectez-vous à votre espace de suivi santé</p>
            </div>
            
            <?php 
            if (!empty($error_message)) {
                // Remplacement de la classe alerte pour utiliser l'alerte moderne
                echo str_replace('alert alert-danger', 'alert-modern alert-danger-modern', $error_message);
            }
            ?>

            <form action="login.php" method="post" class="mt-4">
                <div class="form-group mb-4">
                    <label class="form-label" for="email">Adresse E-mail</label>
                    <div class="input-group">
                        <span class="input-group-text" style="background: rgba(255,255,255,0.6); border: 1px solid rgba(203, 213, 225, 0.6); border-right: none;"><i class="fa-regular fa-envelope text-muted"></i></span>
                        <input type="email" id="email" class="form-control form-control-modern border-start-0 ps-0" name="email" placeholder="votre@email.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group mb-5">
                    <div class="d-flex justify-content-between">
                        <label class="form-label" for="password">Mot de passe</label>
                        <small><a href="#" class="text-decoration-none" style="color: var(--primary-color); font-weight: 500;">Oublié ?</a></small>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text" style="background: rgba(255,255,255,0.6); border: 1px solid rgba(203, 213, 225, 0.6); border-right: none;"><i class="fa-solid fa-lock text-muted"></i></span>
                        <input type="password" id="password" class="form-control form-control-modern border-start-0 ps-0" name="password" placeholder="••••••••" required>
                    </div>
                </div>
                
                <div class="form-group text-center">
                    <button type="submit" class="btn-modern w-100" name="login">
                        Se connecter <i class="fa-solid fa-arrow-right ms-2"></i>
                    </button>
                </div>
                
                <div class="mt-4 text-center">
                    <span class="text-muted" style="font-size: 0.95rem;">Nouveau ici ? <a href="registration.php" style="font-weight: 600;">Créer un compte</a></span>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
