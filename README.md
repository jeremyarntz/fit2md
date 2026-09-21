# fit2md

Turn a workout's `.fit` file — plus an optional text description — into a compact markdown summary that an AI training agent can read cheaply.

A raw FIT file holds thousands of samples. fit2md reduces it to what a coach actually needs: the workout's structure, heart rate for each block, and time spent in each heart rate zone.

It was built around Orange Theory classes recorded on a COROS watch, but isn't tied to either. Activity parsers, description parsers and zone definitions are all swappable.

## What it does today

- Reads `.fit` activity files: heart rate, cadence, distance and laps
- Reads a plain-text workout description: title, coach, location, RPE, notes and blocks
- Reads weights, reps, rounds and RPE from block lines, in two notations
- Matches description blocks to watch laps in order, treating the first lap as a warmup
- Calculates heart rate stats and minutes per heart rate zone for each block
- Renders the result as markdown

## Requirements

- Docker (Docker Desktop on macOS)

That's all. PHP, Composer and every development tool run inside the container.

## Setup

```bash
git clone git@github.com:jeremyarntz/fit2md.git
cd fit2md
docker compose up -d --build
docker compose exec php composer install
```

Then set up your heart rate zones — see [Configuration](#configuration).

## Usage

Put each workout in its own folder:

```
workouts/2026-09-17-hyrox/
  workout.fit        # required: exactly one activity file
  description.txt    # optional: at most one description
  tread.png          # optional: screenshots, reserved for future OCR
```

Then run:

```bash
docker compose exec php php bin/console fit2md:inspect workouts/2026-09-17-hyrox
```

The summary prints to the screen. To write it to a file, add `-o`:

```bash
docker compose exec php php bin/console fit2md:inspect workouts/2026-09-17-hyrox -o output/2026-09-17.md
```

You can also pass a single `.fit` file instead of a folder.

A few things to know:

- `workouts/` and `output/` are gitignored. Workout data is personal and is never committed.
- Only the project folder is shared with the container, so input and output paths must be inside the project.

## Description file format

Plain text. Paste a workout write-up (for example, from the OTF subreddit) and add your own details at the top:

```
HYROX Phase 1 Week 2 2G
Coach: Alex
Location: My Studio
RPE: 8
Notes: Felt strong on deadlifts.

**Tread Block 1 (14.5 min)**
* 2.5 min tread
* 30 sec surge

**Floor Block 1 (14.5 min)**
* 8 x deadlift | 50# | 2 rounds | RPE 7
* 8 x pullover | 35#
```

The rules:

- The first plain line is the title.
- `Coach:`, `Location:`, `RPE:` and `Notes:` lines before the first block are read as fields. RPE accepts `8` or `8/10`.
- A bold line (`**...**`) starts a block. An optional `(N min)` gives its planned length.
- Every line after a block's header belongs to that block, until the next header.
- **List blocks in the order you did them.** In a 2G class that may mean moving the floor blocks to the top.

### Exercises: weights, reps and RPE

Lines inside a block can record what you actually did. Two notations work.

**Reddit style** — start from a pasted post and swap in your numbers:

```
8 x deadlift (slow) | 50# | 2 rounds | RPE 7
Skier swing | 25#
```

**Lifting style** — sets × reps @ weight:

```
Deadlift 2x8 @ 50 lb RPE 7
Push-up 3x15
```

After a `|`, each piece is read as a weight (`50#`, `35 lbs`, `60 kg`), rounds (`2 rounds`), or RPE (`RPE 7`). Anything else is kept as a note. Reps are optional.

Both notations come out in one consistent format:

```
*deadlift (slow):* 2x8 @ 50 lb, RPE 7
```

A line is only treated as an exercise if it contains at least one number fit2md understands. Anything else, like `2.5 min tread`, is shown as written.

### How blocks match laps

On the watch, press lap at the start of each block. A lap press *ends* the current lap, so N presses give N + 1 laps, and the first lap is everything before your first press — the warmup.

fit2md labels the first lap "Warmup", then matches lap 1 to the first block, lap 2 to the second, and so on. If the counts don't match, nothing breaks: extra laps appear as "Lap N", and extra blocks appear without heart rate data.

## Configuration

Personal settings go in `.env.local`, which is gitignored. Create it in the project root if it doesn't exist.

### Heart rate zones

```
FIT2MD_ZONES='[{"name":"Grey","max":100},{"name":"Blue","max":116},{"name":"Green","max":138},{"name":"Orange","max":151},{"name":"Red","max":null}]'
```

- Each zone lists only its upper limit in BPM. Each zone starts where the previous one ends.
- Readings below the first zone count as the first zone. `null` on the last zone means no upper limit.
- Zone names appear in the output exactly as written.
- For Orange Theory, copy the numbers from the OTF app: **Settings → Heart Rate**. Use the top number of each zone's range. If you change your max HR in the app, copy the new numbers.
- To turn zones off, set `FIT2MD_ZONES=[]` — this is the default in `.env`. Don't remove the variable entirely, or the app won't start.
- Computed zone minutes won't exactly match OTF's totals. OTF uses its own heart rate strap and starts counting before you start your watch.

Environment files load in this order, with later files overriding earlier ones:

1. `.env`
2. `.env.local`
3. `.env.dev`
4. `.env.dev.local`

Don't put personal settings in `.env.dev`: it's committed, and it overrides `.env.local`.

Tests don't read `.env.local`. They use the fixed zones in `.env.test`, so results don't depend on anyone's personal settings.

### Display timezone

Times are stored in UTC and converted only for display. The display timezone is currently set in `src/Rendering/MarkdownRenderer.php` (`America/Chicago`).

## Development

Run these from your Mac, in the project root:

| Command       | What it does                                      |
|---------------|---------------------------------------------------|
| `make up`     | Start the container                               |
| `make down`   | Stop the container                                |
| `make sh`     | Open a shell inside the container                 |
| `make test`   | Run the tests                                     |
| `make stan`   | Run PHPStan (level 10)                            |
| `make cs`     | Show code style problems without changing files   |
| `make cs-fix` | Fix code style problems                           |

Inside the container shell (`make sh`), run the underlying commands directly instead — for example `vendor/bin/phpunit`.

**Main is always green.** Before merging anything to `main`, `make test`, `make stan` and `make cs` must all pass.

### The golden-file test

`tests/Fixtures/tread_50_hyrox.expected.md` holds the exact expected output for a known input. When you change the output format on purpose, this test fails — that's its job. Check the diff, and if the change is what you intended, regenerate the file:

```bash
mkdir -p workouts/test
cp tests/Fixtures/tread_50.fit tests/Fixtures/hyrox_p1w2.txt workouts/test/
docker compose exec -e APP_ENV=test php php bin/console fit2md:inspect workouts/test -o tests/Fixtures/tread_50_hyrox.expected.md
```

Always copy the fixtures first, even if the folder already exists — the test reads the files in `tests/Fixtures/`, so the copies in `workouts/test/` must match them.

`APP_ENV=test` makes the command use the same zones as the test. Commit the template change and the updated expected file together.

## Project structure

```
src/
  Domain/       The workout concepts: Activity, Lap, Sample, Segment, zones,
                exercises. Pure PHP with no dependencies on anything else in
                the project.
  Parser/       Reads activity files (.fit) into domain objects.
  Description/  Reads description files (.txt) into domain objects, including
                the exercises on each line.
  Analysis/     Works things out from the data: matching blocks to laps, zones.
  Application/  Coordinates the work: finds the files, parses, builds the summary.
  Rendering/    Turns a summary into text, including the Twig filters for
                durations and exercises.
  Command/      The console command — a thin entry point.
templates/
  summary.md.twig   The output layout.
```

The rule that keeps this manageable: dependencies point inward. Everything may use `Domain/`, and `Domain/` uses nothing else.

Adding a new activity format means adding one class that implements `ActivityParserInterface`. It's picked up automatically — no configuration needed.

## A note on the FIT library

FIT parsing uses `adriangibbons/php-fit-file-analysis`, which is **abandoned**, archived, and has **no license** on its latest version. It was the best available option, so it's quarantined: only `src/Parser/Fit/FitActivityParser.php` knows it exists, and it can be replaced without touching anything else.

It prints warnings on some COROS files, because it doesn't recognise COROS's custom fields. It still parses the standard data correctly, so those warnings are suppressed at that one call.

## Roadmap

**Done**

- v0.1 — FIT file to markdown summary
- v0.2 — Description blocks matched to lap heart rate data
- v0.3 — Heart rate zones per block
- v0.4 — Weights, reps and RPE parsed from block lines

**Next**

- v0.4.1 — `fit2md:new` command to create blank description files per workout type (OTF, outdoor run, resistance training)
- v0.4.2 — AI description normalizer: turn any write-up into the description format
- v0.4.3 — Screenshot extraction (Tesseract or a vision model) for OTF summary data

**Housekeeping**

- Rename `fit2md:inspect` to `fit2md:summarize`
- Make the display timezone configurable
- Add a `Type:` field so zones and warmup rules can vary per workout type
- Clean up test fixtures: one real, matching workout folder inside `tests/Fixtures/`, so regenerating the golden file no longer needs a copy step; anonymize personal details before going public

**Someday**

- API endpoint and web UI
- An open-source, maintained PHP FIT parser to replace the abandoned library