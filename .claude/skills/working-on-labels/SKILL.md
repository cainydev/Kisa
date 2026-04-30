---
name: working-on-labels
description: Use whenever you create, edit or debug label templates (Blade views under resources/views/labels/templates/, classes under app/Labels/Templates/), the parameter system, the renderer, or the EditLabel UI. Covers the architectural mental model and the gotchas that bite if you don't know them.
---

# Working on the label system

## Mental model

A label = **template (code) + Label row (DB) + entity (the morph subject)**.

- **Template** is a PHP class implementing `App\Labels\LabelTemplate` (extend `AbstractLabelTemplate`). Lives in `app/Labels/Templates/`. Declares: `key()`, `name()`, `subjects()` (which Eloquent classes it accepts), `dimensions()` in mm, `pages()` (named map: `['front' => 'view.path', ...]`), and `parameters()` returning `Param[]`.
- **Label** is a row in the `labels` table. Polymorphic `belongsTo` a subject via `labelable_type` + `labelable_id` (nullable for abstract bases). Inheritance via `parent_id` (self-ref). Holds scalar overrides in `parameters` JSON column and image overrides in Spatie media collections named `param_<key>`.
- **Entity** (Product, Variant, ...) inversely `morphMany`s its Labels through `labels()`.

The renderer takes `(Template, pageKey, ?Label, ?Entity, RenderOptions)` and produces HTML or a PNG/PDF for one named page.

## Param resolution order — memorise this

For each `Param` declared by the template, the resolver walks (`App\Labels\ParameterResolver::resolveOne`):

1. The **Label parent chain** (current label → parent → grandparent → …):
   - For image params: `$ancestor->getFirstMedia("param_<key>")`.
   - For scalar params: `$ancestor->parameters[$key]` if set and non-empty.
   First non-null hit wins.
2. The Param's **auto closure** if defined: called with `?Model $entity` (the labelable).
3. The Param's **literal default**.
4. Throw if `required()` and still null; otherwise null.

This means: Label rows are **purely overrides**. Defaults live in the template. The same template can render bare against an entity (no Label row) when every required Param has a default.

## Param API

```php
Param::make('key')->image()->required();
Param::make('key')->font();                                // .otf/.ttf/.woff/.woff2 upload
Param::make('key')->string()->default('foo');
Param::make('key')->number()->range(0, 100, 0.5, 'mm');    // numeric slider/range hint
Param::make('key')->color()->default('#d8dc8e');
Param::make('key')->boolean()->default(false);             // Toggle
Param::make('key')->select(['a' => 'A', 'b' => 'B']);      // dropdown
Param::make('key')->string()->label('Bezeichnung');        // human label override
Param::make('key')->shared();                              // see "Inheritance" below
Param::make('key')->auto(fn (?Product $p) => $p?->size);   // dynamic default
```

Types: `image`, `font`, `string`, `number`, `color`, `boolean`, `select`. Sources: literal `default()` and/or `auto(closure)`. The auto closure receives the entity (or null if rendering against no entity). It is the developer's job to handle the null case.

The `select()` map is `value => human label` — the stored value is the array key.

## Templates (where to copy from)

Look at `app/Labels/Templates/HerbTemplate.php` for the full pattern. Key conventions:

- **Always** type-hint `?Product` (or whatever subject) in auto closures and handle null.
- **Sanitize** the entity's name in auto closures — DB name fields can have noise (e.g. "Bio Brennnesselblätter Brennnessel"). Have a `$cleanName` closure at the top of `parameters()` and reuse it.
- **Image params for brand assets** (logo, seals, prep icons) belong on the template, NOT hardcoded SVGs in Blade. Users attach them once on the abstract base label and every concrete child inherits them.

## Blade pages

Live in `resources/views/labels/templates/{template-key}-{pagekey}.blade.php`.

Each page wraps its content in `<x-label-page :width :height :bleed :marks>`. The chassis component handles `@page` size, the manual crop marks (Chromium ignores CSS `marks: crop`, so we draw them as positioned divs), bleed offsets, and font setup.

```blade
<x-label-page :width="$width" :height="$height" :bleed="$bleed" :marks="$marks">
    <style>/* page-scoped CSS in mm */</style>
    <div class="my-page">…</div>
</x-label-page>
```

### Image embedding rule (critical)

**Browsershot blocks `file://` URLs in HTML.** Don't write `src="file:///..."`. Always **base64-embed**:

