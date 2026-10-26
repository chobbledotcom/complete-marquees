#!/usr/bin/env bun

/**
 * Point git at our committed hooks directory so the pre-commit verifier runs
 * for everyone after a `bun install`. No-ops outside a git checkout (e.g. the
 * sparse-checkout CI build) so it never breaks the deploy pipeline.
 */

import { execSync } from "node:child_process";

const isGitCheckout = () => {
  try {
    execSync("git rev-parse --git-dir", { stdio: "ignore" });
    return true;
  } catch {
    return false;
  }
};

if (isGitCheckout()) {
  execSync("git config core.hooksPath .githooks");
  console.log("Git hooks installed (core.hooksPath -> .githooks)");
}
