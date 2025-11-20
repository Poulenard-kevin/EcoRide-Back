#!/usr/bin/env bash
set -euo pipefail

# Port Symfony
PORT=8000

# Chemin du projet (script doit être placé dans le dossier backend)
cd "$(dirname "$0")"

echo "Start-back: vérification dépendances et démarrage du backend..."

# Vérifier si le port est déjà utilisé
if lsof -iTCP:"$PORT" -sTCP:LISTEN -t >/dev/null 2>&1; then
  PID_IN_USE=$(lsof -iTCP:"$PORT" -sTCP:LISTEN -t)
  echo "Le port $PORT est déjà utilisé par PID $PID_IN_USE. Abandon."
  exit 1
fi

# Installer les dépendances composer si nécessaire
if [ -f composer.json ]; then
  echo "Composer : installation des dépendances (si nécessaire)..."
  # Ne bloquer pas complètement si composer rencontre un warning -> essayer d'installer mais ne pas échouer le script sur des warnings
  composer install --no-interaction --prefer-dist || true
fi

START_METHOD=""
PHP_PID=""
LOGFILE=""

cleanup() {
  echo
  echo "Nettoyage..."
  if [ "$START_METHOD" = "symfony" ]; then
    echo "  Arrêt du serveur symfony..."
    symfony server:stop >/dev/null 2>&1 || true
  elif [ "$START_METHOD" = "php" ] && [ -n "$PHP_PID" ]; then
    echo "  Arrêt du serveur PHP (PID $PHP_PID)..."
    kill "$PHP_PID" 2>/dev/null || true
  fi
  if [ -n "$LOGFILE" ] && [ -f "$LOGFILE" ]; then
    rm -f "$LOGFILE"
  fi
  echo "Terminé."
}
trap cleanup EXIT

# Démarrage via symfony CLI si disponible
if command -v symfony >/dev/null 2>&1; then
  echo "Utilisation de symfony CLI..."
  # Stop any existing local server so we start clean
  symfony server:stop >/dev/null 2>&1 || true

  # Start in daemon mode (mais on streamera ensuite les logs pour rester en foreground)
  symfony server:start -d --port="$PORT" --no-tls
  START_METHOD="symfony"

  # Attendre que le serveur écoute (timeout)
  echo -n "Attente du démarrage du serveur symfony"
  retries=0
  until lsof -iTCP:"$PORT" -sTCP:LISTEN -t >/dev/null 2>&1 || [ $retries -ge 20 ]; do
    printf "."
    sleep 0.3
    retries=$((retries+1))
  done
  echo

  if ! lsof -iTCP:"$PORT" -sTCP:LISTEN -t >/dev/null 2>&1; then
    echo "Le serveur symfony n'a pas démarré correctement. Consulte les logs avec : symfony server:log"
    exit 2
  fi

  echo "Backend démarré via symfony CLI -> http://127.0.0.1:$PORT"
  echo "Streaming des logs (Ctrl+C pour arrêter et arrêter le serveur)..."
  # Stream les logs en foreground : permettra de garder le script en cours et d'arrêter proprement via cleanup
  symfony server:log

else
  # Fallback : php -S
  echo "symfony CLI introuvable — fallback sur php -S"
  LOGFILE="$(mktemp /tmp/php-server-XXXX.log)"
  nohup php -S 127.0.0.1:"$PORT" -t public >"$LOGFILE" 2>&1 &
  PHP_PID=$!
  START_METHOD="php"
  sleep 0.5

  if kill -0 "$PHP_PID" >/dev/null 2>&1 && lsof -iTCP:"$PORT" -sTCP:LISTEN -t >/dev/null 2>&1; then
    echo "Backend démarré (php -S) -> http://127.0.0.1:$PORT"
    echo "Logs : $LOGFILE (Ctrl+C pour arrêter et tuer le serveur)"
    # Afficher les logs en temps réel
    tail -f "$LOGFILE" &
    TAIL_PID=$!
    wait "$PHP_PID"
    # Si on sort du wait, killer le tail
    kill "$TAIL_PID" 2>/dev/null || true
  else
    echo "Le serveur PHP n'a pas réussi à démarrer. Logs :"
    sed -n '1,200p' "$LOGFILE" || true
    exit 2
  fi
fi

# fin du script (cleanup sera appelé sur EXIT)