```blade
@php
    $imgSrc = function ($media) {
        if (!$media || !is_file($media->getPath())) return null;
        $mime = $media->mime_type ?: mime_content_type($media->getPath());
        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($media->getPath()));
    };
    $artworkSrc = $imgSrc($artwork ?? null);
@endphp
@if ($artworkSrc) <img src="{{ $artworkSrc }}"> @endif
```

The resolver returns Spatie `Media` objects for image params; the template view does the base64 dance.

### Sizing

- All dimensions in **mm**. Do not mix px.
- The chassis applies `@page size` and a `.lp-page` block sized in mm; the page content fills 100%.
- Use `font-size`, `padding`, `width`, `height` in mm. Browsers honor mm units for print and Browsershot screenshots them at the configured paper size.
- For preview only: `LabelRenderer::renderPagePng()` rasterises at ~96dpi (`1mm ≈ 3.78px`). The PDF output is true mm. Don't confuse the two.

### Working with config-level brand constants

`config/labels.php` holds:

```php
'brand' => [
    'name', 'address_lines', 'oeko_code', 'oeko_origin',
    'colors' => ['heading', 'subtitle', 'text'],
],
```

Reference via `config('labels.brand')` in Blade. Templates reading these in `parameters()` should use them as defaults so users can still override per-label.

## Form rendering (LabelResource)

Form fields are auto-generated from `Param` schemas in `LabelResource::fieldFor()`. The mapping:

- `image` → `SpatieMediaLibraryFileUpload` with collection `param_<key>`. **Must** have `->disk('public')->visibility('public')` — otherwise files land on the local disk and the URL `/storage/...` won't resolve via the symlink.
- `string` / `number` → `TextInput` with placeholder showing the inherited/auto value.
- `color` → `ColorPicker`.

All fields are `live(onBlur: true)` and call `$livewire->autosave()` so the live preview updates after each blur.

### Autosave re-entrancy guard

`EditLabel::autosave()` has an `$autosaving` re-entrancy guard. Reason: when saving an image param, Spatie's `saveUploadedFiles()` fires `afterStateUpdated` which loops back into `autosave()` → `save()` → image save → ... → infinite recursion. Don't remove the guard.

## EditLabel page — quirks

- It's a Filament `EditRecord` with a custom view at `resources/views/filament/resources/labels/pages/edit-label.blade.php`.
- Uses `Width::Full`. Two main schema sections keyed `topBar` and `parameters` are rendered separately in the Blade via `$this->form->getComponent('topBar')` and `'parameters'`.
- **Do not split into two `getForms()`** — that breaks Livewire entanglement for `param_*_upload` fields. One form, two layout sections, render each in the view.
- Live preview is an `<img src="">` pointing at `route('labels.preview', ['label', 'page'])` with `?v={updated_at-timestamp}` cache-bust. The browser refetches the PNG when `updated_at` changes (i.e. after autosave).
- Preview controller is `App\Http\Controllers\LabelPreviewController` registered in `routes/web.php` under panel auth middleware.

## What blades receive

In addition to the resolved param values, `LabelRenderer` injects:

- `$width`, `$height` (mm, from `template->dimensions()`)
- `$bleed`, `$marks` (from `RenderOptions`)
- `$slug` (a stable per-page slug for caching/file naming)
- **`$entity`** — the model the label is bound to (Product, Variant, …) or null. Lets the blade reach into the recipe (`$entity->herbs`) without going through an auto closure. `IngredientList::build($entity ?? null, $bioMode)` is the canonical use.

If you need access to a related record from the entity at render time, read it from `$entity` directly rather than threading it through a Param.

## Renderer pipeline

`LabelRenderer::renderPagePng()` and `renderPagePdf()` are separate methods:

- **Preview (PNG)**: Browsershot `screenshot()` at `windowSize(pageW_mm × 3.78, pageH_mm × 3.78)` with `deviceScaleFactor(2)` for retina sharpness. No bleed, no crop marks regardless of options — preview is always clean.
- **Print (PDF)**: Browsershot `pdf()` with `paperSize(w + 2*bleed, h + 2*bleed, 'mm')`, `margins(0,0,0,0)`, `showBackground()`, `emulateMedia('print')`, `waitUntilNetworkIdle()`. The chassis emits `@page size` matching.

Browsershot needs `BROWSERSHOT_CHROMIUM_PATH=/usr/bin/chromium` set (Dockerfile + .env). Puppeteer is in node_modules.

### CMYK conversion

`App\Services\Labels\CmykConverter` runs Ghostscript on the rendered PDF when `RenderOptions::$cmyk` is true. ICC profile expected at `resources/print/icc/ISOcoated_v2_300_eci.icc` (optional — falls back to default conversion).

