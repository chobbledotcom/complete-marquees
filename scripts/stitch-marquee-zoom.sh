#!/usr/bin/env bash
#
# Take the first N seconds of marquee-zoom-1.mp4 .. marquee-zoom-7.mp4 and
# stitch them into a single marquee-zoom.mp4.
#
# NOTE: the source clips are H.264 with heavy B-frame GOPs (one keyframe per
# clip, pattern I B B B B B B B P ...). A pure stream-copy trim cuts mid-GOP and
# leaves dangling B-frame references plus out-of-order timestamps at every join,
# which plays back stuttery. So we re-encode in a single pass: trim + concat via
# filter_complex, then encode once at near-visually-lossless quality (CRF 18).
# Exact 2.000s per clip, smooth output.

set -euo pipefail

DURATION="${DURATION:-2}"
OUTPUT="${OUTPUT:-marquee-zoom.mp4}"
CRF="${CRF:-18}"
PRESET="${PRESET:-slow}"
INPUTS=(marquee-zoom-{1..7}.mp4)

# Work in the repo root regardless of where the script is called from.
cd "$(dirname "$0")/.."

# Build -i args and the filter graph dynamically.
inputs_args=()
filter=""
labels=""
i=0
for input in "${INPUTS[@]}"; do
  if [[ ! -f "$input" ]]; then
    echo "Missing input: $input" >&2
    exit 1
  fi
  inputs_args+=(-i "$input")
  filter+="[${i}:v]trim=0:${DURATION},setpts=PTS-STARTPTS[v${i}];"
  labels+="[v${i}]"
  i=$((i + 1))
done
filter+="${labels}concat=n=${i}:v=1:a=0[out]"

ffmpeg -hide_banner -loglevel error -y \
  "${inputs_args[@]}" \
  -filter_complex "$filter" \
  -map "[out]" \
  -c:v libx264 -crf "$CRF" -preset "$PRESET" -pix_fmt yuv420p \
  "$OUTPUT"

echo "Wrote $OUTPUT (${DURATION}s x ${#INPUTS[@]} clips, CRF ${CRF})"
