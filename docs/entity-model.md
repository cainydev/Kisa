# KISA Entity Model

Target model for KISA v3. Every term below has exactly one meaning. Where a
term is currently ambiguous in the codebase, the ambiguity is named explicitly.

Status: **proposal**. Nothing here is built yet. Figures are from the
production dump as of 2026-07-24.

Two things to know before reading:

- **Only the ledger table is near-term.** `Item`, `Lot`, and `Batch` are all
  deferred to phase 4 (§10); phases 1–3 ship a herb-only ledger whose
  `item_id` points at `herbs.id`. The item-based *name* is committed now, the
  machinery is not (§8.2).
- **Model the distinction, defer the machinery.** This rule decides §3.4
  (`Batch` + `Fill` as a named special case) and §3.7 (`stock_source` as a
  column, not a driver layer) the same way.

---

## 1. The core principle

> **Quantities are events. State is a fold over events.**

Today every quantity in KISA is derived state: `bags.size` minus a computed
sum, `bags.trashed` as a mutable column, `ingredients.amount` recomputed from
today's recipe. Derived state is why the same item reports two different stock
figures depending on which code path you ask, and why editing a position's
`count` silently rewrites 2020's bottling records.

The target inverts this. One append-only ledger. Stock is `SUM(quantity)` —
never stored, never able to drift — for every item whose balance the ledger
keeps. Items whose balance lives elsewhere name that source instead (§3.7).

**Immutability is the load-bearing rule.** Movement rows are never updated or
deleted. A correction is a new compensating movement with a reason. This single
constraint eliminates the `count`-edit-rewrites-history bug, makes soft-deletes
on bags unnecessary, and produces the audit trail the application exists to
provide.

---

## 2. Vocabulary

| Term | Definition | Identifier assigned by | Today |
|---|---|---|---|
| **Item** | A stockable kind of thing, with a base unit and a stock source | us | `Herb`, `Variant` |
| **Supplier lot** | Batch identity of *received* material | **the supplier** | `bags.charge` |
| **Bag** | One physical sack belonging to a supplier lot | us | `Bag` |
| **Production order** | One filling run — typically one day, one operator | us | `Bottle` |
| **Batch** | One *mix* of one recipe, in one quantity | **us** | *missing* |
| **Fill** | Batch → n units of one variant | us | `BottlePosition` |
| **Movement** | One signed quantity change against a bag | system | *missing* |
| **Sales order** | A customer purchase | Billbee | `Order` |

`Item` is the stock-facing identity of a thing. `Herb` and `Variant` keep
everything else about themselves — botanical name, `steamed`, SKU, price. See
§3.8 for what `Item` does and does not swallow.

### The rule that keeps this clean

> **A batch may *carry* a supplier lot number. It must never *be* one.**

A printed number is a value. Identity is a row. A single-herb product may
legitimately print its supplier's lot number on the jar — that is honest,
useful, one-hop traceability, and it is what we do today.

What is not acceptable is what happens now: charge `55224` identifies 44
positions across 36 bottles — 36 physically distinct mixes, made on 36 different
days, sharing one label. Grouping unrelated physical batches under one identity
because they were assigned the same number destroys the ability to answer "which
mix was this?".

So: batches keep the passthrough, and gain their own identity. See §6.

---

## 3. Entities

### 3.1 Supplier lot

The batch identity the supplier assigned to material they shipped us. We do not
control its format, its uniqueness, or its meaning. Two suppliers may
legitimately use the same string.

It is **evidence**: it links our goods to the supplier's organic certificate
and to their recall notices.

**Not 1:1 with a bag.** 43 charges in the current data span multiple bags —
charge `42799` covers 5 bags across 5 separate deliveries; `55224` covers 4.
One supplier lot arrives as several sacks, sometimes across several deliveries.

This matters because **recall operates on the lot, not the sack**. If a supplier
recalls charge `42799`, all 5 bags are affected. Today `TraceChargeTool` and
`find-bag-by-charge` reconstruct that grouping by string-matching `charge`.

```
Lot (item, charge, supplier)        ← identity is the triple, not the string
  └── Bag  (one physical sack)
        └── Movement (the grams)
```

> **Resolved (§9.1), deferred to phase 4:** `Lot` becomes a real entity, keyed on
> `(item_id, charge, supplier_id)`. 350 of 399 bags are 1:1 with their charge, so
> the immediate gain is the 43 multi-bag lots — but the reason is that `charge`
> alone was never an identity in the first place.

### 3.2 Bag

One physical sack. Carries `size` (declared by supplier), `bestbefore`,
`steamed`, and belongs to a delivery.

Under the target model a bag has **no quantity column**. Its balance is
`SUM(movements.quantity) WHERE bag_id = ?`.

- `bags.size` → becomes a `receipt` movement
- `bags.trashed` → becomes `loss` movements, each dated and with a reason
- `bags.deleted_at` → unnecessary; a bag is empty when its balance reaches 0

A bag is **never deleted**. It reaches zero and stays in the record forever.
Deleting destroys traceability, which is the one thing an EU-organic audit
cannot tolerate. The same holds for lots once they exist (§9.1).

