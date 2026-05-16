# WebMobUi — Application de sondage

Projet fullstack Laravel + Vue.js réalisé dans le cadre du cours WebMobUI (HEIG-VD).

## Stack technique

- **Backend** : Laravel 12, SQLite, Sanctum
- **Frontend** : Vue.js 3 (Composition API), Tailwind CSS, Vite

## Installation

```bash
git clone https://github.com/Raniambrk/WebMobUi.git
cd WebMobUi
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
npm install
```

## Lancer le projet

```bash
# Terminal 1
php artisan serve

# Terminal 2
npm run dev
```

Ouvrir : http://localhost:8000

## Fonctionnalités

- Dashboard : liste, création, édition, suppression de sondages
- Lancement d'un sondage depuis le dashboard
- Lien de partage avec token unique
- Page de vote accessible sans compte
- Vote simple ou multiple selon la configuration
- Résultats en temps réel via polling (toutes les 5s)
- Graphique en barres des résultats
- Sondage clôturé automatiquement à la date de fin
- Résultats publics ou privés selon la configuration