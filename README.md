# TRMNL Météo-France Forecast

Backend PHP et layouts TRMNL permettant d'afficher les prévisions météorologiques de Météo-France sur un écran TRMNL.

Le projet adapte le plugin TRMNL **Open-Meteo Hourly Weather Forecast** pour utiliser les données de prévision de Météo-France tout en conservant le système d'icônes compatible avec les codes météo WMO.

## Fonctionnement

Le plugin est constitué de deux parties :

1. un backend PHP qui interroge Météo-France et transforme les données dans un format adapté au plugin TRMNL ;
2. quatre layouts Liquid utilisés par TRMNL pour les différents formats d'affichage.

Architecture générale :

```text
Météo-France
     |
     v
MeteoFranceForecastClient
     |
     v
ForecastDataset
     |
     v
TrmnlForecastAdapter
     |
     v
forecast.inscog.eu
     |
     v
TRMNL
```

Le backend fournit notamment :

- les conditions actuelles ;
- les prévisions horaires ;
- les prévisions quotidiennes ;
- les températures minimales et maximales ;
- les heures de lever et coucher du soleil ;
- les codes météo Météo-France originaux ;
- leur conversion en codes météo WMO ;
- les descriptions météorologiques en français.

## Structure du projet

```text
.
├── composer.json
├── composer.lock
├── dev/
│   ├── test_adapter.php
│   ├── test_dataset.php
│   └── test_forecast.php
├── public/
│   └── index.php
├── src/
│   ├── ForecastCache.php
│   ├── ForecastDataset.php
│   ├── ForecastDay.php
│   ├── ForecastHour.php
│   ├── MeteoFranceForecastClient.php
│   └── TrmnlForecastAdapter.php
├── trmnl/
│   ├── full.liquid
│   ├── half_horizontal.liquid
│   ├── half_vertical.liquid
│   └── quadrant.liquid
├── var/
│   └── cache/
└── vendor/
```

Les fichiers `trmnl/*.liquid` constituent la copie versionnée des markups utilisés dans la configuration du plugin TRMNL.

Ils ne sont pas exécutés par le serveur PHP.

## Prérequis

- PHP 8.3 ou supérieur
- Composer
- extension PHP cURL
- accès au service de prévisions Météo-France
- variable d'environnement `METEOFRANCE_API_TOKEN`

## Installation locale

Cloner le dépôt :

```bash
git clone https://github.com/lbonnefond/TRMNL-MeteoFrance-Forecast.git
cd TRMNL-MeteoFrance-Forecast
```

Installer les dépendances :

```bash
composer install
```

Créer le répertoire de cache si nécessaire :

```bash
mkdir -p var/cache
```

Définir le token Météo-France :

```bash
export METEOFRANCE_API_TOKEN='VOTRE_TOKEN'
```

Le token ne doit jamais être enregistré dans le dépôt Git.

Lancer le serveur PHP de développement :

```bash
php -S 127.0.0.1:8080 -t public
```

Tester le backend :

```bash
curl -s \
  "http://127.0.0.1:8080/?lat=48.5839&lon=7.7455" \
  | python3 -m json.tool
```

## API

Le backend accepte deux paramètres :

| Paramètre | Description |
|-----------|-------------|
| `lat` | Latitude |
| `lon` | Longitude |

Exemple :

```text
https://forecast.inscog.eu/?lat=48.5839&lon=7.7455
```

La réponse est au format JSON.

Exemple simplifié :

```json
{
  "latitude": 48.583085,
  "longitude": 7.746694,
  "elevation": 142,
  "timezone": "Europe/Paris",
  "current": {
    "time": "2026-08-19T18:00",
    "temperature_2m": 30.4,
    "is_day": 1,
    "weather_code": 0,
    "weather_code_meteofrance": "p1j",
    "weather_description": "Ensoleillé"
  }
}
```

`weather_code` contient le code WMO utilisé par les layouts et le mapping des icônes.

`weather_code_meteofrance` conserve le code météo Météo-France original.

## Configuration TRMNL

### Polling URLs

Le plugin utilise deux sources :

```text
https://forecast.inscog.eu/?lat={{lat}}&lon={{lon}}
https://andi4000.github.io/weather-app-assets/open-meteo/iconsmapping.v1.json
```

