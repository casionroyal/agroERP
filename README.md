agroERP est une application web de gestion intégrée (ERP) conçue pour répondre aux besoins opérationnels, administratifs et logistiques des structures du secteur agricole ou agro-industriel.

🛠️ Fonctionnalités et Modules Principaux
La solution s'articule autour de plusieurs modules clés :

Tableau de bord (/modules/dashboard) : Un espace centralisé offrant une vue d'ensemble des indicateurs clés de performance et de l'activité.

Gestion de la production (/modules/production) : Suivi des processus de fabrication, gestion des recettes et création des fiches de production.

Gestion des stocks (/modules/stocks) : Suivi rigoureux des stocks (ajout, modification, suppression et consultation de l'état des stocks en temps réel).

Gestion des ventes et de la facturation (/modules/ventes) : Enregistrement des ventes, édition des factures, gestion des créances clients et suivi commercial.

Gestion des paiements (/modules/paiements) : Suivi des flux financiers et des transactions associées.

Sécurité et Administration : Système d'authentification sécurisé, gestion des profils utilisateurs et vérification par code.

📂 Structure du Projet
Plaintext
agroERP/
├── assets/             # Ressources statiques (CSS, JS, images, logos)
├── config/             # Fichiers de configuration (connexion à la base de données db.php)
├── includes/           # Éléments réutilisables (en-têtes, pieds de page, contrôles d'authentification)
├── modules/            # Modules fonctionnels de l'ERP
│   ├── dashboard/      # Tableau de bord
│   ├── paiements/      # Gestion des paiements
│   ├── production/     # Production et recettes
│   ├── stocks/         # Gestion des stocks (CRUD)
│   └── ventes/         # Ventes, factures et créances
├── index.php           # Page d'accueil de l'application
├── login_page.php      # Page de connexion
└── profile.php         # Gestion du profil utilisateur
⚙️ Prérequis techniques
Un serveur web supportant PHP (ex: Apache / MAMP / XAMPP).

Une base de données relationnelle (MySQL / MariaDB).

🚀 Installation rapide
Clonez ou téléchargez le dépôt du projet dans le répertoire de votre serveur web (par exemple htdocs/agroERP).

Configurez l'accès à votre base de données dans le fichier situé dans config/db.php.

Lancez votre serveur local et accédez à l'URL correspondante (ex: http://localhost/agroERP/login_page.php).