### 3.3 Production order (`Bottle`)

One filling run: a date, an operator, a note. It **groups the work**; it does
not identify the goods.

It is *not* the batch. Evidence: bottle 621 has 25 positions across 25 distinct
variants and 13 distinct charges. 477 of 980 bottles produce more than one
variant. Conversely charge `55224` spans 36 different bottles. Neither contains
the other.

### 3.4 Batch — the missing entity

**One mix of one recipe, in one quantity.**

This is the entity the current schema has no name for, and it is the one the
operators actually work with. What they do:

> The UI shows `1× Hagebuttenschale 100g` and `2× Hagebuttenschale 200g`, sums
> them, and says: **take 500 g Hagebuttenschalen. From which bag?**

They mix 500 g in one bowl, then fill it into the different jar sizes. One mix,
many fills.

This is visible in the data: **270 mix-groups** where the same run + same
product + same charge spans multiple variants. Bottle 895 filled product 122
into 80 g / 160 g / 320 g / 1000 g jars from a single mix.

```
Bottle (production run)
  └── Batch (one mix of one recipe)          ← THE MISSING ENTITY
        │     required = Σ(variant.size × count) over its fills
        │     number   = KISA-assigned; this is what goes on the jar
        ├── Allocation → Bag   (consumption movements, may span several lots)
        └── Fill → Variant × count
```

Three consequences:

1. **Consumption belongs to the batch, not the fill.** The herbs went into one
   bowl. Attaching consumption to individual fills forces KISA to apportion a
   single physical draw across several line items — fiction that generates
   derived-number drift.

2. **Batch quantity is derived, not planned.** The operator never types "make
   500 g." `required = Σ(variant.size × count)` across the batch's fills. It is
   an input to their physical work and an output of the recipe math.

3. **The batch number is assigned, never derived.** `CHARGE_NOT_CALCULATABLE`
   (currently on 43 positions across 26 bottles) becomes unreachable.

`Recipes::groups()` already reconstructs batches at display time by grouping on
`product_id` + attached bags. The concept has always been there — it just lives
in a computed property instead of the schema.

#### `Batch` is a special case, chosen deliberately

The general form of what a batch does is a **process**: it consumes items and
produces items. Under that model the mix is itself an item — an intermediate —
and `Batch` and `Fill` are both just processes: one consuming herbs and
producing 500 g of mix, several consuming that mix and producing jars.

That model is strictly more expressive. It gets blend-of-a-blend, rework,
repacking a bought-in blend, and a half-used mix carried to the next day for
free. This one gets none of them.

We take `Batch` + `Fill` anyway, because KISA has exactly one process today —
mix and fill — and naming it after what it is beats a generic `Process` row with
a type column that only ever holds one value.

**This is a waypoint, not the terminal shape.** The decision point is **the
moment before phase 4 starts**, because phase 4 is what backfills the 270
historical mix-groups:

- Decide *before* phase 4 that a second process is real (drying, cutting,
  blending a blend, repacking) → build process/item directly, and backfill the
  270 groups into that shape once.
- Discover it *after* phase 4 → the 270 groups already exist as `Batch` rows and
  have to be migrated a second time, along with everything that reads them.

> **Note:** grouping on *attached bags* is backwards. A batch is defined by what
> ops decided to mix; the bag allocation is a *consequence* of that decision,
> not part of its identity. That is why the current grouping can fragment when
> two positions of one product get different bags. `Batch` needs an explicit
> row, created when ops group.

### 3.5 Fill (`BottlePosition`)

Batch → n units of one variant. Carries `count` and the Billbee `uploaded`
flag (2,064 of 2,403 currently uploaded).

Under the target model it carries **no charge and no ingredients**. Both move up
to the batch.

A fill produces an `External` item, so it writes **no movement** in KISA's
ledger — it pushes one stock delta to Billbee instead (§3.7). The existing
`uploaded` flag is the idempotency marker such a push needs, but nothing more:
there is no queue, no retry, and a failed push simply leaves the flag false
(§9.7).

### 3.6 Movement — the ledger

```
stock_movements
  id
  bag_id            -- the physical sack this quantity belongs to (never null)
  item_id           -- denormalised for per-item aggregates (§8.2)
  quantity          -- signed, in the item's base unit: + in, − out
  type              -- receipt | consumption | loss | adjustment
  reason            -- nullable enum; required for loss and adjustment
  reference_type    -- polymorphic: Delivery, Batch, ...
  reference_id
  user_id           -- who
  occurred_at       -- when it physically happened
  note
  created_at        -- when it was recorded
```

**The ledger carries no unit column.** `bag_id` implies an item and the item
declares its base unit, so there is nothing to disambiguate (§8.2).

**`bag_id` non-null is the one thing here that is still herb-shaped.** A bag is
a sack; "bag" does not generalise to a box of 1,000 jars (§3.8). It is a
deliberate narrowing while herbs are the only ledgered item. When packaging
becomes real, resolve it by **renaming the container concept**, never by making
`bag_id` nullable — a null would mean "this quantity is nowhere" and would
silently disable the per-bag balance the reject-if-negative guard locks on
(§9.5). Note that `item_id` is *denormalisable from `bag_id`* only while that
holds; the first ledgered item with no bag makes it load-bearing (§8.2).

