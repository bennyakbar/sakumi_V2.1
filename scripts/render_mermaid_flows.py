#!/usr/bin/env python3
"""
Render Mermaid flow/graph blocks from Markdown files into SVG and PNG
using Graphviz as an offline fallback renderer.

This parser supports the subset used in this repository:
- graph/flowchart direction (TB/TD/LR/RL/BT)
- subgraph ... end
- node specs: A[Label], A{Decision}, A(Label), A["Label"]
- edges: A --> B, A -->|label| B
"""

from __future__ import annotations

import re
import subprocess
from dataclasses import dataclass, field
from pathlib import Path
from typing import Dict, List, Optional, Tuple


MERMAID_START = re.compile(r"^```mermaid\s*$")
MERMAID_END = re.compile(r"^```\s*$")
HEADER_RE = re.compile(r"^\s*(?:flowchart|graph)\s+([A-Za-z]+)\s*$")
SUBGRAPH_RE = re.compile(r'^\s*subgraph\s+(.+?)\s*$')
EDGE_RE = re.compile(r"^\s*(.+?)\s*-->\s*(?:\|([^|]+)\|\s*)?(.+?)\s*$")


@dataclass
class Node:
    node_id: str
    label: str
    shape: str = "box"


@dataclass
class GraphData:
    direction: str = "TB"
    nodes: Dict[str, Node] = field(default_factory=dict)
    edges: List[Tuple[str, str, Optional[str]]] = field(default_factory=list)
    subgraphs: Dict[str, str] = field(default_factory=dict)  # id -> label
    subgraph_order: List[str] = field(default_factory=list)
    node_group: Dict[str, str] = field(default_factory=dict)  # node id -> subgraph id
    _sg_counter: int = 0

    def next_subgraph_id(self) -> str:
        self._sg_counter += 1
        return f"sg_{self._sg_counter}"


def clean_label(value: Optional[str]) -> str:
    if not value:
        return ""
    value = value.strip()
    if value.startswith('"') and value.endswith('"'):
        value = value[1:-1]
    return value.strip()


def parse_subgraph(value: str, graph: GraphData) -> Tuple[str, str]:
    raw = value.strip()

    # subgraph ID["Label"]
    m = re.match(r'^([A-Za-z_][A-Za-z0-9_]*)\s*\[(.+)\]\s*$', raw)
    if m:
        sg_id = m.group(1)
        label = clean_label(m.group(2))
        return sg_id, label or sg_id

    # subgraph "Label"
    m = re.match(r'^"(.+)"\s*$', raw)
    if m:
        label = clean_label(m.group(1))
        sg_id = graph.next_subgraph_id()
        return sg_id, label or sg_id

    # subgraph ID
    m = re.match(r'^([A-Za-z_][A-Za-z0-9_]*)\s*$', raw)
    if m:
        sg_id = m.group(1)
        return sg_id, sg_id

    sg_id = graph.next_subgraph_id()
    return sg_id, raw


def parse_node_spec(spec: str) -> Tuple[str, Optional[str], Optional[str]]:
    """
    Returns: (node_id, label, shape)
    shape in {"box", "diamond", "ellipse"} or None
    """
    s = spec.strip()
    m = re.match(r"^([A-Za-z_][A-Za-z0-9_]*)\s*(.*)$", s)
    if not m:
        raise ValueError(f"Invalid node spec: {spec}")

    node_id = m.group(1)
    rest = m.group(2).strip()
    if not rest:
        return node_id, None, None

    if rest.startswith("{") and rest.endswith("}"):
        return node_id, clean_label(rest[1:-1]), "diamond"
    if rest.startswith("(") and rest.endswith(")"):
        inner = rest[1:-1]
        # handle ((text))
        if inner.startswith("(") and inner.endswith(")"):
            inner = inner[1:-1]
        return node_id, clean_label(inner), "ellipse"
    if rest.startswith("[") and rest.endswith("]"):
        return node_id, clean_label(rest[1:-1]), "box"

    return node_id, None, None


def upsert_node(graph: GraphData, node_id: str, label: Optional[str], shape: Optional[str]) -> None:
    if node_id not in graph.nodes:
        graph.nodes[node_id] = Node(node_id=node_id, label=label or node_id, shape=shape or "box")
        return

    node = graph.nodes[node_id]
    if label:
        node.label = label
    if shape:
        node.shape = shape


def parse_mermaid_block(lines: List[str]) -> GraphData:
    graph = GraphData()
    stack: List[str] = []

    for raw in lines:
        line = raw.strip()
        if not line or line.startswith("%%"):
            continue

        h = HEADER_RE.match(line)
        if h:
            direction = h.group(1).upper()
            graph.direction = "TB" if direction in {"TD", "TB"} else direction
            continue

        sgm = SUBGRAPH_RE.match(line)
        if sgm:
            sg_id, sg_label = parse_subgraph(sgm.group(1), graph)
            if sg_id not in graph.subgraphs:
                graph.subgraphs[sg_id] = sg_label
                graph.subgraph_order.append(sg_id)
            stack.append(sg_id)
            continue

        if line == "end":
            if stack:
                stack.pop()
            continue

        em = EDGE_RE.match(line)
        if em:
            src_id, src_label, src_shape = parse_node_spec(em.group(1))
            dst_id, dst_label, dst_shape = parse_node_spec(em.group(3))
            edge_label = clean_label(em.group(2))

            upsert_node(graph, src_id, src_label, src_shape)
            upsert_node(graph, dst_id, dst_label, dst_shape)
            graph.edges.append((src_id, dst_id, edge_label or None))

            if stack:
                graph.node_group.setdefault(src_id, stack[-1])
                graph.node_group.setdefault(dst_id, stack[-1])
            continue

        # Standalone node definition
        try:
            nid, nlabel, nshape = parse_node_spec(line)
            upsert_node(graph, nid, nlabel, nshape)
            if stack:
                graph.node_group.setdefault(nid, stack[-1])
        except ValueError:
            # Ignore unsupported lines in fallback parser
            continue

    return graph


