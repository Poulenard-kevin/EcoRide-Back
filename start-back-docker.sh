#!/bin/bash

echo "DEBUG: PWD is $(pwd)"
echo "DEBUG: LS -l of BACKEND_DIR:"
ls -ld "${BACKEND_DIR}"
echo "DEBUG: LS -l of DOCKER_COMPOSE_DIR:"
ls -ld "${DOCKER_COMPOSE_DIR}"
echo "DEBUG: Contents of DOCKER_COMPOSE_DIR:"
ls -l "${DOCKER_COMPOSE_DIR}"

# --- Configuration ---
PROJECT_ROOT="/Users/poulenard-kevin/Documents/Cours Studi/ECF/WEB" # Chemin absolu complet
BACKEND_DIR="${PROJECT_ROOT}/EcoRide-Back"
DOCKER_COMPOSE_DIR="${BACKEND_DIR}" # Chemin vers le dossier contenant docker-compose.yml
# SQL_DUMP_FILE="${BACKEND_DIR}/ecf_dump_sf_EcoRide_with_users.sql" # Supprimé
BACKEND_PORT=8000
PHPMYADMIN_PORT=8082
DB_SERVICE_NAME="db_ecoride" # NOM DU SERVICE DE BASE DE DONNÉES DANS DOCKER-COMPOSE.YML
DB_NAME="sf_EcoRide" # Nom de la base de données
DB_USER="kevin" # Utilisateur de la base de données
DB_PASSWORD="kevin_password" # Mot de passe de la base de données

# --- Fonctions utilitaires ---

# Fonction pour détecter l'OS et ouvrir le navigateur
detect_os() {
    case "$(uname -s)" in
        Linux*)     echo "linux";;
        Darwin*)    echo "macos";;
        CYGWIN*|MINGW32*|MSYS*) echo "windows";;
        *)          echo "unknown";;
    esac
}

open_browser() {
    local url=$1
    local os=$(detect_os)
    echo "Ouverture du navigateur..."
    case "$os" in
        linux)      xdg-open "$url" >/dev/null 2>&1;;
        macos)      open "$url" >/dev/null 2>&1;;
        windows)    start "$url" >/dev/null 2>&1;;
        *)          echo "Impossible d'ouvrir le navigateur automatiquement sur ce système ($os). Veuillez ouvrir manuellement : $url";;
    esac
}

# Fonction pour vérifier si un port est utilisé
is_port_in_use() {
    local port=$1
    lsof -i :$port -sTCP:LISTEN -t >/dev/null
    return $?
}

# Fonction pour attendre que la base de données soit saine
# Cette fonction est toujours utile pour s'assurer que la DB est vraiment prête
# avant de lancer des commandes Symfony, même si Docker Compose gère l'ordre de démarrage.
wait_for_db() {
    local service_name=$1
    local compose_dir=$2
    echo "Attente que le service Docker '$service_name' soit sain..."
    for i in $(seq 1 60); do # Attendre jusqu'à 5 minutes (60 * 5 secondes)
        # Utilise 'docker compose ps -q' pour obtenir l'ID du conteneur
        DB_CONTAINER_ID=$(cd "$compose_dir" && docker compose ps -q "$service_name" 2>/dev/null)
        if [ -n "$DB_CONTAINER_ID" ]; then
            HEALTH_STATUS=$(docker inspect --format='{{.State.Health.Status}}' "$DB_CONTAINER_ID" 2>/dev/null)
            if [ "$HEALTH_STATUS" = "healthy" ]; then
                echo "Le service Docker '$service_name' est sain."
                return 0
            fi
        fi
        echo "Le service Docker '$service_name' n'est pas encore sain, attente... ($i/60)"
        sleep 5
    done
    echo "Erreur : Le service Docker '$service_name' n'est pas devenu sain à temps."
    return 1
}

