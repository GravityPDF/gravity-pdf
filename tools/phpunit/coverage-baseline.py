#!/usr/bin/env python3
"""Print a per-`src/`-subdir coverage breakdown from a PHPUnit Clover XML.

Usage: python3 tools/phpunit/coverage-baseline.py [path/to/clover.xml]

Default path: tmp/coverage/report-xml/baseline.xml. See
tests/phpunit/COVERAGE_BASELINE.md for how to produce the input XML.
"""

import sys
import xml.etree.ElementTree as ET
from collections import defaultdict


def bucket_for(path):
    if path.endswith(('/pdf.php', '/api.php', '/gravity-pdf-updater.php')):
        return 'Plugin root'
    if '/src/' not in path:
        return None
    rel = path.split('/src/', 1)[1]
    parts = rel.split('/')
    if len(parts) == 1:
        return 'src/ root'
    if parts[0] == 'Helper' and len(parts) >= 3:
        return f'Helper/{parts[1]}'
    return parts[0]


def main(xml_path):
    tree = ET.parse(xml_path)
    buckets = defaultdict(
        lambda: {'st': 0, 'covst': 0, 'el': 0, 'covel': 0, 'files': 0}
    )
    overall = {'st': 0, 'covst': 0, 'el': 0, 'covel': 0}

    for f in tree.iter('file'):
        bucket = bucket_for(f.get('name', ''))
        if bucket is None:
            continue
        m = next((c for c in f if c.tag == 'metrics'), None)
        if m is None:
            continue
        st, covst = int(m.get('statements', '0')), int(m.get('coveredstatements', '0'))
        el, covel = int(m.get('elements', '0')), int(m.get('coveredelements', '0'))
        b = buckets[bucket]
        b['st'] += st; b['covst'] += covst; b['el'] += el; b['covel'] += covel
        b['files'] += 1
        overall['st'] += st; overall['covst'] += covst
        overall['el'] += el; overall['covel'] += covel

    def pct(c, t):
        return (c / t * 100) if t else 0.0

    print(f"{'Bucket':28s}  {'Files':>5s}  {'Stmts':>11s}  {'Stmt %':>7s}  {'Elem %':>7s}")
    print('-' * 70)
    for b in sorted(buckets):
        d = buckets[b]
        print(f"{b:28s}  {d['files']:5d}  {d['covst']:5d}/{d['st']:<5d}  "
              f"{pct(d['covst'], d['st']):6.2f}%  {pct(d['covel'], d['el']):6.2f}%")
    print('-' * 70)
    files_total = sum(b['files'] for b in buckets.values())
    print(f"{'OVERALL':28s}  {files_total:5d}  "
          f"{overall['covst']:5d}/{overall['st']:<5d}  "
          f"{pct(overall['covst'], overall['st']):6.2f}%  "
          f"{pct(overall['covel'], overall['el']):6.2f}%")
    return 0


if __name__ == '__main__':
    sys.exit(main(sys.argv[1] if len(sys.argv) > 1 else 'tmp/coverage/report-xml/baseline.xml'))
