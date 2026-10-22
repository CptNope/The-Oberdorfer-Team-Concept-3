# Claude artifacts

This folder holds the source of the two Claude artifacts made for this project, so they are versioned with the code they come from. Nothing here is published to GitHub Pages.

| Folder | Artifact | What it is |
|---|---|---|
| `design-system/` | [The-Oberdorfer-Team-Concept-3](https://claude.ai/artifact/YE21oQ5cfN4i2RqN51Wwj4) (Claude Design System) | Tokens, the brand book, 47 component previews and 62 assets, built from `assets/css/` and `design-system/index.html` |
| `fse-templates/` | [Oberdorfer Team FSE Templates](https://claude.ai/artifact/Lzv2vEyR3YjDWmhdJZxZs6) (Claude Design canvas) | Annotated boards for the WordPress block theme: a template map, the header and footer parts, 14 page templates at 1440px and 7 phone boards at 390px |

Both artifacts are private until shared from their Share menu.

## design-system/

- `project/` mirrors the artifact's own files exactly: `README.md` (the brand book), `tokens.json`, `design-system.json` (the index), `components/<Name>/README.md` and `preview.html`, `components/bundle.css` and one `README.md` per asset group.
- The asset binaries (logos, icons, photos, publication pages, the REWAP logo) live in the artifact's asset store. `blobs.json` maps each one to its upload id. Their sources are already in `assets/`; local copies under `project/assets/` are ignored by git.
- `tokens.json` uses the design-system list format, not the W3C format in `assets/tokens/tokens.json`. Both come from `assets/css/tokens.css`.

To rebuild after the CSS, the design-system page or the illustrations change:

```sh
python3 tools/claude/build_ds.py      # tokens.json, bundle.css, component READMEs, render-check pages
python3 tools/claude/check_ds.py      # renders each preview at 960px and records its height
python3 tools/claude/finalize_ds.py   # writes previews, asset READMEs, the cover and the index
```

Then ask Claude to publish `claude/design-system/project/` to the artifact. Changed images must be uploaded again and their ids updated in `blobs.json`.

## fse-templates/

- `project/canvas.json` is the canvas index: board positions, titles, notes and the installed design system.
- Each `*.dc.html` file is one board. Page boards import `Header.dc.html` and `Footer.dc.html` as template parts, and each `Mobile*.dc.html` board imports its desktop template at 390px, so the phone views follow any change to the desktop boards.
- `fse.css` styles the blue block annotations; `oberdorfer-tokens.css` is the compiled token sheet. The canvas also holds a copy of the design system under `ds/oberdorferteam/` (its `tokens.json` and `components/bundle.css`), copied from the design-system artifact.
- Images on the boards are uploads in the canvas's asset store, referenced as `/_blob/<id>`.
- `tools/claude/fse_markup.py <page>` prints a prototype page's `<main>` with links, images and icons rewritten for a board, which is how the boards were written.