La première URL fournit les prévisions Météo-France.

La seconde fournit uniquement le mapping entre les codes WMO et les fichiers d'icônes.

### Polling Verb

```text
GET
```

### Polling Headers

Aucun header particulier n'est nécessaire.

## Form Fields

Configuration utilisée dans TRMNL :

```yaml
- keyname: plugin_info
  name: Prévisions Météo-France
  field_type: author_bio
  category: environment
  description: 'Prévisions météorologiques horaires et quotidiennes basées sur les données Météo-France. Le plugin utilise les coordonnées latitude/longitude du lieu choisi.'
  github_url: https://github.com/lbonnefond/TRMNL-MeteoFrance-Forecast

- keyname: lat
  optional: false
  field_type: string
  name: Latitude
  description: Latitude du lieu
  help_text: 'Exemple pour Strasbourg : 48.5839'
  placeholder: 48.5839
  default: 48.5839

- keyname: lon
  optional: false
  field_type: string
  name: Longitude
  description: Longitude du lieu
  help_text: 'Exemple pour Strasbourg : 7.7455'
  placeholder: 7.7455
  default: 7.7455

- keyname: disp_full_today_max_hours
  field_type: number
  name: "Full / Half Horizontal : prévisions horaires"
  description: "Nombre de prévisions horaires à afficher après l'heure actuelle."
  optional: false
  default: 7

- keyname: disp_full_tomorrow_hours
  field_type: string
  name: "Full : heures de demain"
  description: "Heures de demain à afficher dans le layout Full, séparées par des virgules."
  help_text: "Exemple : 7,9,11,13,15,17,19"
  optional: false
  default: '7,9,11,13,15,17,19'

- keyname: disp_half_vertical_today_max_hours
  field_type: number
  name: "Half Vertical : prévisions horaires"
  description: "Nombre de prévisions horaires à afficher après l'heure actuelle."
  optional: false
  default: 5
```

## Layouts TRMNL

Quatre formats sont disponibles :

| Fichier | Layout TRMNL |
|---------|--------------|
| `trmnl/full.liquid` | Full |
| `trmnl/half_horizontal.liquid` | Half Horizontal |
| `trmnl/half_vertical.liquid` | Half Vertical |
| `trmnl/quadrant.liquid` | Quadrant |

Les layouts utilisent :

- `IDX_0` : données fournies par `forecast.inscog.eu` ;
- `IDX_1` : mapping des icônes météo.

Les textes affichés sont directement en français.

## Déploiement Hostinger

Le backend actuellement déployé est accessible à :

```text
https://forecast.inscog.eu/
```

Le dépôt Git est cloné dans le répertoire du site sur Hostinger.

Pour mettre à jour le serveur après un push GitHub :

```bash
git pull origin main
```

Puis vérifier :

```bash
git status
git log -1 --oneline
```

Les fichiers `.htaccess` utilisés pour la configuration Hostinger sont volontairement exclus de Git :

```text
/.htaccess
/public/.htaccess
```

Ils doivent donc rester présents sur le serveur lors des mises à jour.

Le cache doit être accessible en écriture :

```bash
mkdir -p var/cache
chmod 775 var/cache
```

## Mise à jour du code

Workflow recommandé :

### Sur la machine locale

```bash
git status
git diff

git add <fichiers>
git commit -m "Description de la modification"
git push
```

### Sur Hostinger

```bash
git pull origin main
```

## Sécurité

Le token utilisé pour accéder au service Météo-France n'est pas stocké dans le dépôt.

Le backend le récupère via :

```text
METEOFRANCE_API_TOKEN
```

Il ne faut jamais :

- inscrire le token dans le code source ;
- l'ajouter au README ;
- le committer dans Git ;
- l'exposer dans la configuration TRMNL.

## Crédits

Ce projet est basé sur le plugin TRMNL **Open-Meteo Hourly Weather Forecast** de andi4000, dont les layouts et le système d'icônes ont servi de base à l'adaptation Météo-France.

Mapping des icônes :

```text
https://andi4000.github.io/weather-app-assets/open-meteo/iconsmapping.v1.json
```

Les données météorologiques sont fournies par Météo-France.