## Adding a new template — checklist

1. Class at `app/Labels/Templates/{Name}Template.php` extending `AbstractLabelTemplate`.
2. Add to `config/labels.php` `'templates'` array.
3. Blade views at `resources/views/labels/templates/{key}-{pagekey}.blade.php`. Each wraps in `<x-label-page>`.
4. If template adds new image params: the `Label` model auto-registers media collections from the registry — no extra wiring needed.
5. **Reuse, don't copy**: for blend-style templates use `App\Labels\IngredientList::build()` and the `BioMode` enum; for headings use `<span class="section-heading">` / `<h3 class="section-heading">`; for prep-icon recoloring use the `$svgInline` pattern. Keep the existing K&W back-page conventions (5mm padding, `font-size: 3.5mm`, the section-margin scheme) unless the IDML genuinely calls for different.
6. Test by creating a Label row attached to an entity, then opening `/labels/{id}/edit` and watching the preview.

## Adding a new Param to an existing template

Just add it to `parameters()`. The form auto-generates a field. Existing Label rows ignore unknown keys and inherit/use defaults for the new one. No migration needed.

## Inheritance — using parent labels well

Use a parent label when **multiple labels share assets or values**:

- Brand assets (logo, seals, prep icons) → upload once on a base label with no `labelable`, set `parent_id` on every concrete label to point at the base. To swap a logo: upload once on the base, every child label re-renders with the new logo.
- Shared text overrides (specific subtitle for a product line, etc.) — same idea.

The cycle guard in `Label::booted()` prevents loops. Don't try to bypass it.

### `->shared()` — hides the field on descendants where an ancestor already set it

Marking a Param `->shared()` makes it edit-once-and-inherit: as soon as **any ancestor** in the chain has stored a value (scalar param OR media collection entry), the field is hidden on descendants. Implemented in `Label::hasAncestorValue()` (used by `LabelResource::parameterFields`).

The check is **chain-aware** — it works in 3+ level hierarchies. Root → Mid → Leaf, shared param `subtitleColor`:
- Root: shown (no ancestor) — set or skip.
- Mid: if Root set it → hidden; if Root left empty → shown (Mid can override).
- Leaf: if Root or Mid set it → hidden; otherwise shown.

For media-typed shared params the check looks at the ancestor's `param_<key>` Spatie collection; for scalar types it checks the JSON `parameters` map. A field reappears on a descendant if you delete the ancestor's value/file.

### Use `IngredientList` for any recipe-driven copy

The `App\Labels\IngredientList` value object centralises the ingredient-list rendering shared by HerbBlend and RuthsBlend templates. It exposes `text`, `anyBio`, `allBio`, `nonBioPercent`. Blade views call `IngredientList::build($entity, $bioMode)`.

Don't copy the closure into a third template — extend `IngredientList` (or add another method on it) instead.

## Bio claims — the `BioMode` system

Blend templates (HerbBlendTemplate, RuthsBlendTemplate) use a tri-state `bioMode` Param of type `select`:

- `none` — no bio claim. Plain ingredient names, no asterisk, no percentage, no seals.
- `bio` — every ingredient is certified bio. Plain names, **no asterisk** (a single closing sentence applies to the whole list, e.g. "… aus kontrolliert biologischem Anbau DE-ÖKO-039.").
- `from_stock` — derive bio status per herb at render time. Walks the herb's `bags` filtered to those with `getCurrent() >= 100g`; herb is bio iff every relevant bag is bio (conservative — one non-bio bag flips it). Bio entries get `name*`; non-bio get `{percentage}% name`. Footnote line `*aus ökologischer EU-/nicht-EU-Landwirtschaft DE-ÖKO-039` shows.

Logic lives in `App\Labels\BioMode` (enum) and `App\Labels\IngredientList` (the actual list rendering with the `allBio`/`anyBio`/`nonBioPercent` flags).

### EU 95/5 rule for seal eligibility

The BIO hex seal and the EU leaf (with the DE-ÖKO-039 caption) **may not be shown** when the product has more than 5% non-bio ingredients. Compute `bioSealsAllowed = anyBio && nonBioPercent <= 5.0` in the blade, and gate the seals on that — not just `anyBio`. The footnote can still appear on partial-bio products.

### Why no `*` on all-bio lists

Visual + legal: when 100% of ingredients are bio, asterisks add no information (the closing sentence covers the whole list), so we drop them and switch from a footnote-with-marker to an inline closing sentence. Mixed-bio lists keep the asterisk pattern because there it carries actual info (which subset is bio).

