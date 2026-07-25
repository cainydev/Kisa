# KISA Entity Model

Target model for KISA v3. Every term below has exactly one meaning. Where a
term is currently ambiguous in the codebase, the ambiguity is named explicitly.

Status: **proposal**. Nothing here is built yet. Figures are from the
production dump as of 2026-07-24.

---

## 1. The core principle

> **Quantities are events. State is a fold over events.**

Today every quantity in KISA is derived state: `bags.size` minus a computed
sum, `bags.trashed` as a mutable column, `ingredients.amount` recomputed from
today's recipe. Derived state is why the same herb reports two different stock
figures depending on which code path you ask, and why editing a position's
`count` silently rewrites 2020's bottling records.

The target inverts this. One append-only ledger. Stock is `SUM(quantity)` —
never stored, never able to drift, because there is only one definition.

**Immutability is the load-bearing rule.** Movement rows are never updated or
deleted. A correction is a new compensating movement with a reason. This single
constraint eliminates the `count`-edit-rewrites-history bug, makes soft-deletes
on bags unnecessary, and produces the audit trail the application exists to
provide.

---

## 2. Vocabulary

| Term | Definition | Identifier assigned by | Today |
|---|---|---|---|
| **Item** | A stockable kind of thing | us | `Herb`, `Variant` |
| **Supplier lot** | Batch identity of *received* material | **the supplier** | `bags.charge` |
| **Bag** | One physical sack belonging to a supplier lot | us | `Bag` |
| **Production order** | One filling run — typically one day, one operator | us | `Bottle` |
| **Batch** | One *mix* of one recipe, in one quantity | **us** | *missing* |
| **Fill** | Batch → n units of one variant | us | `BottlePosition` |
| **Movement** | One signed quantity change against a bag | system | *missing* |
| **Sales order** | A customer purchase | Billbee | `Order` |

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

> **Resolved (§9.1):** `Lot` becomes a real entity, keyed on
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

A lot is **never deleted**. It reaches zero and stays in the record forever.
Deleting destroys traceability, which is the one thing an EU-organic audit
cannot tolerate.

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

### 3.6 Movement — the ledger

```
stock_movements
  id
  bag_id            -- the lot this gram belongs to (never null)
  herb_id           -- denormalised for per-herb aggregates
  quantity          -- signed grams: + in, − out
  type              -- receipt | consumption | loss | adjustment
  reason            -- nullable enum; required for loss and adjustment
  reference_type    -- polymorphic: Delivery, Batch, ...
  reference_id
  user_id           -- who
  occurred_at       -- when it physically happened
  note
  created_at        -- when it was recorded
```

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

### 3.7 Scope boundary: finished goods are Billbee's

**The KISA ledger covers raw materials only.**

`variants.stock` is *not* a KISA ledger — it is a cached snapshot of Billbee's
balance, overwritten on each sync. Evidence: 64 variants currently hold negative
stock (down to −10), which is legitimate Billbee state but looks like corruption
without that context.

Billbee owns sales, returns, corrections, and manual adjustments. KISA
contributes exactly one movement type (`Einlagerung` on bottling). Rebuilding a
finished-goods ledger locally would mean maintaining a permanently-behind copy —
the same divergence bug, across a network boundary.

| | Raw materials | Finished goods |
|---|---|---|
| Ledger | KISA `stock_movements` | Billbee |
| KISA holds | Full history | Cached `variants.stock` |
| Written by | Operators, batches | Billbee sync + KISA `Einlagerung` |

Bottling is the seam: consumption movements against herb lots (ours), one stock
delta pushed to Billbee (theirs).

**Recommended:** rename to `billbee_stock` or add `stock_synced_at`, so the
column reads as the cache it is.

---

## 4. Cardinality summary

```
Supplier 1─* Delivery 1─* Bag *─1 Lot(charge)
                            │
                            └─1─* Movement

Bottle 1─* Batch 1─* Fill ─1 Variant
              │
              └─* Movement (consumption)
```

- One supplier lot → many bags (43 cases today)
- One bottle → many batches (477 of 980 today)
- One batch → many fills (270 mix-groups today)
- One batch → many consumption movements, possibly several per herb (multi-lot
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
/catalog       Herb, Product, ProductType, Variant, RecipeIngredient, RecipeVersion
               What things ARE. No quantities.

/stock         Supplier, Delivery, Lot, Bag, StockMovement
               How much EXISTS. The ledger.

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

### 8.2 The ledger points at items, not herbs

`stock_movements.item_id`, never `herb_id`. A herb is one *kind* of item;
packaging (Doypacks, labels) will be others. SAP calls this the material type
(`ROH` raw / `VERP` packaging / `FERT` finished).

Renaming this column later means rewriting every movement row and every query.
Naming it right now costs nothing. Same argument as introducing `Lot` early.

### 8.3 Lots carry a status

```php
enum LotStatus { Unrestricted; QualityInspection; Blocked; }
```

Status is a property of provenance-identified material, so it sits on the lot —
not on the bag, not on the item. `Blocked` refuses consumption without deleting
anything, which is how a supplier recall gets handled *before* the material is
written off.

Built with the ledger, not after it: the check belongs inside the same locked
transaction as the reject-if-negative guard (§9.5). Retrofitting means
reopening the most correctness-sensitive code in the system a second time.

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
batches with 2026 percentages.

### 8.5 Deliberately deferred

| Gap | Why it can wait |
|---|---|
| Storage location / bin | One room, one operator |
| Planned-vs-actual yield & scrap variance | `fill_variance` at close-out is enough for now |
| Stocktake as a first-class document | `inventory_count` as an adjustment reason covers it |

---

## 9. Open questions

### 9.1 `Lot` as an entity — resolved
Introduce it. Not because of the 43 multi-bag charges, but because **`charge`
alone was never an identity.** The lot key is:

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

---

## 10. Migration plan

**Recommendation: incremental, not big-bang.** Six phases. Phases 1–2 are purely
additive — nothing reads the ledger, nothing breaks, and stopping there leaves
the system in a valid state. The first behaviour change is phase 3.

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

*Additive. Safe to stop here.*

### Phase 2 — Backfill
- 399 `receipt` movements from `bags.size`
- 5,307 `consumption` movements from `ingredients.amount`
- ~163 `loss` movements from `bags.trashed`

**Gate:** the backfill must reproduce current per-herb stock exactly, for every
herb, before proceeding. That equality is the migration's test.

*Additive. Safe to stop here.*

### Phase 3 — Migrate readers
Point `HerbStock`, `MassBalance`, `Bag::getCurrent()`, and the MCP tools at the
ledger. Delete the `forBags` / `fromAggregates` divergence by deleting one of
them.

*First behaviour change. Phase 0 fixtures must still pass.*

### Phase 4 — `Batch` entity + multi-lot allocation
- `batches` table; migrate the 270 historical mix-groups
- Move `ingredients` → allocations on the batch
- Allocation UI: required amount, per-bag remaining, split entry, sum validation
- Retire the one-bag `updateOrCreate` in `PositionBagSelector`
- Retire `BottlePosition::getCharge()`

*Largest phase. Touches `Warenweg`, `Recipes`, `TraceChargeTool`.*

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