**`quantity` is a signed decimal, never a float** — §7 is a list of drift bugs
and float grams would add unexplainable `0.30000000000000004` phantoms to it.
The scale is still open (§9.6).

**`occurred_at` vs `created_at` are never conflated.** An operator recording
Monday's bottling on Wednesday is normal, and an auditor asks about the physical
date.

Types:

| Type | Sign | Reference | Meaning |
|---|---|---|---|
| `receipt` | + | `Delivery` | Material arrived |
| `consumption` | − | `Batch` | Drawn into a mix |
| `loss` | − | nullable | Spillage, moisture, sampling, discard |
| `adjustment` | ± | nullable | Correction; reason required |

Reasons (initial set): `fill_variance`, `moisture`, `spillage`, `sample`,
`discard_rounding`, `derived_amount_drift`, `unattributed_draw`,
`inventory_count`.

### 3.7 Stock source: items declare where their stock lives

**Not every item's stock is ours to keep.** Finished goods already have a
ledger, and it is Billbee's.

So stock source is a property of the item:

```php
enum StockSource { Ledger; External; }
```

The ledger's guarantee is unchanged by this: `SUM(quantity)`, never stored,
cannot drift. "One definition" becomes "one definition per source", which is
what it always meant once more than one source exists.

**Why Billbee stays external.** It owns sales, returns, corrections, and manual
adjustments. Rebuilding that locally means maintaining a permanently-behind copy
— the same divergence bug, across a network boundary. Evidence that its state is
legitimately not ours to reconstruct: 64 variants currently hold negative stock
(down to −10), which is valid Billbee state and would look like corruption in a
ledger that enforces reject-if-negative.

| | Herbs | Variants |
|---|---|---|
| Source | `Ledger` | `External` (Billbee) |
| KISA holds | Full history | Cached snapshot |
| Authoritative store written by | Operators, batches (in KISA) | Billbee: sales, returns, corrections — **and** KISA's `Einlagerung` push |
| KISA's local copy written by | — (there is no copy) | The sync, **and** the `Einlagerung` push, from Billbee's response |
| Reject-if-negative | Enforced (§9.5) | Not available |

Note that the push is not write-only: `UploadBottlePositionStock` sends the
delta and then writes Billbee's returned `currentStock` straight into
`variants.stock` in the same transaction. The snapshot is therefore only ever as
fresh as the last push or sync — which is what makes it a cache, and what §9.7
has to design around.

Whatever the read returns must carry the unit and an `asOf` timestamp — null
when computed from the ledger now, set when fetched from a remote system. A bare
int would read as truth and not be, which is precisely today's `variants.stock`
bug.

The interface is uniform; the guarantees behind it are not. `Ledger` is
transactional and enforces reject-if-negative (§9.5); `External` is a network
read that may fail, may be stale, and may legitimately return a negative. Reads
are easy — **writes are where this gets hard** (§9.7).

Per the rule at the top of this document: `stock_source` is a column, not a
strategy. Do not extract a driver interface while `Ledger` and `Billbee` are the
only cases; let the ledger read path stay direct, and extract when a third
source turns up — a second sales channel, a co-packer, a warehouse.

**Recommended regardless:** rename `variants.stock` to `billbee_stock`, or add
`stock_synced_at`, so the column reads as the cache it is.

### 3.8 What `Item` covers — and what it does not

> **Item = a stockable kind of thing. It has a base unit and a stock source.**

```
items
  id
  type          -- herb | packaging | variant
  base_unit     -- Gram | Piece
  stock_source  -- Ledger | External
```

| Entity | An item? | Why |
|---|---|---|
| **Herb** | Yes | `Gram`, `Ledger` |
| **Variant** | Yes | `Piece`, `External` — Billbee owns the balance (§3.7) |
| **Packaging** | Yes, when it exists | `Piece`, `Ledger` — jars, Doypacks, filter bags |
| **Product** | **No** | A recipe, not a thing you can hold. No quantity, no lot. |

`Item` is the stock-facing identity, **not a replacement**. `Herb` keeps
botanical name, `steamed`, and whatever drives the recipe UI. `Variant` keeps
SKU, size, and price. Neither is swallowed.

**Both units exist from day one.** As soon as `Variant` is an item, the model
holds grams and pieces simultaneously — units are not a later concern that
arrives with packaging.

**Packaging is not scheduled.** It is the clearest second member of this
category and the whole reason the ledger points at items rather than herbs, but
no phase builds it. Note also that the `Lot`/`Bag` vocabulary does not survive
contact with it unexamined: "bag" does not generalise to a box of 1,000 jars.
Check that naming when packaging becomes real, not before.

---

## 4. Cardinality summary

```
Item(stock_source: Ledger)                    ← herbs today; packaging later
  └─* Lot(charge) *─1 Delivery *─1 Supplier
        └─* Bag                               ← "bag" is herb-shaped (§3.6)
              └─* Movement

Item(stock_source: External)                  ← variants; balance lives in Billbee
        (no lots, no bags, no movements — see §3.7)

Bottle 1─* Batch 1─* Fill ─1 Item(External)
              │
              └─* Movement (consumption, against Ledger items)
```

