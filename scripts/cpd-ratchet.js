#!/usr/bin/env bun

/**
 * CPD ratchet check - fails if the duplication threshold could be lowered.
 *
 * Reads the current --min-tokens value from .jscpd.json (the single source of
 * truth for this repo), then runs jscpd with minTokens - 1. If the codebase
 * still passes at the lower threshold, this check fails to force tightening
 * it. Pay down a clone, lower the threshold, and the ratchet keeps everyone
 * honest from there.
 *
 * A pass requires evidence that jscpd completed a scan: a fresh JSON report
 * in .jscpd-report/. A nonzero jscpd exit without one means jscpd itself
 * failed (missing dependency, invalid config, crash) and the ratchet fails
 * rather than reading the error as "clones found at the lower threshold".
 *
 * Ported from chobble-template's scripts/cpd-ratchet.js and renegade-solar's
 * variant. jscpd runs from the static musl binary scripts/jscpd.js installs
 * at .bin/jscpd (npm's glibc build is dynamically linked and won't start on
 * NixOS); JSCPD_BIN overrides it for restricted sandboxes.
 */

import { spawnSync } from "node:child_process";
import { existsSync, mkdirSync, readFileSync, rmSync } from "node:fs";
import { join, resolve } from "node:path";
import { ensureJscpd, JSCPD_BIN_PATH } from "./jscpd.js";
import { throwIfSpawnFailed } from "./cpd.js";

const ROOT_DIR = resolve(import.meta.dir, "..");
const REPORT = join(ROOT_DIR, ".jscpd-report", "jscpd-report.json");

// Read current threshold from .jscpd.json (single source of truth)
const config = JSON.parse(readFileSync(join(ROOT_DIR, ".jscpd.json"), "utf-8"));
const CURRENT_MIN_TOKENS = Number(config.minTokens);
if (!Number.isInteger(CURRENT_MIN_TOKENS) || CURRENT_MIN_TOKENS < 2) {
  throw new Error(`Invalid minTokens in .jscpd.json: ${config.minTokens}`);
}
const RATCHET_MIN_TOKENS = CURRENT_MIN_TOKENS - 1;

// Clear the previous report so its presence after the run proves jscpd
// completed this scan.
rmSync(REPORT, { force: true });
mkdirSync(join(ROOT_DIR, ".jscpd-report"), { recursive: true });

const runner = process.env.JSCPD_BIN
  ? [process.env.JSCPD_BIN]
  : [await ensureJscpd()];
const [file, ...baseArgs] = runner;

const result = spawnSync(
  file,
  [...baseArgs, "--min-tokens", String(RATCHET_MIN_TOKENS)],
  {
    cwd: ROOT_DIR,
    stdio: "inherit",
  },
);

throwIfSpawnFailed(result, "jscpd");

const status = result.status ?? 1;

if (!existsSync(REPORT)) {
  console.error(
    `\n❌ CPD ratchet failed: jscpd exited with status ${status} but wrote no report to .jscpd-report/jscpd-report.json`,
  );
  console.error(
    "   The scan never completed, so there is no evidence about duplication - fix jscpd and re-run.",
  );
  process.exit(1);
}

let clones;
try {
  clones = JSON.parse(readFileSync(REPORT, "utf-8")).duplicates.length;
} catch (error) {
  console.error(
    `\n❌ CPD ratchet failed: could not read ${REPORT}: ${error.message}`,
  );
  process.exit(1);
}

if (status === 0 && clones === 0) {
  // jscpd completed cleanly and found nothing at the lower threshold -
  // threshold can be tightened!
  console.error(
    `\n❌ CPD ratchet failed: code passed with minTokens=${RATCHET_MIN_TOKENS}`,
  );
  console.error(
    `   Update "minTokens" to ${RATCHET_MIN_TOKENS} in .jscpd.json`,
  );
  process.exit(1);
}

if (status !== 0 && clones > 0) {
  // jscpd completed and failed the threshold check - current threshold is correct
  console.log(
    `\n✅ CPD ratchet passed: minTokens=${CURRENT_MIN_TOKENS} is correct (${clones} clone(s) at ${RATCHET_MIN_TOKENS})`,
  );
  process.exit(0);
}

console.error(
  `\n❌ CPD ratchet failed: incoherent result - jscpd exit status ${status} with ${clones} clone(s) at minTokens=${RATCHET_MIN_TOKENS}`,
);
process.exit(1);