def esc(value: str) -> str:
    return value.replace("\\", "\\\\").replace('"', '\\"')


def graph_to_dot(graph: GraphData, title: str) -> str:
    rankdir = graph.direction if graph.direction in {"TB", "BT", "LR", "RL"} else "TB"
    out: List[str] = []
    out.append("digraph G {")
    out.append(f'  label="{esc(title)}";')
    out.append('  labelloc="t";')
    out.append('  fontsize=16;')
    out.append('  fontname="Arial";')
    out.append(f"  rankdir={rankdir};")
    out.append('  bgcolor="white";')
    out.append('  node [fontname="Arial", fontsize=10, style="rounded,filled", fillcolor="#f8fafc", color="#334155"];')
    out.append('  edge [fontname="Arial", fontsize=9, color="#475569"];')

    nodes_in_group: Dict[str, List[str]] = {}
    for nid, gid in graph.node_group.items():
        nodes_in_group.setdefault(gid, []).append(nid)

    emitted = set()
    for gid in graph.subgraph_order:
        glabel = graph.subgraphs[gid]
        out.append(f'  subgraph "cluster_{esc(gid)}" {{')
        out.append(f'    label="{esc(glabel)}";')
        out.append('    color="#cbd5e1";')
        out.append('    style="rounded";')
        for nid in sorted(nodes_in_group.get(gid, [])):
            node = graph.nodes[nid]
            out.append(f'    "{esc(nid)}" [label="{esc(node.label)}", shape={node.shape}];')
            emitted.add(nid)
        out.append("  }")

    for nid, node in sorted(graph.nodes.items()):
        if nid in emitted:
            continue
        out.append(f'  "{esc(nid)}" [label="{esc(node.label)}", shape={node.shape}];')

    for src, dst, label in graph.edges:
        if label:
            out.append(f'  "{esc(src)}" -> "{esc(dst)}" [label="{esc(label)}"];')
        else:
            out.append(f'  "{esc(src)}" -> "{esc(dst)}";')

    out.append("}")
    return "\n".join(out) + "\n"


def extract_mermaid_blocks(md_path: Path) -> List[List[str]]:
    blocks: List[List[str]] = []
    current: List[str] = []
    in_block = False

    for line in md_path.read_text(encoding="utf-8").splitlines():
        if MERMAID_START.match(line):
            in_block = True
            current = []
            continue
        if in_block and MERMAID_END.match(line):
            blocks.append(current[:])
            in_block = False
            current = []
            continue
        if in_block:
            current.append(line)

    return blocks


def render_dot(dot_path: Path, svg_path: Path, png_path: Path) -> None:
    subprocess.run(["dot", "-Tsvg", str(dot_path), "-o", str(svg_path)], check=True)
    subprocess.run(["dot", "-Tpng", str(dot_path), "-o", str(png_path)], check=True)


def main() -> int:
    repo_root = Path(__file__).resolve().parents[1]
    docs_dir = repo_root / "docs"
    out_dir = docs_dir / "flows"
    out_dir.mkdir(parents=True, exist_ok=True)

    source_files = [
        docs_dir / "Manual Flow Operasional Aplikasi Sakumi.md",
        docs_dir / "OPERATIONAL_HANDBOOK.md",
        docs_dir / "OPERATIONAL_HANDBOOK_EN.md",
        docs_dir / "OPERATIONAL_HANDBOOK_ID.md",
    ]

    generated: List[Tuple[Path, Path, Path, str]] = []

    for src in source_files:
        if not src.exists():
            continue
        blocks = extract_mermaid_blocks(src)
        base = src.stem.lower().replace(" ", "_")

        for idx, block in enumerate(blocks, start=1):
            graph = parse_mermaid_block(block)
            title = f"{src.name} - diagram {idx}"
            dot = graph_to_dot(graph, title)

            stem = f"{base}_diagram_{idx:02d}"
            dot_path = out_dir / f"{stem}.dot"
            svg_path = out_dir / f"{stem}.svg"
            png_path = out_dir / f"{stem}.png"

            dot_path.write_text(dot, encoding="utf-8")
            render_dot(dot_path, svg_path, png_path)
            generated.append((dot_path, svg_path, png_path, src.name))

    index_path = out_dir / "README.md"
    lines: List[str] = []
    lines.append("# Flow Diagram Assets")
    lines.append("")
    lines.append("Generated from Mermaid blocks using offline Graphviz fallback.")
    lines.append("Open the SVG for best quality, PNG for quick sharing.")
    lines.append("")

    current_src = None
    for _, svg, png, src_name in generated:
        if src_name != current_src:
            if current_src is not None:
                lines.append("")
            lines.append(f"## {src_name}")
            current_src = src_name
        lines.append(f"### {svg.stem}")
        lines.append(f"- SVG: [{svg.name}](./{svg.name})")
        lines.append(f"- PNG: [{png.name}](./{png.name})")
        lines.append(f"![{svg.stem}](./{svg.name})")

    lines.append("")
    lines.append("Regenerate:")
    lines.append("```bash")
    lines.append("python3 scripts/render_mermaid_flows.py")
    lines.append("```")
    lines.append("")
    index_path.write_text("\n".join(lines), encoding="utf-8")

    print(f"Generated {len(generated)} diagrams in {out_dir}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
