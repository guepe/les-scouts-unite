# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Stack

WordPress block theme (Full Site Editing: `theme.json`, HTML templates and template parts, block patterns), living in `wordpress/` inside the `template-unite` repository and reusing its fonts and images. Chosen by the user over a classic PHP theme so that animateurs can edit the site in the WordPress editor without code.

## Users

- **Parents of current members**: check the next meetings, camp dates, documents to bring, and who to contact. They visit often, usually on a phone, between other things.
- **Families looking for a scout unit near them**: want to know what the unit is, which section fits their child's age, and how to sign up.
- **Animateurs and the staff d'unité**: maintain the site in the WordPress editor; they are volunteers, not developers.

## Product Purpose

A reusable WordPress theme that any unit of Les Scouts asbl (Fédération des Scouts Baden-Powell de Belgique) can install as its website. It answers four needs, all confirmed as priorities by the user: news and agenda, the section pages, registration, and contact / practical information. Success: a parent finds the next activity or the right contact in seconds, a new family understands how to join, and the staff can keep it current without help.

## Positioning

Not a generic association site: it is the local face of a Les Scouts unit, carrying the federation's identity (logo, toppings, section colours) while being clearly about the unit that installs it, its sections, its people and its place. Nothing unit-specific is hard-coded: the unit's name and tagline come from the WordPress site title and tagline, and every local fact ships as a marked placeholder.

## Operating Context

- Content is written by volunteer animateurs in the WordPress block editor; layouts must survive arbitrary text lengths and missing photos.
- The scout year runs September to June, with weekly meetings, weekend activities, and summer camps per section.
- A unit is usually named by its federation unit code and its locality; the theme reads both from the site title, never from code.

## Capabilities and Constraints

- Sections: Baladins (6–8 ans), Louveteaux (8–12), Éclaireurs (12–16), Pionniers (16–18), each with its own federation colour, logo and topping.
- News / agenda built on WordPress posts; section pages, registration and contact as pages.
- No contact form plugin is assumed; forms are left to the unit's choice of plugin.
- Each unit hosts its own copy; the theme assumes no particular URL.
- Undecided: meeting times, locations, staff names, phone numbers, registration fees. Must not be invented; ship clearly marked placeholders.

## Brand Commitments

The Les Scouts asbl graphic charter in this repository is binding: palette in `scss/_ls-variables.scss` (bleu foncé `#0A4275` as primary, vert base, vert foncé, section colours Baladins `#00A0DE`, Louveteaux `#296F52`, Éclaireurs `#004F9F`, Pionniers `#D51317`), Muli as the text face, and the logos and "toppings" (confetti-like pattern) in `images/`. Language is French (fr-BE), with the federation's informal "tu" voice toward members.

## Evidence on Hand

- Federation and section logos: `images/logos/`
- Toppings per section, white and coloured, three sizes: `images/toppings/`
- One real photo: `images/illustrations/coucher-soleil.png` (camp pioneering tower at sunset)
- Fonts: `fonts/` (Muli, Caveat Brush, Housepaint, Mali, Roboto Mono)
- No unit photos, staff names, dates or testimonials yet: placeholders only.

## Product Principles

1. The next thing that happens is always one tap away.
2. Each section feels like its own place, inside one unit.
3. Editable by a volunteer in the WordPress editor without breaking the design.
4. Federation identity, local voice.