## Section headings — single shared class

All three section headings on the back pages (`Inhaltsstoffe:`, `Zubereitungshinweise:`, `Sicherheitshinweis:`) use the same class `.section-heading`. The element type differs by position (h3 for block, span for inline) but the typography is identical:

```css
.{template}-back .section-heading {
    font-family: 'herb-title', 'herb-body', -apple-system, sans-serif;
    font-size: 3.5mm;
    line-height: 1;
}
.{template}-back h3.section-heading { margin: 0; }
```

Don't reintroduce per-element rules (`.label`, bare `h3`) — they were unified in the cleanup.

## SVG inlining for accent-color recoloring

Prep-icon SVGs are inlined (not `<img>`) so we can string-replace the dominant fill with the user's `accentColor`. The pattern in `*-blend-back.blade.php`:

```php
$svgInline = function ($media) use ($accentColor, $accentBaseColor): ?string {
    if (!$media || !is_file($media->getPath())) return null;
    $svg = file_get_contents($media->getPath());
    $svg = preg_replace('/<\?xml[^?]*\?>/', '', $svg);
    $svg = preg_replace('/<!DOCTYPE[^>]*>/', '', $svg);
    return preg_replace('/' . preg_quote($accentBaseColor, '/') . '/i', $accentColor, $svg);
};
```

`$accentBaseColor` is the source-file dominant color (`#C5C95C` for the K&W prep icons). Multi-tone elements (the green leaves) keep their other fills untouched. The CSS sizes both `<img>` and `<svg>` selectors:

```css
.{template}-back .prep-row .icon img,
.{template}-back .prep-row .icon svg {
    width: auto;
    height: 25mm;
    object-fit: contain;
    display: block;
}
```

This requires the user-uploaded SVG to actually contain the source color you're swapping. If you change source assets, update `$accentBaseColor`.

## Filament uploads: keep original filenames

Add `->preserveFilenames()` to every `SpatieMediaLibraryFileUpload` for label media. Filament's default temp-file path mangles the name (random prefix + base64-encoded original between `-meta…-` markers). With `preserveFilenames()` the stored file keeps the upload name, which makes "what's the font I uploaded" answerable from the filesystem.

Already wired in `LabelResource::fieldFor()` for `Image` and `Font` types. New upload slots should follow suit.

## Tree view in the Labels table

The Labels Filament resource sorts by **preorder traversal** of the parent_id chain — roots first, each followed by its descendants depth-first. The Name column is prefixed with box-drawing branches (`├─`, `└─`, `│  `) so the hierarchy is visually obvious.

Implementation:
- `LabelResource::treeOrderAndDepths()` does a single 2-column query and computes both the ordered id list and the per-row prefix string.
- The Filament query is reordered via `orderByRaw("FIELD(id, ?, ?, ...)", $orderedIds)`.
- The depth/prefix map is stashed in the container under `labels.tree-meta` and read back by the column formatter.

If you reach thousands of labels, swap the in-PHP ordering for a precomputed `tree_path` column. For now this is fine.

## IDML extraction — getting ground-truth typography from designer files

The K&W product line was originally laid out in InDesign. The `.idml` files in `~/Shared/Kräuter & Wege/! Marketing/Etiketten/.../*.idml` are the source of truth for **font weights, sizes, leading, tracking, frame positions**.

IDML is a zip of XMLs:
- `Stories/Story_uXXXX.xml` — text runs with `FontStyle`, `PointSize`, `Leading`, `Tracking`, `HorizontalScale`, `Capitalization`. Match the `<Content>` to figure out which story is which.
- `Spreads/Spread_uXXXX.xml` — frame placement (`<TextFrame>` with `ItemTransform` translation + `<PathPointArray>` corners).
- `Resources/Preferences.xml` — page dimensions in pt.

Conversions:
- `1pt = 0.352778 mm`
- IDML `Tracking` is 1/1000 em → CSS `letter-spacing: {value/1000}em`
- IDML `Leading / PointSize` = CSS `line-height`
- IDML `FontStyle="55 Roman"` / `"85 Heavy"` / `"35 Light"` / `"65 Medium"` map to Avenir LT Std weights — the user uploads the actual `.otf` files into the corresponding font slots; the slot label hints which weight is expected.

To extract one:

```bash
mkdir /tmp/idml_extract && unzip -q "/path/to/file.idml" -d /tmp/idml_extract
# Grep for the visible text to find the right story:
grep -l 'BIO-Kräutertee' /tmp/idml_extract/Stories/*.xml
```

