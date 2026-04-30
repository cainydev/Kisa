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
Param::make('key')->string()->default('foo');
Param::make('key')->number()->auto(fn (?Product $p) => $p?->size);
Param::make('key')->color()->default('#d8dc8e');
Param::make('key')->string()->label('Bezeichnung'); // human label override
```

Types: `image`, `string`, `number`, `color`. Sources: literal `default()` and/or `auto(closure)`. The auto closure receives the entity (or null if rendering against no entity). It is the developer's job to handle the null case.

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
5. Test by creating a Label row attached to an entity, then opening `/labels/{id}/edit` and watching the preview.

## Adding a new Param to an existing template

Just add it to `parameters()`. The form auto-generates a field. Existing Label rows ignore unknown keys and inherit/use defaults for the new one. No migration needed.

## Inheritance — using parent labels well

Use a parent label when **multiple labels share assets or values**:

- Brand assets (logo, seals, prep icons) → upload once on a base label with no `labelable`, set `parent_id` on every concrete label to point at the base. To swap a logo: upload once on the base, every child label re-renders with the new logo.
- Shared text overrides (specific subtitle for a product line, etc.) — same idea.

The cycle guard in `Label::booted()` prevents loops. Don't try to bypass it.

## Common mistakes

- **Hardcoding brand assets as SVG in the chassis or Blade**. Wrong. Make them image params on the template; users attach them on the base label.
- **Using `file://` paths for images**. Browsershot blocks them. Always base64.
- **Calling `autosave()` from outside `afterStateUpdated`**. Will work but dilutes the contract. The image-upload `afterStateUpdated` already bounces back through it via Spatie's lifecycle — that's why the re-entrancy guard exists.
- **Forgetting `->disk('public')` on `SpatieMediaLibraryFileUpload`**. File goes to `local`, URL points at `/storage/<id>/...`, browser 404s, FilePond can't render the thumbnail, and the image editor button never appears.
- **Mixing px and mm in Blade CSS**. Use mm consistently. The chassis assumes mm.
- **Not type-hinting `?Type` in auto closures**. The closure can be called with null when the template is being rendered "bare" (no entity). Always handle null.
- **Trying to make MorphToSelect inline like a single field**. Filament v5 MorphToSelect is structurally a fieldset of two selects — it cannot be made flat. Use `->columns(2)` to put the two inner selects side-by-side, accept the fieldset wrapper.

## Debugging the preview

- HTML version: hit `/labels/{id}/preview/{page}?format=html` to see raw rendered HTML.
- Tinker: `app(LabelRenderer::class)->renderPageHtml($tpl, 'front', $label, $entity, new RenderOptions(0, false, false))` to inspect what's going to the PNG/PDF.
- Logs: Browsershot exceptions surface in Laravel log. Common ones:
  - `puppeteer module not found` → run `npm install puppeteer --legacy-peer-deps`.
  - `file:// not allowed` → image src is a file path; switch to base64.
  - timeouts → check Chromium binary is installed and the path env var is correct.

## Asset conversion (for adding brand graphics)

`.ai`/`.eps`/`.pdf` → PNG via Ghostscript (already installed):

```bash
gs -dNOPAUSE -dBATCH -sDEVICE=pngalpha -r600 -dEPSCrop -o out.png in.ai
magick out.png -trim +repage out.png  # trim canvas whitespace
```

Then upload via the Filament UI on the abstract base label.

## Don't do

- Don't add per-label `width_mm`/`height_mm` columns to the `labels` table. Dimensions belong to the template; if a Brennnessel-100g needs different dims, make a new template, not a column.
- Don't make `template_key` editable on existing Label rows. The form disables it on edit; it's frozen because changing it would orphan all the params.
- Don't add new tables for label content. JSON `parameters` + Spatie media collections cover everything we've needed.
- Don't try to use FilePond's image editor with cross-origin images — the page must be served from the same origin as `APP_URL` (the `/storage` URL). Visit at the host that matches `APP_URL` in `.env`.
