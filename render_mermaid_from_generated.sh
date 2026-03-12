#!/bin/bash

BASE_DIR="$(cd "$(dirname "$0")" && pwd)"

INPUT_DIR="$BASE_DIR/docs/generated"
TMP_DIR="$BASE_DIR/docs/mermaid_tmp"
OUTPUT_DIR="$BASE_DIR/docs/diagrams"

mkdir -p "$TMP_DIR"
mkdir -p "$OUTPUT_DIR"

echo "Scanning Mermaid diagrams in $INPUT_DIR..."

for mdfile in "$INPUT_DIR"/*.md; do

  [ -e "$mdfile" ] || continue

  base=$(basename "$mdfile" .md)
  count=0

  awk '
  /```mermaid/ {flag=1; next}
  /```/ {flag=0}
  flag {print}
  ' "$mdfile" | awk -v base="$base" -v tmp="$TMP_DIR" '
  /^flowchart|^sequenceDiagram|^graph|^stateDiagram/ {
      count++
      file=sprintf("%s/%s_%02d.mmd", tmp, base, count)
  }
  { print >> file }
  '

done

echo "Rendering diagrams..."

for mmd in "$TMP_DIR"/*.mmd; do

  [ -e "$mmd" ] || continue

  name=$(basename "$mmd" .mmd)

  mmdc -i "$mmd" -o "$OUTPUT_DIR/$name.svg" --puppeteerConfigFile puppeteer-config.json 

done

echo "Done."
echo "Diagrams saved to $OUTPUT_DIR"
