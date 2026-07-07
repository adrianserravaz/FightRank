# FightRank

FightRank is a custom WordPress plugin for cataloguing UFC fights, fighters and events, with automatic fight scoring, user ratings and a frontend experience built around rankings and event archives.

The project was built as a practical web development portfolio piece: it combines WordPress custom post types, custom metadata, AJAX interactions, admin tooling, CSV imports, PHP templates, JavaScript and responsive CSS.

## Features

- Custom post types for fights, fighters and UFC events.
- Custom taxonomies for weight classes and victory methods.
- Automatic FightRank score based on method, round, knockdowns, significant strikes, submission attempts and title-fight context.
- Logged-in user rating system from 1 to 10, stored in a custom database table.
- AJAX voting flow with nonce validation and live UI updates.
- Admin importer for public UFC CSV data.
- Frontend templates for fight detail pages, fighter profiles, archives, events and the home page.
- Responsive navigation and custom visual styling.

## Tech Stack

- WordPress plugin development
- PHP
- MySQL / WordPress database API
- JavaScript / jQuery
- AJAX
- HTML templates
- CSS

## Project Structure

```text
assets/
  css/fightrank.css
  js/fightrank.js
includes/
  admin-ui.php
  ajax.php
  importer.php
  meta-fields.php
  post-types.php
  ratings.php
  scoring.php
  shortcodes.php
templates/
  archive-fight.php
  archive-fighter.php
  archive-ufc_event.php
  front-page.php
  single-fight.php
  single-fighter.php
  single-ufc_event.php
fightrank.php
```

## Installation

1. Copy this folder into `wp-content/plugins/fightrank-plugin`.
2. Activate `FightRank` from the WordPress admin panel.
3. Go to `Tools > Importar UFC` to download and import public UFC data.
4. Visit the generated fight, fighter and event archives.

## Notes

The importer uses public CSV data from the `Greco1899/scrape_ufc_stats` dataset and public image lookup sources. No local WordPress configuration, credentials, database dump or uploaded media are included in this repository.

## Portfolio Context

This repository is intended to show practical full-stack WordPress work: data modelling, admin workflows, frontend rendering, secure AJAX handling, user-generated ratings, import automation and responsive UI implementation.
