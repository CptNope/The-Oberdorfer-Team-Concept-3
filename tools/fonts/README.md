# Font subsets

These are the font subsets embedded in `source/Wakefield_At_Home_8_Page_Print_Ready.pdf`. They were extracted and given a Unicode character map so tools can use them:

- **CormorantGaramond-Light.ttf** and **CormorantGaramond-LightItalic.ttf**: Cormorant Garamond, © Christian Thalmann (Catharsis Fonts)
- **Manrope-ExtraLight.ttf**: Manrope, © Mikhail Sharanda

Both families are licensed under the SIL Open Font License 1.1. Each file contains printable ASCII only, in a single weight.

They are used by:

- `tools/build_logos.py`, to outline the wordmark glyphs into SVG paths
- `tools/screenshot.py`, as a stand-in when Google Fonts cannot load

The live pages load the full font families from Google Fonts. Production should self-host the full font families, not these subsets.
