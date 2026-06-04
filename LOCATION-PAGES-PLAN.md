---
permalink: false
eleventyExcludeFromCollections: true
---

# Location pages — improvement plan

A plan for working through the 27 pages under `Complete-Marquees/`. Aimed at the same standard as the EEAT and VOICE docs: verifiable, plain-spoken, not over-claimed, useful to a planner doing real research rather than a search engine reading boilerplate.

This is a working draft. There are decisions in section 8 that the owner needs to weigh in on before phase 3 starts.

---

## 1. The current state

Triage of the 27 pages by content health:

### Type A — heavily AI-rewritten (full rewrite)
- `aldershot.html` (60 ChatGPT artifacts, emoji headings, fragments)
- `andover.html` (58 ChatGPT artifacts, same pattern)

These are the worst of the set. The headings carry circus-tent and fireworks emojis (🎪 🎉 💍 📞 📍), the paragraphs carry `data-start` / `data-end` / `data-section-id` attributes lifted straight from a ChatGPT export, and the prose is wedding-magazine vocabulary throughout.

### Type B — lightly AI-touched (targeted clean-up)
- `hampshire.html` (7 ChatGPT artifacts, mixed)

The county overview page got an AI rewrite but only a partial one. It needs the AI fragments cleaned out and a fresh opening paragraph written.

### Type C — original voice, generic (light edit + standardisation)
- The remaining 24 pages.

