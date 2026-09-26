Photos used by the theme. Originals live in `assets/pictures/` at the repository root; these are resized WebP copies made with `scripts/optimize-photo.sh`, for example:

```sh
scripts/optimize-photo.sh assets/pictures/NX3_9750.JPG.jpg dome 2400
```

| File | Original | Where |
|---|---|---|
| `dome.webp` | `NX3_9750` | Front-page hero. The text sits on the right, so the lit dome on the left stays in view. |
| `portico.webp` | `NX3_9755` | Front page, "Καλώς ήρθατε" section. |
| `church-night.webp` | `NX2_1411` | Header photo of the "Η Ένωση" page (copied into the Media Library by `setup.sh`). |
| `school.webp` | `NX3_9764` | Header photo of the "Η Ριζάρειος Σχολή" page (the same way). |

If `dome` or `portico` is missing, the front page falls back to a plain background.