- One item → many lots
- One supplier lot → many bags (43 cases today)
- One bottle → many batches (477 of 980 today)
- One batch → many fills (270 mix-groups today)
- One batch → many consumption movements, possibly several per item (multi-lot
  allocation — currently impossible)
- One bag → many movements

---

## 5. What each current column becomes

| Today | Becomes |
|---|---|
| `bags.size` | `receipt` movement |
| `bags.trashed` | `loss` movements, dated, with reason |
| `bags.deleted_at` | Balance reaching 0 |
| `Bag::discard()` | `loss` movement for the remainder |
| `Bag::getCurrent()` | `SUM(quantity) WHERE bag_id` |
| `HerbStock::forBags` / `::fromAggregates` | One query, one definition |
| `ingredients.amount` | `consumption` movement |
| `ingredients` (row) | `Allocation` on a batch |
| `bottle_positions.charge` | `Batch.printed_number` (value, not identity) |
| `BottlePosition::getCharge()` | Assigned at batch creation; passthrough kept |
| `MassBalance`'s "not dateable" caveat | Gone — losses are dated |
| `variants.stock` | Read through `Item(source: External)` → Billbee (§3.7) |
| `Herb` (stock-facing) | `Item(type: herb, base_unit: Gram, source: Ledger)` |
| `Herb` (everything else) | Unchanged — stays in `/catalog` (§3.8) |
| `Variant` (stock-facing) | `Item(type: variant, base_unit: Piece, source: External)` |
| `Variant` (everything else) | Unchanged — SKU, size, price stay in `/catalog` |

---

## 6. Printed numbers vs. identity

**Decision: the passthrough stays.** It is not the problem. The problem is that
the printed number is currently doing double duty as the identity.

`BottlePosition::getCharge()` returns the **supplier's** charge verbatim when a
product has exactly one recipe ingredient. 79 of 160 products are single-herb;
**1,951 of 2,369 charged positions** carry a supplier number. For a pure
single-herb product that is the *right* number to print — it traces the jar
straight back to the sack.

### The split

```
Batch
  id              -- identity. A row. Never a string.
  printed_number  -- what goes on the jar:
                  --   single-herb  → the supplier lot number (passthrough)
                  --   blend        → generated ymd + sequence
  supplier_lot_id -- set when printed_number came from a lot (provenance)
```

`printed_number` is **not unique** and is not expected to be. Two batches
sharing one are still two batches. Every trace, join, and recall query follows
`batch_id`; nothing joins on the string.

This resolves all three failures without giving up the passthrough:

1. **Recall stops being ambiguous.** "Which batches contain supplier lot X?" is
   a join through movements → bags → lot. It never depends on what was printed.

2. **Uniqueness stops mattering.** Two suppliers may both use `42799`. Since the
   string is not an identity, nothing collides.

3. **Multi-lot allocation works.** When a single-herb product draws from two
   lots, there is no single supplier charge to inherit → fall back to a
   generated number. The rule becomes: pass through *only* when the batch drew
   from exactly one lot.

### 6.1 Stale passthrough charges — 15 known-bad

`getCharge()` runs once in `static::created`. If the bag selection changes
afterwards — which `PositionBagSelector::selectBag()` allows freely — the charge
is never recomputed.

Result: **15 positions carry a charge belonging to a bag they never drew from**,
usually a different herb entirely.

| Position | Printed | Actually drew from | That charge belongs to |
|---|---|---|---|
| 82 | 35384 | bag 37 (`34394`) | bag 36 — Frauenmantelkraut |
| 1253 | 40909 | bag 209 (`49627`) | bag 198 — Pfefferminzblätter |
| 1424 | 25700 | bag 330 (`55224`) | bag 21 — Preiselbeerblätter |
| 2146 | 55577 | bag 95 (`42521`) | bag 285 — Ringelblumenblüten |
| 2250 | 32136 | bag 97 (`41923`) | bag 31 — Himbeerblätter |

*(10 more; full list to be generated during phase 4.)*

These are jars in the field labelled with a lot number that traces to material
they do not contain. Unlike the other 1,936, these are **genuinely wrong as
printed values** and cannot be corrected — the jars are gone.

**Migration rule:** verify every historical passthrough against the bags actually
drawn from. Matches keep `supplier_lot_id`. The 15 mismatches are flagged
`printed_number_unverified` with the discrepancy recorded.

> Whether the 15 require disclosure to the Kontrollstelle is a domain decision
> (§9.2).

### 6.2 The generated format is sound

418 positions carry generated charges (`ymd` + sequence), 355 distinct. These
**cannot collide** with supplier numbers: generated values start with a 2-digit
year (20–26); all 22 seven-digit supplier charges start with `08`. The namespace
separation holds.

Keep the format. Fix the derivation:

- Assign at batch creation, not `static::created` on a position
- Unique index on `(printed_number)` where generated
- Sequence from a real counter, not `COUNT(id < ?)` — the current read-then-write
  races under Octane