These are still in the pre-AI house voice (e.g. salisbury, southampton: *"Complete Marquees has been delivering their renowned marquee hire service in Southampton since 2002. As a family run business..."*). They aren't broken, but they are:
- Near-duplicates of one another with only the town name swapped
- Dated phrasing ("renowned marquee hire", "wonderfully modern outdoor shelter")
- Generic — no genuine local knowledge per page
- Inconsistent in their use of the existing shared includes (some use `location-page-body.html`, some don't)

This is the bigger job in volume but a smaller job per page than Type A.

---

## 2. The shared-include architecture as it stands

Three includes already exist for location pages:

- `_includes/location-header.html` — opens body / main / grid / sidebar scaffolding.
- `_includes/location-page-body.html` — the "by linking together our Capri marquees" boilerplate paragraph, plus the trailing "many years of experience" paragraph.
- `_includes/location-event-types.html` — the bulleted list of event types (Wedding / Party / Corporate / Fun Day / Garden Parties of any size).

Use is uneven:
- 14 pages include only the header (1 include each).
- 13 pages include header plus body or event-types (2 includes each).

The architecture is right; it's just not consistently applied. Refactor to one shared boilerplate block per page, plus a small location-specific block.

---

## 3. The new per-page shape

A target template for every location page after this work, in order:

1. **One H1**, location-specific. Example: *"Marquee hire in Guildford"*. Not *"Complete Marquees in Guildford"*. No emojis.

2. **Lead paragraph** (location-specific, the only genuinely unique block on most pages). Real content, not template-spun. Three good options for what to put here, in order of quality:
   - A factual route + distance line: *"Guildford is about an hour up the A3 from our workshop in Havant. We deliver into the surrounding villages too — Compton, Shalford, Shere — and we know the routes."*
   - A real local reference point: a venue we've delivered to, an area characteristic, a specific kind of garden common to that town.
   - A real customer / event reference if one exists for that area.

3. **Standard credentials block** (shared include). One or two short paragraphs:
   - Family-run by David and Joanne from Havant since 2002.
   - Registered limited company, two physical addresses (workshop and registered office), insurance and fire-retardancy certificates available on request *(once those are confirmed and published — see EEAT-CREDENTIALS.md section 7)*.
   - In-house team, no sub-contracting.

4. **What we hire** (shared include). Capri sizes, linking kit, Pagoda, side walls plain and clear, matting, lighting. Two to three sentences, no lists.

5. **Honest site rules** (shared include or short paragraph). Flat grass, no trees or hedges through the footprint, can't tie into a building. Same plain language as the FAQ.

6. **Packages link with one real "from" price** to anchor the reader against the back-garden hirers and the wedding-luxury operators. *"An 80-guest seated package starts from £995 plus VAT including delivery, set-up, flooring and uplighting."*

7. **One named, schema-marked testimonial** (shared include, can be the same one across many pages). Use the existing four — Judith, Jane, Lucy, Johan — rotating where it makes sense.

8. **Site-visit and contact line**. Plain: *"We'll come and have a look at your garden before you commit. Call 02392 717 925 or send the form below."*

9. **Existing footer / sidebar / map iframe** (already shared).

Aim for under 350 words of body copy per page. The current pages run around 250-500 words; the model gets us closer to 300, with the location-specific block taking around 60-80 of those.

---

## 4. Phase plan

### Phase 1 — quick wins, all pages (one PR)
Surgical, no rewriting yet. Lower-risk, high-readability.

- Strip every `data-start`, `data-end`, `data-section-id`, `data-is-last-node`, `data-is-only-node`, `data-message-model-slug`, `data-message-id`, `data-turn-id`, `data-testid` attribute from every page. Regex-able across the corpus.
- Strip the embedded ChatGPT export HTML on `capri-marquee-hire.html` (the `<section class="text-token-text-primary..."` block — see EEAT doc section 7, item 8).
- Remove emoji from headings on `aldershot.html` and `andover.html` (🎪 🎉 💍 📞 📍 and the green ✅ on the home page too).
- Replace every em-dash (`—`) with either a spaced hyphen, a comma, or a full stop. Per VOICE.md em-dashes are a generated-copy tell. This is one of the cleanest single passes available.
- Reword the royal-wedding sidebar line per EEAT-CREDENTIALS.md section 3.

After phase 1: the corpus is no longer self-evidently AI-generated to a casual reader looking at the source.

### Phase 2 — boilerplate consolidation (one PR)
- Extract a single new include `_includes/location-credentials.html` containing the "family-run by David and Joanne from Havant since 2002" block, the registered-company line, and the in-house-team line. One source of truth.
- Extract a single new include `_includes/location-rules.html` containing the flat-grass / no-buildings / no-trees plain-language rules.
- Extract a single new include `_includes/location-packages-cta.html` containing the *"from £995 plus VAT…"* anchor and link to /packages/.
- Update every existing location page to call these includes consistently.
- Result: every page now has the same EEAT block; differentiation has to come from the location-specific lead paragraph.

### Phase 3 — pilot a model page (one small PR)
- Pick one location to write properly as the model. Recommend either:
  - **Havant** (the home base) — the easiest to write with real specifics, and most credible because we live there. *Currently no Havant page exists, only the postcode in meta.* Worth adding.
  - **Portsmouth** — adjacent to Havant, where the geographic phone number actually rings. Easy to write.
  - **Guildford** — biggest commercial centre in Surrey with real venues we'd plausibly have served, and the longest commute from Havant which is its own honest specific.
- Get the model approved by the owner before rolling out the pattern.

### Phase 4 — roll out (batched PRs by area)
- **Hampshire batch**: Hampshire, Portsmouth, Southampton, Fareham, Winchester, Basingstoke, Andover, Aldershot, Bournemouth (just over the Dorset line, but it's the existing page).
- **Surrey batch**: Surrey, Guildford, Woking, Camberley, Godalming, Esher, Cobham, Oxshott, Leatherhead, Epsom, Dorking, Sutton, Chessington.
- **Sussex / outliers batch**: Chichester, Crawley, Reading, Wokingham, Salisbury.

Five-or-so pages per PR keeps each one reviewable. Rough effort: 30 minutes of writing per page once the boilerplate is shared, so a batch of five is half a day.

### Phase 5 — dropped
Owner has confirmed all 27 pages stay. Plan ends at phase 4.

---

## 5. Voice and content rules per page

Per VOICE.md. Not negotiable:

- **No em-dashes anywhere** in any page in the corpus.
- **No fragment "sentences"** ("The lot." / "No fuss." / "Sorted.").
- **No wedding-magazine adjectives** (stunning / magical / luxe / bespoke / elevated / curated / your special day / fairy-tale).
- **No exclamation marks**.
- **No emojis** in any heading or body copy.
- **No first-name terms with strangers** ("Hey there!").
- **One specific** per page minimum. PO9, A3(M), M27, named villages, drive time. Generic claims about "professional service" don't count.
- **Plain word choice**. "The kit" not "our equipment range". "Set up properly" not "professionally installed". "We've been doing this a while" not "with extensive industry experience".
- **Long sentences with hedges**, not short polished ones with parallel structure.
- **The royal wedding line is reworded**, not removed: see EEAT doc section 3 for the honest phrasings.

---

## 6. EEAT facts to surface on every page

From EEAT-CREDENTIALS.md, the verifiable list:

- Family-run by **David and Joanne** since **2002**.
- Working out of **The Oakwood Centre, Downley Road, Havant, PO9 2NP**.
- Registered limited company **(number 1090110, registered office Southleigh Farm, Havant)** — once the Companies House cross-check is done.
- Geographic phone **02392 717 925** answering Mon-Fri 8am-9pm, weekends 8am-8pm.
- Capri specialist with linking-kit capability across four sizes plus Pagoda.
- Published prices and packages, VAT exclusive, Friday delivery / Sunday collection.
- In-house install team, evidenced in the testimonials.
- 27 named towns covered (where a town in the list is the page being written, this is the place to soft-claim local knowledge).

Things to **not** surface unverified:
- "Hundreds of events" without flagging it as our own number.
- "MUTA member" if we aren't.
- "Fully insured" without quoting the cover figure.
- "Royal wedding" as a top-line credential (per the EEAT doc correction).

---

## 7. Anti-patterns to remove on sight

A practical checklist for each page during phase 1 and phase 4:

| Pattern | Action |
|---|---|
| `data-start=`, `data-end=`, `data-section-id=` etc. | Strip the attribute, keep the content. |
| `<section class="text-token-text-primary..."` block on capri-marquee-hire.html | Delete the whole block. |
| Emoji in any heading (🎪 🎉 💍 📞 📍 ✅) | Delete the emoji. |
| Em-dash (`—`) | Replace with spaced hyphen, comma, or full stop. |
| "Stunning" / "magical" / "elevated" / "bespoke" / "luxe" / "curated" / "fairy-tale" / "your special day" | Cut or rewrite. |
| "Unforgettable" / "memorable" / "once-in-a-lifetime" as default adjectives | Cut. |
| "Stress-free" / "seamless" / "hassle-free" | Cut; demonstrate it instead. |
| Fragment closers ("The lot." / "Sorted." / "No fuss.") | Absorb into a complete sentence. |
| "Royal wedding" as a top-line claim | Reword per EEAT doc section 3. |
| Cinematic one-line summary | Cut. |
| Rule-of-three lists ("bigger, better, faster") | Cut. |
| Handle-the-objection lines ("and yes, even on a sloping lawn") | Cut. |

---

## 8. Owner answers (captured)

The gating questions from the first draft, with owner answers folded in:

1. **All 27 towns have genuinely been delivered in.** None are aspirational. The trading record is real. ✓
2. **No specific named venues to publish at this point.** Each location page will have to differentiate on route, drive time, area characteristic and trading-record rather than on named venues. The follow-up ask is in section 11 below. ⚠
3. **Drive times and routes** will be derived mechanically from Havant (A3 / A3(M) / A27 / M27 / A31 / A24 / M3) since no per-town venue data is available. ✓
4. **Keep all 27 pages.** Phase 5 (consolidation) is dropped. The plan now ends at phase 4. ✓
5. **No MUTA membership.** Honest copy must not imply otherwise. The gap stays in EEAT-CREDENTIALS.md section 5; commercial decision on whether to pursue. ✗
6. **£10 million public liability cover.** This is a strong line — double the £5m wedding-tier baseline. Surface on every page from phase 1 onwards. ✓
7. **The royal wedding line is dropped** from public copy. The sidebar CTA has been replaced with the £10m / family-run / in-house-team line in `_includes/sidebar-cta.html`. ✓

---

## 9. Definition of done (per page)

Every page after phase 4 should pass all of these checks:

- [ ] No `data-*` ChatGPT export attributes anywhere.
- [ ] No em-dashes.
- [ ] No emojis.
- [ ] No wedding-magazine vocabulary.
- [ ] No fragment sentences.
- [ ] One H1 naming the location.
- [ ] One genuinely location-specific lead paragraph.
- [ ] The shared credentials, rules, packages-CTA and testimonial includes called.
- [ ] One named testimonial visible.
- [ ] The phone number, opening hours and a site-visit offer are visible.
- [ ] The page reads in under 90 seconds aloud.
- [ ] WhatsApp test passed on every sentence.

---

## 10. Sequencing summary

| Phase | Scope | Effort estimate | Deliverable |
|---|---|---|---|
| 1 | Strip ChatGPT artefacts, em-dashes, emojis. Surface £10m PL on every page. (Royal-wedding sidebar already done.) | Half a day | One PR, mechanical |
| 2 | Refactor boilerplate into shared includes (credentials, rules, packages CTA) | Half a day | One PR, structural |
| 3 | Pilot model page (Havant or Portsmouth or Guildford) | Half a day | One PR, sets the bar |
| 4 | Roll out to remaining 26 pages in 3 batches | Two to three days | Three PRs |

Phase 5 (consolidation) is dropped per owner direction. Total: roughly four days of focused work across six PRs.

Phase 1 alone visibly improves the site in an afternoon and is worth doing first regardless of how phases 2-4 are sequenced.

---

## 11. Thin-page risk and per-town asks

Without named venue references (per owner answer 2 above), every location page is differentiated only by route, drive time, area type, and the standard EEAT block. For the bigger commercial centres this is enough — the area itself supplies content. For smaller commuter villages the lead paragraph risks being little more than "we drive to X from Havant".

The list below sorts the 27 pages into three risk tiers and notes what's writable today versus what could transform each page if even one detail per town is supplied.

### Tier A — area itself supplies content (writable now, no owner input needed)
Fourteen pages. The area, distance, road or characteristic gives the lead naturally:

| Page | Lead anchors |
|---|---|
| `hampshire.html` | County overview, our home county. |
| `surrey.html` | County overview, the routes north of Havant up the A3. |
| `portsmouth.html` | Adjacent to Havant; where the geographic phone rings. The Solent, the naval city. |
| `southampton.html` | Port city, M27 west, the university. |
| `winchester.html` | Cathedral city, county town, M3 corridor. |
| `salisbury.html` | Cathedral city, A36 / A30 over the Wiltshire border. |
| `chichester.html` | Roman walled city, A27 east, the festival theatre, the Goodwood country. |
| `bournemouth.html` | Coast, A31 west into Dorset, Sandbanks side. |
| `aldershot.html` | Army garrison town, A331. |
| `reading.html` | Thames Valley, M4 / A33, Berkshire edge of the area. |
| `guildford.html` | Surrey county town, North Downs, A3 north. |
| `woking.html` | Surrey commuter belt, A3 / A320. |
| `basingstoke.html` | Big commercial centre, M3 north, business parks. |
| `andover.html` | North Hampshire market town, A303 / A343. |

### Tier B — writable but thinner without specifics
Seven pages. Real content is available on route and area type; one specific from the owner per town would meaningfully thicken each.

| Page | Lead anchors today | One owner specific that would transform it |
|---|---|---|
| `fareham.html` | Between Portsmouth and Southampton, M27 corridor. | A garden / estate / hall we've delivered to, or a recurring local event. |
| `camberley.html` | Surrey / Hampshire border, near Sandhurst, M3. | Any military / officers' mess / private regimental event we can credibly reference (without breaching client confidence). |
| `dorking.html` | North Downs, Box Hill country, A24. | A named house, garden, or village fête we've covered. |
| `epsom.html` | Surrey, the Downs, racecourse country. | A garden party, a private event around Derby week, anything outside the racecourse itself. |
| `godalming.html` | River Wey, Charterhouse country. | A village name, a recurring summer event, or a school / charity job. |
| `wokingham.html` | Berkshire commuter, M4 corridor. | Any named event or area we've worked. |
| `crawley.html` | West Sussex, Gatwick edge. | A corporate / business-park job, or a private garden in the surrounding villages. |

### Tier C — at genuine risk of being thin
Six pages. The lead is sustainable on route and Surrey-belt characterisation, but each is small enough that without one detail the page reads as a near-duplicate of the next one.

| Page | Lead anchors today | One owner specific that would transform it |
|---|---|---|
| `cobham.html` | Small Surrey village, A3 corridor, large gardens, big-house country. | A named estate, garden, or recurring event we've delivered to. |
| `oxshott.html` | Very small Surrey village near Cobham, big-garden territory. | Any named estate, garden, charity day, or recurring event. |
| `esher.html` | Surrey, near the racecourse, A3 corridor. | Same — anything off the racecourse itself. |
| `chessington.html` | Surrey, A3 / A243. | A garden party, school event, village fête, anything outside the theme park. |
| `leatherhead.html` | M25 / A24 junction, Surrey. | A street, a school, an estate, a recurring event. |
| `sutton.html` | London edge of our coverage area, A24 / A3 link. | Any named estate, garden, recurring event, or the most distant job we've actually delivered into. |

### What we'd ideally get from the owner

Even one of any of these per Tier B/C town transforms the page from generic to credible:

- A first-name memory of a job ("the Hardisty wedding in Cobham, the church fête in Oxshott").
- A named house, estate, garden centre or hall we've set up at.
- A recurring event we cover (an annual fête, a regimental do, a charity fundraiser).
- A single street, area or village name within the town that customers come from.
- A funny or memorable job ("the one in Esher where the dog ate the matting") — even an anonymised anecdote.
- A landmark we use as a delivery reference ("a garden behind the church", "off the Portsmouth Road past the petrol station").

Send these in any form — text message, voice note, scribble. We take what comes in, paraphrase it into the house voice, and slot it into the relevant lead paragraph during phase 4.

If nothing comes in, every page is still writable. They just won't be the version of the page they could be.

### A separate observation: the £330 sidebar price
While editing the sidebar in this work, the second CTA card still reads *"Packages from only £330"*. The cheapest currently-published package on `packages.html` is **£390 + VAT** (25-guest standing-only). The £330 figure is either out of date or a different basis (single-marquee minimum, perhaps). Worth checking with the owner and aligning before phase 1 is published — the wrong figure on every page is a small but real credibility leak.
