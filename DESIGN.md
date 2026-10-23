---
name: Les Scouts – Unité
description: The notice board at the door of the local, in the colours of Les Scouts asbl.
colors:
  bleu-fonce: "#0A4275"
  bleu-nuit: "#062F55"
  vert-base: "#95C11F"
  vert-fonce: "#304A3C"
  orange: "#EF7B00"
  rouge: "#D60F3C"
  baladins: "#00A0DE"
  louveteaux: "#296F52"
  eclaireurs: "#004F9F"
  pionniers: "#D51317"
  fond: "#F2F5F8"
  trait: "#DCE4EC"
  a-completer: "#FFF4E5"
  blanc: "#FFFFFF"
typography:
  display:
    fontFamily: "Muli, \"Segoe UI\", Helvetica, Arial, sans-serif"
    fontSize: "5.5rem"
    fontWeight: 900
    lineHeight: 0.95
    letterSpacing: "-0.035em"
  headline:
    fontFamily: "Muli, \"Segoe UI\", Helvetica, Arial, sans-serif"
    fontSize: "3.75rem"
    fontWeight: 900
    lineHeight: 1.05
    letterSpacing: "-0.02em"
  title:
    fontFamily: "Muli, \"Segoe UI\", Helvetica, Arial, sans-serif"
    fontSize: "2.25rem"
    fontWeight: 900
    lineHeight: 1.05
    letterSpacing: "-0.02em"
  title-small:
    fontFamily: "Muli, \"Segoe UI\", Helvetica, Arial, sans-serif"
    fontSize: "1.375rem"
    fontWeight: 900
    lineHeight: 1.05
    letterSpacing: "-0.01em"
  body:
    fontFamily: "Muli, \"Segoe UI\", Helvetica, Arial, sans-serif"
    fontSize: "1.0625rem"
    fontWeight: 400
    lineHeight: 1.6
  body-small:
    fontFamily: "Muli, \"Segoe UI\", Helvetica, Arial, sans-serif"
    fontSize: "0.9375rem"
    fontWeight: 400
    lineHeight: 1.6
  label:
    fontFamily: "Muli, \"Segoe UI\", Helvetica, Arial, sans-serif"
    fontSize: "1rem"
    fontWeight: 800
    lineHeight: 1.2
  manuscrit:
    fontFamily: "\"Caveat Brush\", cursive"
    fontSize: "1.75rem"
    fontWeight: 400
    lineHeight: 1
    letterSpacing: "0"
rounded:
  none: "0"
  punaise: "50%"
spacing:
  "20": "0.5rem"
  "30": "0.75rem"
  "40": "1.25rem"
  "50": "clamp(1.5rem, 3vw, 2.25rem)"
  "60": "clamp(2rem, 5vw, 3.5rem)"
  "70": "clamp(3rem, 7vw, 5.5rem)"
  "80": "clamp(4rem, 10vw, 8rem)"
components:
  button-primary:
    backgroundColor: "{colors.bleu-fonce}"
    textColor: "{colors.blanc}"
    typography: "{typography.label}"
    rounded: "{rounded.none}"
    padding: "0.9rem 1.5rem"
  button-primary-hover:
    backgroundColor: "{colors.vert-fonce}"
    textColor: "{colors.blanc}"
  button-panneau:
    backgroundColor: "{colors.vert-base}"
    textColor: "{colors.bleu-fonce}"
    rounded: "{rounded.none}"
    padding: "0.9rem 1.5rem"
  button-panneau-hover:
    backgroundColor: "{colors.blanc}"
    textColor: "{colors.bleu-fonce}"
  button-bandeau:
    backgroundColor: "{colors.blanc}"
    textColor: "{colors.bleu-fonce}"
    rounded: "{rounded.none}"
    padding: "0.9rem 1.5rem"
  button-bandeau-hover:
    backgroundColor: "{colors.bleu-fonce}"
    textColor: "{colors.blanc}"
  panneau:
    backgroundColor: "{colors.bleu-fonce}"
    textColor: "{colors.blanc}"
    padding: "{spacing.60} 0 {spacing.70}"
  affiche:
    backgroundColor: "{colors.blanc}"
    textColor: "{colors.bleu-fonce}"
    rounded: "{rounded.none}"
    padding: "clamp(1.5rem, 3vw, 2.25rem)"
  affiche-verte:
    backgroundColor: "{colors.vert-base}"
    textColor: "{colors.bleu-fonce}"
    rounded: "{rounded.none}"
    padding: "clamp(1.5rem, 3vw, 2.25rem)"
  punaise:
    backgroundColor: "{colors.orange}"
    rounded: "{rounded.punaise}"
    size: "1.25rem"
  a-completer:
    backgroundColor: "{colors.a-completer}"
    textColor: "{colors.bleu-fonce}"
  input-search:
    backgroundColor: "{colors.blanc}"
    textColor: "{colors.bleu-fonce}"
    rounded: "{rounded.none}"
    padding: "0.75rem 1rem"