# Fonction pour arrêter les services Docker et Symfony
cleanup() {
    echo -e "\nArrêt des services..."
    # Arrêter les conteneurs Docker
    if [ -d "$DOCKER_COMPOSE_DIR" ]; then
        echo "Arrêt des services Docker..."
        (cd "$DOCKER_COMPOSE_DIR" && docker compose down)
    fi

    # Arrêter le serveur Symfony si running
    if pgrep -f "symfony serve" > /dev/null; then
        echo "Arrêt du serveur Symfony..."
        pkill -f "symfony serve"
    fi
    echo "Tous les services ont été arrêtés."
    exit 0
}

# Piège Ctrl+C pour appeler la fonction cleanup
trap cleanup SIGINT

# --- Script principal ---

echo "--- Démarrage du projet EcoRide ---"

# 1. Vérifier et libérer les ports si nécessaire
# On vérifie les ports avant de lancer Docker Compose pour éviter des erreurs
if is_port_in_use "$PHPMYADMIN_PORT"; then
    echo "Le port $PHPMYADMIN_PORT est déjà utilisé."
    echo "Veuillez libérer le port $PHPMYADMIN_PORT ou modifier la configuration de phpMyAdmin."
    echo "Pour libérer le port, vous pouvez utiliser : sudo lsof -i :$PHPMYADMIN_PORT et kill -9 <PID>"
    exit 1
fi

if is_port_in_use "$BACKEND_PORT"; then
    echo "Le port $BACKEND_PORT est déjà utilisé."
    echo "Veuillez libérer le port $BACKEND_PORT ou modifier la configuration du backend."
    echo "Pour libérer le port, vous pouvez utiliser : sudo lsof -i :$BACKEND_PORT et kill -9 <PID>"
    exit 1
fi


# 2. Démarrer les services Docker (db, phpmyadmin)
echo "Démarrage des services Docker (db_ecoride, phpmyadmin_ecoride) via ${DOCKER_COMPOSE_DIR}/docker-compose.yml..."
if [ ! -d "$DOCKER_COMPOSE_DIR" ]; then
    echo "Erreur : Le dossier Docker Compose '${DOCKER_COMPOSE_DIR}' n'existe pas."
    exit 1
fi

# Utilise 'docker compose up -d --wait' pour attendre que les services soient sains
# L'option --wait attend que les services soient "healthy" si un healthcheck est défini
(cd "$DOCKER_COMPOSE_DIR" && docker compose up -d --remove-orphans --wait)
if [ $? -ne 0 ]; then
    echo "Erreur lors du démarrage des services Docker."
    cleanup
    exit 1
fi
echo "Services Docker démarrés et sains."

# 3. Exécuter les migrations Doctrine (si nécessaire)
echo "Exécution des migrations Doctrine..."
(cd "$BACKEND_DIR" && php bin/console doctrine:migrations:migrate --no-interaction)
if [ $? -ne 0 ]; then
    echo "Erreur lors de l'exécution des migrations Doctrine."
    cleanup
    exit 1
fi
echo "Migrations Doctrine exécutées."

# 4. Démarrer le serveur Symfony
echo "Démarrage du serveur Symfony..."
if pgrep -f "symfony serve" > /dev/null; then
    echo "Le serveur Symfony est déjà en cours d'exécution."
else
    (cd "$BACKEND_DIR" && symfony serve -d --port="$BACKEND_PORT")
    if [ $? -ne 0 ]; then
        echo "Erreur lors du démarrage du serveur Symfony."
        cleanup
        exit 1
    fi
fi
echo "Serveur Symfony démarré."

# 5. Attendre que le backend soit accessible
echo "Attente que le backend soit accessible sur http://localhost:$BACKEND_PORT..."
while ! curl -s http://localhost:"$BACKEND_PORT" > /dev/null; do
    sleep 2
done
echo "Backend accessible."

# 6. Ouvrir le navigateur
open_browser "http://localhost:$BACKEND_PORT"
open_browser "http://localhost:$PHPMYADMIN_PORT"

echo "Le projet EcoRide est maintenant en cours d'exécution."
echo "Backend: http://localhost:$BACKEND_PORT"
echo "phpMyAdmin: http://localhost:$PHPMYADMIN_PORT"
echo "Appuie sur Ctrl+C pour arrêter tous les services."

# Garder le script en vie pour que le trap fonctionne
wait