For the front title/subtitle on the K&W blend templates: title is `85 Heavy` 25.79pt (≈9.10mm), subtitle is `55 Roman` 13.41pt (≈4.73mm), tracking 70/57 = 0.07em/0.057em respectively. Encoded in `*-blend-front.blade.php`.

## Asset conversion — common cases

`.psd` (raster art with hidden background layer) → transparent PNG:

```bash
# Flatten respects layer visibility; first hide background in Photoshop/GIMP, then:
magick file.psd[0] -colorspace sRGB out.png

# If background isn't a layer but baked-in, knock out the bg color:
magick file.png -fuzz 8% -transparent 'srgb(83,85,82)' out.png
```

For PSDs with live text/effect layers, ImageMagick can only render the cached composite (layer `[0]`). Live text won't appear from individual layer extraction — use the composite or open in GIMP/Photoshop and re-export.

`.ai` (Illustrator vector) → PNG at 300dpi:

```bash
magick -density 300 file.ai -colorspace sRGB -background none -alpha on out.png
# Or with Ghostscript:
gs -dNOPAUSE -dBATCH -sDEVICE=pngalpha -r600 -dEPSCrop -o out.png file.ai
magick out.png -trim +repage out.png
```

## Common mistakes

- **Hardcoding brand assets as SVG in the chassis or Blade**. Wrong. Make them image params on the template; users attach them on the base label.
- **Using `file://` paths for images**. Browsershot blocks them. Always base64.
- **Calling `autosave()` from outside `afterStateUpdated`**. Will work but dilutes the contract. The image-upload `afterStateUpdated` already bounces back through it via Spatie's lifecycle — that's why the re-entrancy guard exists.
- **Forgetting `->disk('public')` on `SpatieMediaLibraryFileUpload`**. File goes to `local`, URL points at `/storage/<id>/...`, browser 404s, FilePond can't render the thumbnail, and the image editor button never appears.
- **Mixing px and mm in Blade CSS**. Use mm consistently. The chassis assumes mm.
- **Not type-hinting `?Type` in auto closures**. The closure can be called with null when the template is being rendered "bare" (no entity). Always handle null.
- **Trying to make MorphToSelect inline like a single field**. Filament v5 MorphToSelect is structurally a fieldset of two selects — it cannot be made flat. Use `->columns(2)` to put the two inner selects side-by-side, accept the fieldset wrapper.
- **Showing the BIO seal / EU leaf with `>5%` non-bio share**. Illegal under EU 95/5. Always gate seals on `bioSealsAllowed = anyBio && nonBioPercent <= 5.0`, not just `anyBio`.
- **Asterisks on every herb when the whole list is bio**. Visual noise. Drop the `*` markers and use a single inline closing sentence; reserve the asterisk-and-footnote pattern for mixed-bio lists where it carries information.
- **Skipping `->preserveFilenames()`** on label upload fields. Files end up with a mangled `xxxx-meta…-` name and you can't tell what was uploaded.
- **Extracting PSD layers individually with ImageMagick to make a transparent logo**. ImageMagick can't render live PSD text/effect layers — only the cached composite at `[0]`. Either knock out the background color from the composite, or re-export from GIMP/Photoshop with the bg layer hidden.
- **Re-implementing the ingredient-list logic in a third blend template**. Use `App\Labels\IngredientList::build()`. If the rendering shape genuinely differs, add a method to `IngredientList` rather than copying the closure.

## Debugging the preview

- HTML version: hit `/labels/{id}/preview/{page}?format=html` to see raw rendered HTML.
- Tinker: `app(LabelRenderer::class)->renderPageHtml($tpl, 'front', $label, $entity, new RenderOptions(0, false, false))` to inspect what's going to the PNG/PDF.
- Logs: Browsershot exceptions surface in Laravel log. Common ones:
  - `puppeteer module not found` → run `npm install puppeteer --legacy-peer-deps`.
  - `file:// not allowed` → image src is a file path; switch to base64.
  - timeouts → check Chromium binary is installed and the path env var is correct.

## Don't do

- Don't add per-label `width_mm`/`height_mm` columns to the `labels` table. Dimensions belong to the template; if a Brennnessel-100g needs different dims, make a new template, not a column.
- Don't make `template_key` editable on existing Label rows. The form disables it on edit; it's frozen because changing it would orphan all the params.
- Don't add new tables for label content. JSON `parameters` + Spatie media collections cover everything we've needed.
- Don't try to use FilePond's image editor with cross-origin images — the page must be served from the same origin as `APP_URL` (the `/storage` URL). Visit at the host that matches `APP_URL` in `.env`.