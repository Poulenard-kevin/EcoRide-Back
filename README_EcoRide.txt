Titre : EcoRide — Instructions pour l’évaluateur

Résumé
Ce package contient l’application EcoRide (frontend + backend API) et la base de données MySQL pré‑remplie (sf_EcoRide). Le dump inclus contient déjà trois comptes de test (admin / employé / utilisateur).

Prérequis
- Docker & Docker Compose installés.
- Ports attendus : frontend 3000, backend 8000, phpMyAdmin 8082.

Fichiers fournis
- ecf_dump_sf_EcoRide_with_users.sql (dump complet avec utilisateurs)
- add_users.sql (INSERTs utilisateurs — optionnel)
- ecoride-docker/docker-compose.yml
- README_EcoRide.txt

Démarrage des services
1) Depuis le dossier du projet :
   docker compose -f ecoride-docker/docker-compose.yml up -d

Accès utiles
- Frontend : http://localhost:3000
- Backend (login) : http://127.0.0.1:8000/login
- phpMyAdmin : http://localhost:8082

Importer la base (méthodes)
- Option A — CLI (recommandée si familiarisé) :
  docker exec -i <db_container_name> mysql -u root -p'rootpassword_ecoride' sf_EcoRide < ecf_dump_sf_EcoRide_with_users.sql

- Option B — phpMyAdmin (interface) :
  1. Ouvrir http://localhost:8082
  2. Se connecter en root : user `root`, password `rootpassword_ecoride`
  3. Si la base n’existe pas, la créer `sf_EcoRide` (collation utf8mb4)
  4. Onglet Importer → choisir `ecf_dump_sf_EcoRide_with_users.sql` (ou `.gz`) → Exécuter

Remarque : si vous utilisez `add_users.sql` séparément, ne l’exécuter qu’une seule fois (éviter doublons).

Comptes créés (pour tests)
- Admin
  - email : admin@example.com
  - mot de passe : AdminPass!2025
  - rôles : ROLE_USER, ROLE_EMPLOYE, ROLE_ADMIN

- Employé
  - email : employe@example.com
  - mot de passe : EmployerPass!2025
  - rôles : ROLE_USER, ROLE_EMPLOYE

- Utilisateur simple
  - email : user@example.com
  - mot de passe : UserPass!2025
  - rôles : ROLE_USER

Test rapide après import
- Vérifier tables : phpMyAdmin → sf_EcoRide → Structure
- Vérifier utilisateurs : phpMyAdmin → SELECT * FROM utilisateur
- Connexion back-office : http://127.0.0.1:8000/login (utiliser les identifiants ci‑dessus)
- Tester API (si token required) :
  curl -H "Authorization: Bearer <api_token>" http://127.0.0.1:8000/api/route_protégée

Commandes utiles (exemples)
- Lister conteneurs :
  docker ps

- Import via CLI (exemple) :
  docker exec -i ecoride-docker160533-db_ecoride-1 mysql -u root -prootpassword_ecoride < ecf_dump_sf_EcoRide_with_users.sql

- Vérifier utilisateurs via CLI :
  docker exec -it ecoride-docker160533-db_ecoride-1 \
    mysql -u root -prootpassword_ecoride -e "USE sf_EcoRide; SELECT id, prenom, nom, email, roles, api_token FROM utilisateur;"

Dépannage courant
- Erreur d’import CREATE USER (#1227) → se connecter en root pour importer (ou exécuter create_user.sql si fourni).
- Problèmes de login → vérifier que les migrations/fixtures n’ont pas écrasé les comptes ; vérifier hashing (si app utilise autre hasher).
- Limite d’upload phpMyAdmin → utiliser import CLI.

Sécurité (important)
- Ne pas transmettre le mot de passe root publiquement. Pour évaluation, vous pouvez transmettre uniquement les comptes de test (admin, employe, user) et la procédure d’import.
- Après l’évaluation, changez les mots de passe si le dump a été partagé.

Contact
Pour toute question technique pendant l’évaluation, contacter l’auteur (indiquer ton email).