---

# Design System: Les Scouts – Unité

## Overview

**Creative North Star: "Le panneau du local"**

The site is the notice board at the door of the scout local. The federation's bleu foncé, pricked with the white Fédération topping, is the board; white paper notices are pinned on it with a coloured push-pin and a slight tilt, and they say what happens next. Everything else (section banners, the age ruler, the news list) speaks the same plain, sturdy language: square corners, Muli in heavy weights, flat colour fields from the Les Scouts asbl charter, and a handwritten Caveat Brush note left beside or after the content, the way an animateur scribbles on a poster.

Density is moderate and practical: parents read this on a phone between two things, so information sits in rows (section swatch, section, date and place, time) rather than in decorative cards. Full-width bands touch each other with no gap (header, board, light content band, white band, board-blue footer), and the rhythm alternates bleu foncé and light grounds down the page.

The world rejects the hero-headline-over-three-equal-cards arrangement of association sites. Depth exists only where paper is pinned; everything else is flat.

**Key Characteristics:**
- Bleu foncé board with the white Fédération topping tiled at 11% opacity.
- White (and one vert base) paper notices, pinned with a round push-pin, each tilted between -1.1 and +1.3 degrees, straightening and lifting on hover or focus.
- A square colour swatch per section marks every row, ruler segment and banner.
- Muli 900 with tight tracking for every heading; Caveat Brush only for handwritten asides and notes.
- Square corners everywhere; the only round shape is the push-pin.
- Placeholders the staff must replace are visibly highlighted in pale orange, never disguised as real data.

## Colors

The palette is the Les Scouts asbl charter, used as flat fields: bleu foncé owns the page, vert base is the one accent paper, section colours mark identity, and orange is the hand of the user (pins, focus, caret).

### Primary
- **Bleu foncé de la Fédération** (bleu-fonce): the board ground, all body and heading text on light grounds, the default button, table header rule, news list rules, footer ground. The charter's primary; it is the colour of the site.
- **Bleu nuit** (bleu-nuit): text on the Baladins banner, where white on Baladins blue would not be legible.

### Secondary
- **Vert base** (vert-base): the green notice (registration), the primary button on the board, the navigation underline on hover and current page, handwritten asides on the board, text selection.
- **Vert foncé** (vert-fonce): the thin federation bar above the header, button hover on light grounds, subtitles and age ranges, list markers.

### Tertiary
- **Orange** (orange): push-pins on white notices, the 3px focus ring, the text caret. Never a fill for text or surfaces.
- **Rouge** (rouge): the charter's red, reserved for handwritten marks on white paper.
- **Section colours** (baladins, louveteaux, eclaireurs, pionniers): the section swatch in meeting rows, the 0.75rem top border of each ruler segment, and the full ground of each section banner. Pionniers red also carries handwritten notes on white notices and the step numbers.

### Neutral
- **Fond** (fond): the light content band between boards, row dividers inside notices, submenu hover.
- **Trait** (trait): hairlines (header bottom edge, logo divider, ruler column dividers, table rows).
- **Papier à compléter** (a-completer): the highlight behind every placeholder the staff must fill in.
- **Blanc** (blanc): notice paper, header ground, text on the board and on dark section banners.

### Named Rules
**The Charter Rule.** Every colour comes from the Les Scouts asbl charter (`scss/_ls-variables.scss`) or is a tint or shade serving legibility of a charter colour. No new hues.

**The Section Swatch Rule.** A section is always identified by its own colour as a flat square or band: 0.85rem swatch in a row, 0.75rem bar on the ruler, full ground on its banner. Section colours never tint text on light grounds.

**The Legible Banner Rule.** Banner text is white, except on Baladins blue, where it is bleu nuit.

## Typography

