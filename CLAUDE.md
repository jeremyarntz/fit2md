# CLAUDE.md

@AGENTS.md

# fit2md — project context

fit2md is a Symfony console app that turns a workout's `.fit` file plus an optional text description into a compact markdown summary for an AI training agent. The goal is token efficiency: the output should carry what a coach needs and nothing else.

`README.md` has the user-facing docs: usage, the description file format, configuration and the roadmap. Read it when working on anything user-visible.

## How to work with me

- I'm rebuilding my Symfony and OOP skills, so I often want to write code myself. When a task is a good learning opportunity, explain the approach and let me write it. When I ask you to write code, briefly explain the key decisions.
- Keep explanations simple and concrete. Always give the file path for every change.
- Work in small steps. Run `make stan` and `make test` after each change, before moving on.
- Point out design problems or better approaches instead of silently following a flawed plan.

## Environment

Everything PHP runs inside Docker. Run commands from the project root on the host:

| Command       | What it does                              |
|---------------|-------------------------------------------|
| `make up`     | Start the container (needed before others)|
| `make test`   | PHPUnit                                   |
| `make stan`   | PHPStan, level 10                         |
| `make cs`     | CS-Fixer dry run                          |
| `make cs-fix` | CS-Fixer, applies fixes                   |
| `make sh`     | Shell inside the container                |

Other commands: `docker compose exec php <command>`, e.g. `docker compose exec php php bin/console fit2md:inspect workouts/test`.

- Stack: PHP 8.4, Symfony 8.1, Twig, PHPUnit 13, PHPStan 2 (level 10) with phpstan-symfony, PHP-CS-Fixer (`@Symfony` rules plus `declare_strict_types`).
- No database, no Doctrine. This is a stateless file transformer. Don't add persistence.
- It stays a console app for now. No HTTP endpoint or UI yet.
- Only the project folder is mounted into the container (`.:/app`), so all input and output paths must be inside the project.

## Architecture

```
src/
  Domain/       Pure workout concepts. No Symfony, no vendor types, no imports from other src/ folders.
  Parser/       Activity file parsers (.fit). ActivityParserInterface + ActivityParserRegistry.
  Description/  Description parsers (.txt). PlainTextDescriptionParser + ExerciseLineParser.
  Analysis/     Derived data: SegmentBuilder (blocks to laps), ZoneProfileProvider.
  Application/  Orchestration: SummarizeWorkout, WorkoutFolderLocator, WorkoutInput.
  Rendering/    SummaryRendererInterface, MarkdownRenderer (Twig), Twig filters in Rendering/Twig/.
  Command/      InspectActivityCommand: thin entry point, no logic.
templates/summary.md.twig   The output layout.
```

The flow: `WorkoutFolderLocator` finds the files, `SummarizeWorkout` parses them and builds a `WorkoutSummary` (activity + description + segments), `MarkdownRenderer` renders it, and the command writes it to stdout or `-o`.

Rules:

- **Dependencies point inward.** Everything may use `Domain/`; `Domain/` uses nothing else in the project.
- **The command stays thin.** Logic goes in `Application/` or below, so a future API endpoint can reuse it.
- **Renderers return strings.** They never touch the filesystem.
- **Templates arrange; PHP computes.** Calculations belong in domain methods or Twig filters, not template logic.
- **New file formats are new classes.** Implementing `ActivityParserInterface` (or `DescriptionParserInterface`) is enough; `#[AutoconfigureTag]` on the interface registers it. No config edits.
- **Don't pigeonhole to Orange Theory.** OTF specifics belong in configuration (zones via `FIT2MD_ZONES`) or switches (`SegmentBuilder::$firstLapIsWarmup`), not hardcoded logic.
- `src/Domain/` is excluded from service registration in `config/services.yaml`.

## Coding conventions

- `declare(strict_types=1);` in every file.
- Classes are `final`. Value objects are `final readonly` with constructor property promotion.
- Domain objects enforce their invariants in the constructor and throw `\InvalidArgumentException`. Example: `Activity` requires at least one sample, ordered by timestamp.
- PHPStan level 10 must pass. Annotate arrays precisely: `list<Sample>`, `non-empty-list<Sample>`, array shapes. Bare `array` is rejected.
- `treatPhpDocTypesAsCertain: false` is set, because runtime checks of external data are correct even when PHPDoc says the type is guaranteed.
- `mixed` must never escape an adapter. Cast and validate at the boundary (see `FitActivityParser::intOrNull()`).
- **Missing readings are `null`, never `0`.** A dropped heart rate reading must not drag an average down.
- **Times are UTC internally.** Convert to the display timezone only when rendering.
- Custom exceptions are empty classes extending `\RuntimeException`, one per failure kind, in an `Exception/` folder next to the code that throws them.
- Use `PREG_UNMATCHED_AS_NULL` when a regex has optional groups, and check with `null !==`.
- Don't add fields or features until something uses them.

