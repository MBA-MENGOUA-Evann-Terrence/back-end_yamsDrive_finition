<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Votre mot de passe</title>
</head>
<body>
    <h1>Bonjour {{ $user->prenom }} {{ $user->nom }},</h1>

    <p>Votre compte sur notre plateforme a été créé avec succès.</p>

    <p>Voici vos informations de connexion :</p>

    <ul>
        <li><strong>Nom d'utilisateur :</strong> {{ $user->name }}</li>
        <li><strong>Mot de passe :</strong> {{ $password }}</li>
    </ul>

    <p>Nous vous recommandons de changer votre mot de passe après votre première connexion.</p>

    <p>Cordialement,<br>L'équipe</p>
</body>
</html>
