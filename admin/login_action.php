<?php
// admin/login_action.php
session_start();
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // On cherche l'admin dans la base
    $stmt = $pdo->prepare("SELECT * FROM administrateurs WHERE identifiant = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    // TEST TEMPORAIRE : On compare directement sans hachage ou on force si c'est les bons identifiants
    if (($admin && password_verify($password, $admin['mot_de_passe'])) || ($username === 'bossngoy32@gmail.com' && $password === 'ngoy6464')) {
        $_SESSION['admin_logged'] = true;
        header('Location: dashboard.php');
        exit;
    } else {
        header('Location: index.php?error=1');
        exit;
    }
}