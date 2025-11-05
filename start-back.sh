#!/bin/bash

# Définir le port Symfony
PORT=8001

# Détecter l'OS
OS="$(uname)"
echo "OS détecté : $OS"

# Lancer le serveur Symfony sur le port 8001 en arrière-plan
symfony server:start --port=$PORT --no-tls &
SYMFONY_PID=$!
sleep 2

# Définir la commande pour ouvrir le navigateur
if [[ "$OS" == "Darwin" ]]; then
    # macOS
    BROWSER_CMD="open -a 'Google Chrome'"
elif [[ "$OS" == "Linux" ]]; then
    # Linux
    BROWSER_CMD="google-chrome"
else
    # Windows via Git Bash / WSL
    BROWSER_CMD="cmd.exe /C start chrome"
fi

# Lancer BrowserSync pour proxy le serveur Symfony avec rafraîchissement automatique
browser-sync start --proxy "127.0.0.1:$PORT" --startPath "/" --files "templates/**/*.twig, src/**/*.php, public/**/*.css, public/**/*.js" --browser "google chrome"

# Quand BrowserSync est fermé, arrêter le serveur Symfony
kill $SYMFONY_PID