**Display Font:** Muli (with "Segoe UI", Helvetica, Arial, sans-serif)
**Body Font:** Muli
**Handwritten Font:** Caveat Brush (with cursive)

**Character:** Muli Black, tightly tracked, gives poster-weight headlines with full French accents (the charter's Housepaint has none, so it is not used). Caveat Brush is the animateur's marker on the poster: short, lower-case, human.

### Hierarchy
- **Display** (900, fluid from 3rem up to the token size, line-height 0.95, -0.035em): the board title (unit name, page titles on the board) and section banner titles.
- **Headline** (900, fluid from 2.5rem, 1.05, -0.02em): h1 elsewhere, the large "Prochaines réunions" notice title, the meeting date on section pages.
- **Title** (900, fluid from 1.75rem, 1.05, -0.02em): h2, smaller notice titles, the lead news item.
- **Title small** (900, fluid from 1.25rem, -0.01em): h3, news list titles. h4 is 1.0625rem, tracking 0.
- **Body** (400, 1.0625rem, 1.6): running text, held to a 46rem content column on pages.
- **Body small** (400, 0.9375rem): captions and fine print.
- **Label** (800, 1rem, 1.2): navigation, buttons, arrow links, the time column of meeting rows, age ranges. Section names in rows are 900.
- **Manuscrit** (Caveat Brush 400, 1.75rem, line-height 1): handwritten notes; the board aside scales from 1.5rem to 2.25rem.

### Named Rules
**The Beside-Not-Above Rule.** Caveat Brush is placed beside a heading (the board aside on the same baseline) or after content (photo caption, footer motto). It is never a label stacked above a heading.

**The Heavy Muli Rule.** Headings are Muli 900 with negative tracking and balanced wrapping; nothing between 400 and 800 is used for headings.

**The Tabular Time Rule.** Dates, times and ages use tabular numerals.

## Layout

Full-width bands stacked edge to edge with no gap between header, content and footer. Content width is 46rem for reading; boards, banners and lists use the 78rem wide size. Root side padding is clamp(1rem, 4vw, 2.5rem). Vertical band padding uses the fluid spacing scale (60 to 80); block gap defaults to 1.25rem.

The home board is an asymmetric two-column grid: a large notice at about 57% on the left, a stack of two notices at about 43% on the right with 2.75rem between them. Columns stack on narrow screens.

Meeting rows are a three-column grid (section, date and place, time right-aligned) that becomes two lines under 40rem: section and time on top, date and place below, indented to align with the section name. The age ruler is a horizontal bar of segments sized by age span (2 or 4 years) with ages sitting on the bar; it reads its own container's width (container query), not the viewport, and turns vertical with the ages on the left when that container is 60rem or narrower, so it holds up inside a 46rem text column too. The news list puts the lead item spanning three rows on the left (1.4fr) and the others in a column (1fr), collapsing to one column under 46rem.

## Elevation & Depth

Flat by default. The only shadow belongs to pinned paper: notices, the section logo card on banners, and dropdown submenus. Depth comes from paper on board, not from layered surfaces.

### Shadow Vocabulary
- **Papier épinglé** (`0 2px 3px rgba(4, 30, 56, 0.18), 0 14px 28px -6px rgba(4, 30, 56, 0.35)`): notices at rest, banner logo card, submenus.
- **Papier décroché** (`0 2px 3px rgba(4, 30, 56, 0.16), 0 26px 40px -10px rgba(4, 30, 56, 0.45)`): notice on hover or focus-within, when it straightens and lifts 4px.
- **Punaise** (`0 3px 4px rgba(4, 30, 56, 0.35)`): under the push-pin.

### Named Rules
**The Only Paper Casts Shadow Rule.** If it is not pinned paper or a menu dropping over content, it has no shadow. Hairlines (1px trait) separate the header; tonal bands separate everything else.

**The Pinning Rule.** On load, notices on the home board drop and settle one after another (0.7s, cubic-bezier(0.16, 1, 0.3, 1), 0.12s stagger). On hover or focus they rotate to 0 and lift. With reduced motion they are shown settled and transitions are off. The editor never animates.

## Shapes

Square corners throughout (radius 0): buttons, notices, inputs, banners, swatches. The one round form is the push-pin, a 1.25rem disc with a radial highlight (orange on white paper, bleu foncé on the green notice). Notices carry a slight rotation (-1.1, +1.3, -0.6, -0.7 degrees by position); the banner logo card sits at -2 degrees. Arrows are drawn as two 2 to 2.5px borders rotated 45 degrees, not glyphs or icon fonts. Rules are thick and flat: 2px row dividers, 6px top rule on the lead news item, 0.75rem section bars.

## Components

### Buttons
Sturdy rectangles, no radius, Muli 800.
- **Shape:** square (0).
- **Primary:** bleu foncé ground, white text, 0.9rem by 1.5rem padding. Hover: vert foncé.
- **On the board:** vert base ground, bleu foncé text; hover white. Outline variant: transparent with a 2px inset white ring, filling white on hover.
- **On a notice (even on the board):** back to primary bleu foncé.
- **On a section banner:** white ground, bleu foncé text; hover bleu foncé with white text.
- **Focus:** 3px orange outline, 3px offset (global for every interactive element).

### Arrow link
Muli 800, no underline, followed by a drawn chevron that slides 4px right on hover. Used for "see all" links at the end of a notice or the right of a section header.

### Cards / Containers: the notice (affiche)
- **Corner Style:** square.
- **Background:** white; one green (vert base) notice per board for the main call to action.
- **Shadow Strategy:** Papier épinglé at rest, Papier décroché on hover and focus-within.
- **Border:** none; a push-pin centred on the top edge.
- **Internal Padding:** clamp(1.5rem, 3vw, 2.25rem).
- **Text:** always bleu foncé, whatever the ground around it.

### The board (panneau)
Full-width bleu foncé band with the white Fédération topping tiled (360px tiles, 11% opacity). Holds the page title with an optional handwritten aside to its right, then notices. Also used as the page header on inner pages, the 404 page and the footer ground.

### Meeting row (ligne de section)
Swatch, section name (900, linked), date and place, time (800, right-aligned, no wrap). Rows are separated by a 2px fond rule. One row per section, in age order.

### Section banner (bandeau)
Full-width ground in the section colour, the white section topping fading in from the right (35% opacity, masked), the section logo on a white tilted card with the paper shadow, display title, age range, intro, and a white button.

### Age ruler (règle des âges)
Four segments sized by age span, each topped by a 0.75rem bar in the section colour with the ages (900, tabular) sitting on the bar; logo, section title (the whole segment is the link target, with the focus ring drawn around the segment), age range in vert foncé, short description. In the horizontal layout the section title scales with the container (never above x-large) so the two-year segments always fit "Pionniers".

### Placeholder highlight (à compléter)
Pale orange highlight behind any text the staff must replace (names, phones, addresses, amounts), wrapping cleanly across lines. Present in shipped patterns by design until the unit fills them in.

### Inputs / Fields
Search input: white ground, 2px bleu foncé border, square, 0.75rem by 1rem padding; placeholder in a muted blue (#4D6F92). Focus uses the global orange ring.

### Navigation
Thin vert foncé federation bar (0.875rem, 700, back-chevron link to Les Scouts asbl) above a white header with a 1px trait bottom edge: federation logo, a hairline divider, unit name (900) and subtitle (700, vert foncé). Menu links are Muli 800, 1rem, no underline; hover and current page draw a 3px vert base underline. Submenus are white paper with the paper shadow and fond hover. Mobile menu opens full screen at 1.375rem.

### Numbered steps (étapes)
Step blocks indented 4rem with a large handwritten number (Caveat Brush 3rem, red) in the margin.

## Do's and Don'ts

### Do:
- **Do** put what happens next on pinned white paper on the bleu foncé board, with a push-pin and a slight tilt.
- **Do** mark every section with its own charter colour as a flat square, bar or ground.
- **Do** keep headings in Muli 900 with negative tracking and every corner square.
- **Do** place Caveat Brush notes beside a heading or after content, short and lower-case.
- **Do** highlight every unknown fact with the pale orange placeholder instead of inventing it.
- **Do** keep the 3px orange focus ring on every interactive element and honour reduced motion.

### Don't:
- **Don't** use Caveat Brush as a label or kicker above a heading.
- **Don't** use Housepaint for headings: it has no French accents.
- **Don't** add shadows to anything that is not pinned paper or a dropdown menu.
- **Don't** round corners; the push-pin is the only circle.
- **Don't** introduce colours outside the Les Scouts asbl charter.
- **Don't** set white text on Baladins blue; use bleu nuit.
- **Don't** build a hero headline over three equal cards.