- Delete `CHARGE_NOT_CALCULATABLE` (43 positions): an assigned number never fails

---

## 7. Known data debt to encapsulate

52 bags are currently physically impossible — more grams left them than entered.
Total phantom: 9.3 kg. Reconstructed via per-bag step graphs into three groups:

| Group | Count | Cause | Resolution |
|---|---|---|---|
| **A** | 12 | `discard()` wrote `trashed` from a stale/rounded balance | `adjustment / discard_rounding` |
| **B** | 24 | Derived-amount drift; final draw tips the bag negative | `adjustment / derived_amount_drift` |
| **C** | 14 | Draw recorded mid-life against an already-empty bag | `adjustment / unattributed_draw` |

> **Unresolved arithmetic:** 12 + 24 + 14 = **50**, not 52. Either a group count
> is wrong or 2 bags are unclassified. This predates the v3 proposal and must be
> settled before phase 2, which needs an exclusion list of exactly N known bag
> ids — a list that cannot be built from three groups that do not sum.

Group C is the fingerprint of the missing multi-lot allocation: bag 20 had 30.1 g
left when 800 g was recorded against it, then was used again. The operator almost
certainly emptied those 30 g and took the rest from another lot — the schema had
no way to say so.

**Reconstruction was attempted and rejected.** Only 5 of 16 candidate donor lots
fit the "dormant afterwards, still had material" pattern, and 3 group-C bags have
no candidate at all. Writing inferred `bag_id` attributions into a traceability
system to make arithmetic balance is worse than recording honest uncertainty.

These become typed `adjustment` movements in a dedicated migration once the
ledger exists (§10, phase 6).

---

## 8. Modules

Code in English, UI in German. Modules are Composer path packages
(`internachi/modular`); boundaries are checked with Pest arch tests, not
enforced by the framework.

```
/catalog       Item, Herb, Product, ProductType, Variant, RecipeIngredient, RecipeVersion
               What things ARE. No quantities. Item carries base_unit + stock_source.

/stock         Supplier, Delivery, Lot, Bag, StockMovement
               How much EXISTS. The ledger — and the Billbee-backed source (§3.7).

/production    Bottle, Batch, Fill, ProductionPlanner
               Depends on catalog (recipes) + stock (draws).

/traceability  BioInspector, Certificate, Warenweg, MassBalance
               Read-only across catalog + stock + production.

/labels        Label, templates, ParameterResolver     → depends on catalog
/billbee       sync, order import, stock push          → anti-corruption layer
/reporting     HerbStats, VariantStats, forecasts      → reads all, nothing reads it
```

Dependencies are acyclic. `/stock` depends on nothing but itself.

### 8.1 Herb belongs to `/catalog`, not `/stock`

A herb is a material master record — it has no quantity. Its stock is a
`/stock` query *about* it. This mirrors SAP's split between `MARA` (material
master) and `MARD` (stock per location, a derived aggregate).

Consequence: `herbs.stats` / `Herb::currentStock` is a **cache of a `/stock`
query**, not a `/catalog` attribute. SAP ships a reconciliation report for
exactly this cache (`MB5B`); we need the equivalent test — *herbs.stats matches
the ledger*.

`Item` follows the same split when it lands: the item record and its
`base_unit` are catalog; `stock_source` names which `/stock` implementation
answers for it (§3.7).

### 8.2 The ledger points at items, not herbs

`stock_movements.item_id`, never `herb_id`.

The weak argument for this is rename cost. It is weak: a column rename is one
`ALTER TABLE` plus a grep, and if it were ever needed it would ride along inside
the migration that repoints the foreign key, for free.

**The real argument is that item-with-type is where this model ends up.** A
process consumes items and produces items; "raw / intermediate / finished" is a
position in a graph, not three table designs. SAP is `MARA` + `MARD` + a
movement type; Odoo is `product.product` + `stock.move`. Neither has a herb
table and a finished-goods table. The ledger should be named for its
destination.

**Until `items` exists, `item_id` is a plain unsigned bigint pointing at
`herbs.id`.** No polymorphism, no type discriminator. The generalisation is
deferred; only the name is not. The later repoint is cheap because `item_id` is
recomputable from `bag_id` while herbs are the only ledgered item — see phase 4
for the mechanics. That recomputability expires exactly when `bag_id` stops
being universal (§3.6); both happen for the same reason, at the same time: the
first non-herb ledgered item.

#### Units live on the item, not on the movement

Quantity is meaningless without a unit — SAP's answer, and it is right. But the
placement is `items.base_unit`, not a column on `stock_movements` (§3.6): herbs
are `Gram`, variants and packaging are `Piece`, and every movement for an item
is in that item's base unit, always. A per-row unit could disagree with the
item's, which would need a constraint to prevent.

No conversion table. SAP carries UoM per line because it genuinely converts —
purchase in cases, stock in pieces, `MARM` between them. We have no such case.
If KISA ever buys in one unit and consumes in another, that is when to revisit
— not before.

### 8.3 Lots carry a status

```php
enum LotStatus { Unrestricted; QualityInspection; Blocked; }
```