## Testing

- Unit tests for pure classes (no kernel). `KernelTestCase` only when Twig or the container is needed.
- Use `#[DataProvider]` for parsers with many input cases.
- Fixtures live in `tests/Fixtures/` and are committed. `workouts/`, `output/` and `scratch/` are gitignored.
- The golden-file test (`tests/Rendering/SummaryRenderingTest.php`) compares output to `tests/Fixtures/tread_50_hyrox.expected.md`. When the output format changes on purpose, regenerate it — always copy the fixtures first:

  ```bash
  mkdir -p workouts/test
  cp tests/Fixtures/tread_50.fit tests/Fixtures/hyrox_p1w2.txt workouts/test/
  docker compose exec -e APP_ENV=test php php bin/console fit2md:inspect workouts/test -o tests/Fixtures/tread_50_hyrox.expected.md
  ```

  Then review the diff before committing. Never regenerate just to make a failing test pass without understanding the change.
- Tests don't read `.env.local`. Test zones are fixed in `.env.test`.

## Gotchas

**FIT library** (`adriangibbons/php-fit-file-analysis`): abandoned, archived, no license. Only `src/Parser/Fit/FitActivityParser.php` may reference it.

- A lap's `timestamp` is its **end**; `start_time` is its start.
- Record fields are column-oriented arrays keyed by Unix timestamp. Timestamps are already Unix, not FIT epoch.
- A message that occurs once comes back as a scalar, not an array. `column()` normalizes this.
- It emits `unpack()` warnings on COROS developer fields but still parses correctly. Symfony turns warnings into exceptions, so the constructor call is suppressed with `@`. Keep the suppression to that one line.
- The first sample of `coros_otf.fit` has no heart rate (`null`). That's correct.

**Laps and blocks**

- N lap presses give N + 1 laps. Lap 0 is the warmup; lap 1 matches the first description block.
- Blocks must be listed in the order they were performed.
- Mismatched counts must degrade gracefully: extra laps become "Lap N", extra blocks have no heart rate.

**Zone time** is the sum of time between consecutive samples, capped at 10 seconds per gap. Never count samples.

**Twig**

- `{% autoescape false %}` wraps the template. Twig guesses HTML escaping for unknown extensions like `.md`.
- Twig swallows the newline after a tag, and spaces before a tag are printed. Keep tag lines at the left edge.
- `{%-` trims whitespace including newlines; `{%~` trims spaces only.
- Twig can't be checked by PHPStan. After changing the template or anything it reads, run the command and the golden-file test.

**Environment files** load in order `.env`, `.env.local`, `.env.dev`, `.env.dev.local`; later files win. Personal settings go in `.env.local` only. `.env.dev` is committed and would override `.env.local`. `FIT2MD_ZONES` must always be defined (`[]` turns zones off).

**Namespaces**: PSR-4, `App\` maps to `src/`. A "Class `App\X\Y` not found" error where `Y` lives elsewhere almost always means a missing `use` statement. `make stan` reports all of them at once.

## Git workflow

- Branch per version (e.g. `v0.4.1-new-command`), merge to `main` with `--no-ff`.
- Tag milestones only: `git tag -a v0.4.1 -m "..."`, then `git push --tags`.
- **Main is always green**: `make test`, `make stan` and `make cs` pass before merging.
- Update `README.md` in the same change that finishes a user-visible feature.
- Never commit `.env.local`, `workouts/`, or real personal workout data.

## Status

Done: v0.1 through v0.4 (FIT to markdown, blocks matched to laps, zones per block, weights/reps/RPE parsing).

Next: v0.4.1, a `fit2md:new <type>` command that creates a dated workout folder with a blank `description.txt` from a template in `templates/descriptions/`. Plan: a `Type:` description field, templates for OTF, outdoor run and resistance training, and the field parser must accept empty values like `Coach:`. Generated templates should round-trip through the parser.

See the README roadmap for everything after that.