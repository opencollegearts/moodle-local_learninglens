# Changelog

## 1.0.1 (2026112001)

- Fix: the launcher button was not visible in Boost and Boost-based themes. Boost renders the footer hook output inside its hidden footer popover, so the widget is now moved to `<body>` when it is output.
- Code style fixes to meet the Moodle coding standard (no functional change).

## 1.0.0 (2026102601)

- First release of OCA Learning Lens for Moodle 4.5+.
- On-page accessibility adjustments: contrast, dyslexia-friendly font, spacing, links, text size, pause animations, hide images, reading guide, reading mask, read aloud, and speech speed.
- Launcher uses the Font Awesome universal-access icon, centred in the floating button.
- Plugin listing uses the Learning Lens icon; the README shows it alongside the OCA logo.
- Signed-in preferences stored as a sanitised Moodle user preference; guests use browser storage only.
- No third-party scripts or services.