Status is a property of provenance-identified material, so it sits on the lot —
not on the bag, not on the item. `Blocked` refuses consumption without deleting
anything, which is how a supplier recall gets handled *before* the material is
written off.

**This ships with `Lot` in phase 4**, not phase 1. The `Blocked` check belongs
inside the same locked transaction as the reject-if-negative guard, so §9.5's
transaction gets reopened — write it in phase 1 such that each reopening is an
added condition rather than a restructure (§9.5). Phases 1–3 have no lot to
block, so there is nothing for it to act on before then.

### 8.4 Recipes are versioned

`herb_product` is a live pivot with no history. Editing a recipe silently
rewrites what past batches were made from.

We have already paid for this twice: the Nr. 64 percentages had to be
reconstructed from a **paper recipe book**
(`2026_07_24_200000_fix_nr64_bottling_ingredient_amounts`), and freezing
`ingredients.amount` (`2026_07_24_190000`) is a mitigation for the symptom, not
the cause. Where no bottling exists, the recipe version is simply gone.

`RecipeVersion` (product_id, valid_from, created_by); recipe edits append a
version instead of `UPDATE`ing the pivot.

**Must land before phase 4**, which reconstructs 270 historical mix-groups by
reading recipes. Against an unversioned pivot that means rebuilding 2020
batches with 2026 percentages. It has its own phase (3.5) so this cannot be
lost as prose.

### 8.5 Deliberately deferred

| Gap | Why it can wait |
|---|---|
| Storage location / bin | One room, one operator |
| Planned-vs-actual yield & scrap variance | `fill_variance` at close-out is enough for now |
| Stocktake as a first-class document | `inventory_count` as an adjustment reason covers it |

---

## 9. Open questions

### 9.1 `Lot` as an entity — resolved, deferred to phase 4
Introduce it. Not because of the 43 multi-bag charges, but because **`charge`
alone was never an identity.**

**But not in phase 1.** Unlike `item_id` — a name, free to get right now — `Lot`
is real modelling work: lot-first delivery entry, an operator confirmation step,
`LotStatus` (§8.3), and a migration that groups 399 bags into lots. Phases 1–3
need none of it; bags keep `charge` as a string and the ledger points at
`bag_id`. Lots arrive when lot-level recall and blocking are actually needed,
alongside `Batch` in phase 4.

The lot key is:

```
(item_id, charge, supplier_id)
```

SAP scopes batch numbers per material by default (`MCHA.MATNR` + `CHARG`), and
keeps the supplier's own string in a separate column (`LICHA`, "vendor batch").
Odoo does the same — `stock.lot` is unique on `(name, product_id, company_id)`.
Nobody treats a supplier's batch string as globally unique, because it isn't:
two suppliers may both use `42799`.

Adding `supplier_id` to the key makes the collision safe, and makes the
*desired* merge automatic: reorder the same herb from the same supplier a
fortnight later, the charge is very likely identical, and it genuinely is the
same lot. That is why `42799` correctly spans 5 bags across 5 deliveries today.

**Inbound vs outbound — two different uses of the supplier's number:**

| | Inbound | Outbound |
|---|---|---|
| Field | `Lot.charge` | `Batch.printed_number` (§6) |
| Role | Identifying attribute of received material | Value printed on the jar |
| Link | — | `Batch.supplier_lot_id` |

Delivery entry becomes lot-first rather than bag-first:

```
Delivery (supplier, delivered_date)
  └── Position (item, charge, size)
        → resolve Lot by (item_id, charge, supplier_id)
        └── Bag × n     (one row per physical sack)
```

Resolution is **offered, not silent** — SAP's goods receipt asks the operator to
confirm receiving into an existing batch. That confirmation step is where a
human catches what the key cannot.

*To verify against the dump:* does any single supplier reuse a charge for the
same herb across genuinely unrelated deliveries? If so the key needs a validity
window or an operator override.

### 9.2 The 15 stale printed numbers
**Resolved for the other 1,936:** the passthrough stays; `printed_number` is a
value, `batch_id` is the identity (§6).

Still open: 15 jars in the field carry a lot number tracing to material they do
not contain (§6.1). The code is fixable; printed labels are not. Whether this
requires disclosure to the Kontrollstelle is a domain decision.

### 9.3 Is a recorded batch immutable?
**Recommendation: yes.** Corrections become compensating movements. This is what
kills the `count`-edit-rewrites-history bug.

### 9.4 The `grouped` toggle — resolved
**Grouping is genuine batch formation, not a display preference.** It must be
persisted on the batch, not left as a URL parameter.

Today the toggle is overloaded with two jobs:

1. *"These are separate mixes"* — a real batch boundary.
2. *"I need to draw one recipe from more than one bag"* — a workaround forced by
   the one-bag-per-herb-per-position constraint.

**Job 2 disappears under multi-lot allocation.** Once ops can allocate 500 g
across two bags, they never need to split a mix to reach a second lot.

**Ungrouping stays as an operator control.** Job 2 is removed; job 1 remains and
must be explicit. Two positions of one recipe in one run are physically
indistinguishable in the data whether they came from one bowl or two — only the
operator knows, so only the operator can say.

Consequences for phase 4:

- Grouping becomes explicit batch formation, persisted on the batch row.
  Default grouped, matching today's `$grouped = true`.
- Multi-bag draws become allocations, not grouping decisions.
- Separate batches get **separate `printed_number`s** — so two mixes of the same
  recipe on the same day carry different lot numbers. This is correct (a
  contaminated mix should be recallable alone) but is a visible change for ops:
  today both positions would share one charge.
- **The backfill cannot blindly trust historical grouping.** Two positions in one
  run, same product, different bags, may be either meaning. Some cases will be
  genuinely ambiguous — record what is known, do not infer (same posture as §7).
- Some of the 14 group-C bags are likely cases where ops *should* have ungrouped
  and did not. Resolution is unchanged (`adjustment / unattributed_draw`), but
  the schema was forcing the error, not the operator.

### 9.5 Concurrency
Reject-if-negative requires the balance check and the insert in one transaction
with a row lock on the bag. Under Octane, two simultaneous batches can otherwise
both pass. This is the enforcement point that makes the model trustworthy.

In phase 1 the guard is unconditional, because every ledgered item is a herb
with a bag. Write it so both phase-4 extensions land as one more condition
inside the same transaction, rather than a restructure:

1. **`Blocked` lots** refuse consumption (§8.3).
2. **Source-conditioning.** Once `stock_source` exists (§3.7), the guard applies
   to `Ledger` items only — `External` items have no bag to lock and
   legitimately go negative (64 variants do today).

### 9.6 `quantity` scalar type — decided by default, not by evidence
**`DECIMAL(12,3)`.** Chosen because it is the reversible side of an asymmetric
bet, not because sub-gram draws are known to be real:

- If sub-gram turns out to be real and the column is integer grams → full-table
  rewrite of an append-only ledger. The one migration this model is worst at.
- If sub-gram is never real and the column is decimal → three unused digits.

**Open:** does KISA ever weigh below a gram — a 0.5 g draw of something
expensive, or a scale reporting decigrams? Answering it can switch the column to
integer grams. **This must be settled before the phase-1 migration is written**,
and it is answerable by looking at a scale readout and the recipes with the
smallest ingredient amounts.

Never float, under either answer (§3.6).

### 9.7 The write path to external stock — open
Reads across `Ledger` and `External` are uniform (§3.7); writes are not. A
Billbee write is a network call that may land, may not, or may land twice, and
it can fail *after* the consumption movements have committed.

**Leaning: queue with an outbox**, because a Billbee outage should not stop an
operator filling jars. The design belongs in `/billbee`, not in the entity
model — but the outbox and its reconciliation job are real work and must sit in
phase 4's stated scope rather than surfacing as a surprise inside it.

---

## 10. Migration plan

**Recommendation: incremental, not big-bang.** Phases 0 through 6, with
`RecipeVersion` inserted as 3.5. Phases 1–2 are purely additive — nothing reads
the ledger, nothing breaks, and stopping there leaves the system in a valid
state. The first behaviour change is phase 3.

The reason not to do it at once: `Warenweg` alone is 1,546 lines with zero test
coverage, and it is the largest consumer of this model. A big-bang cutover would
change the schema and that page in one step, with no way to isolate a regression.

### Phase 0 — Characterisation tests *(prerequisite)*
Capture current output of `Warenweg`, `MassBalance`, `HerbStock`, and the
traceability MCP tools as fixtures. These currently have **zero tests**. Without
them there is no way to prove later phases preserve behaviour.

*Additive. No schema change.*

### Phase 1 — Ledger table
`stock_movements` + `StockMovement` model + the reject-if-negative rule (§9.5),
with tests. Nothing reads it yet.

Three schema decisions are load-bearing here because the table is append-only —
changing any of them later is a full-table rewrite:

- **`item_id`, not `herb_id`** (§8.2). A plain unsigned bigint pointing at
  `herbs.id` for now — no `items` table, no polymorphism. The name is the whole
  commitment.
- **`quantity` is `DECIMAL(12,3)`** (§3.6). Never float. **§9.6 must be answered
  before this migration is written** — it can still switch to integer grams.
- **No unit column.** Units belong on the item (§8.2).

Write §9.5's transaction so **both** phase-4 extensions — §8.3's `Blocked`
check and §3.7's source-conditioning — can later be added as one more condition
each, rather than a restructure.

*Additive. Safe to stop here.*

### Phase 2 — Backfill
- 399 `receipt` movements from `bags.size`
- 5,307 `consumption` movements from `ingredients.amount`
- ~163 `loss` movements from `bags.trashed`

**Gate:** the backfill must reproduce current per-item stock exactly, for every
item, before proceeding. That equality is the migration's test.

Two things make that gate unfalsifiable as stated, and both need naming first:

1. **Reproduce *which* definition?** `HerbStock::forBags` and
   `::fromAggregates` disagree by 44,856 g on a single herb (§11). The gate must
   name one as the reference. **`forBags` is the reference** — it derives from
   per-bag rows, which is what the ledger reconstructs; `fromAggregates` reads
   the `herbs.stats` cache, which §8.1 already classifies as a cache rather than
   a source. Record the delta for every herb where they disagree; that list is
   phase 3's regression check.

2. **The impossible bags fail it by construction** (§7) and are not fixed until
   phase 6. Exclude them explicitly, by id, with their expected variance
   recorded. An exclusion list of exactly N known ids is a gate; "allow small
   differences" is not. **Blocked on §7's arithmetic** — the three groups sum to
   50, not 52, so N is not yet known and the list cannot be built until that is
   settled.

*Additive. Safe to stop here.*

### Phase 3 — Migrate readers
Point `HerbStock`, `MassBalance`, `Bag::getCurrent()`, and the MCP tools at the
ledger. Delete the `forBags` / `fromAggregates` divergence by deleting one of
them.

*First behaviour change. Phase 0 fixtures must still pass.*

### Phase 3.5 — `RecipeVersion` *(prerequisite for phase 4)*
`RecipeVersion` (product_id, valid_from, created_by); recipe edits append a
version instead of `UPDATE`ing the `herb_product` pivot.

**Not optional and not deferrable past this point** — phase 4 reconstructs 270
historical mix-groups by reading recipes. §8.4 has the full argument and the two
occasions this has already cost us.

### Phase 4 — `Item`, `Lot`, `Batch`, multi-lot allocation
The large phase. Everything deferred out of phases 1–3 lands here.

**Before starting, re-check the §3.4 trigger:** is there a second real process
(drying, cutting, blending a blend, repacking)? If yes, build process/item
instead of `Batch` + `Fill` — retrofitting it under 270 backfilled batches is
much worse than building it now.

- `items` table; add `base_unit`, `stock_source`. Repointing `item_id` from
  `herbs.id` to `items.id` is an `UPDATE` across every row of a table whose
  defining rule is that rows are never updated — run it as a one-off migration
  with the constraint dropped, not through the model. Seeding `items.id =
  herbs.id` collapses it to a constraint swap (§8.2); the trick works right up
  until something else allocates ids in that space, so take it deliberately.
- `Variant` becomes an `External` item; `variants.stock` → `billbee_stock`
  (§3.7). Resolve the write path first (§9.7) — outbox and reconciliation are
  in scope, not a surprise.
- `lots` table keyed `(item_id, charge, supplier_id)`; lot-first delivery entry
  with the operator confirmation step (§9.1). `LotStatus` + the `Blocked` check
  inside §9.5's transaction (§8.3).
- `batches` table; migrate the 270 historical mix-groups. Grouping becomes
  persisted batch formation, not a URL parameter (§9.4) — and the backfill must
  not blindly trust historical grouping.
- Move `ingredients` → allocations on the batch
- Allocation UI: required amount, per-bag remaining, split entry, sum validation
- Retire the one-bag `updateOrCreate` in `PositionBagSelector`
- Retire `BottlePosition::getCharge()`; verify all 1,951 historical passthroughs
  and flag the 15 mismatches `printed_number_unverified` (§6.1)

*Touches `Warenweg`, `Recipes`, `TraceChargeTool`. Consider splitting once
scoped — this is five independent workstreams sharing one phase number:
`Item`, `Lot`, `Batch` + allocation, the Billbee write path (§9.7), and the
passthrough verification.*

### Phase 5 — Retire legacy columns
Drop `bags.trashed`, `bags.deleted_at`, `Bag::discard()`,
`bottle_positions.charge`. Add the "bag empty" close-out action that writes
`fill_variance` adjustments — this is where real supplier fill variance finally
gets recorded honestly.

### Phase 6 — Encapsulate the 52 outliers
The §7 migration. Typed `adjustment` movements with reasons. Runs last, because
it needs the ledger, and its numbers should be verified against the phase-2
backfill.

---

## 11. What this fixes

- Two stock definitions that disagree by 44,856 g on a single herb
- 52 physically-impossible bags, and the mechanism that keeps creating them
- `count` edits silently rewriting historical draw quantities
- `trashed` carrying two incompatible meanings in one column
- Ausschuss being undateable, weakening the mass balance
- Recall ambiguity from a printed number doing duty as an identity — 36 distinct
  physical mixes currently share the label `55224`
- 15 jars printed with a lot number tracing to material they do not contain
- `CHARGE_NOT_CALCULATABLE` in production
- A charge sequence that races under Octane (read-then-write, no unique index)
- N+1 on every per-bag stock computation
- Multi-lot draws being unrecordable — the root cause of group C
- `variants.stock` reading as a KISA balance when it is a cache of Billbee's —
  64 variants currently negative, legitimate but indistinguishable from
  corruption without that context

## 12. What this deliberately does not fix

- **Finished-goods history.** Billbee keeps it; KISA holds a snapshot (§3.7).
- **Processes other than mix-and-fill.** `Batch` + `Fill` is a special case of
  process/item, taken because there is one process today (§3.4).
- **Packaging.** The reason the ledger is item-based, but no phase builds it
  (§3.8).
- **Storage location, yield/scrap variance, stocktake documents** (§8.5).
