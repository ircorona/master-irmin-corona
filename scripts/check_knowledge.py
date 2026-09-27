"""Consistency gate for knowledge/ (class notes).

Checks:
  1. Every relative Markdown link resolves to an existing file or folder
     (in the working tree, or on the main branch for repo files).
  2. Every module folder is linked from knowledge/README.md.
  3. In modules with a 00-indice.md, every NN-*.md note is linked from it.
  4. Every class note ends with the three closing sections (warning only).

Usage: python scripts/check_knowledge.py [module]   (exit 1 on errors)
"""
import re
import subprocess
import sys
from pathlib import Path
from urllib.parse import unquote

ROOT = Path(__file__).resolve().parent.parent / "knowledge"
LINK = re.compile(r"\[[^\]]*\]\(([^)\s]+)(?:\s+\"[^\"]*\")?\)")
CODE = re.compile(r"```.*?```|`[^`\n]*`", re.S)
NOTE = re.compile(r"^\d\d-.+\.md$")
CLOSING = ["La gran lecci", "Conexi", "Referencias"]


def links(md: Path):
    text = CODE.sub("", md.read_text(encoding="utf-8"))
    for target in LINK.findall(text):
        # skip URLs, anchors and root-absolute site paths (/registro-marca)
        if re.match(r"^[a-z]+:|^#|^/", target, re.I):
            continue
        yield unquote(target.split("#")[0])


def main_branch_files():
    """Repo files on main: notes link to exercises that only live there."""
    try:
        out = subprocess.run(["git", "ls-tree", "-r", "--name-only", "main"],
                             cwd=ROOT.parent, capture_output=True, text=True, check=True)
        return set(out.stdout.splitlines())
    except (OSError, subprocess.CalledProcessError):
        return set()


def resolves(md: Path, target: str, on_main: set) -> bool:
    path = (md.parent / target).resolve()
    if path.exists():
        return True
    try:
        return path.relative_to(ROOT.parent).as_posix() in on_main
    except ValueError:
        return False


def main():
    only = sys.argv[1] if len(sys.argv) > 1 else None
    errors, warnings = [], []
    modules = sorted(p for p in ROOT.iterdir() if p.is_dir())
    if only:
        modules = [m for m in modules if m.name == only]
        if not modules:
            sys.exit(f"module not found: {only}")

    readme = ROOT / "README.md"
    readme_targets = {t.rstrip("/") for t in links(readme)}
    files = [readme] if not only else []
    for mod in modules:
        if not only and mod.name not in readme_targets:
            errors.append(f"README.md: module {mod.name}/ not linked")
        files += sorted(mod.rglob("*.md"))

    on_main = main_branch_files()
    for md in files:
        for target in links(md):
            if target and not resolves(md, target, on_main):
                errors.append(f"{md.relative_to(ROOT)}: broken link -> {target}")

    for mod in modules:
        notes = [p for p in mod.glob("*.md") if NOTE.match(p.name) and not p.name.startswith("00-")]
        index = mod / "00-indice.md"
        if index.exists():
            linked = {Path(t).name for t in links(index)}
            for n in notes:
                if n.name not in linked:
                    errors.append(f"{mod.name}/00-indice.md: {n.name} not linked")
        for n in notes:
            if "bibliograf" in n.name:
                continue
            body = n.read_text(encoding="utf-8")
            missing = [c for c in CLOSING if c not in body]
            if missing:
                warnings.append(f"{mod.name}/{n.name}: missing closing section(s) {missing}")

    for w in warnings:
        print("WARN ", w)
    for e in errors:
        print("ERROR", e)
    print(f"\nchecked {len(files)} files in {len(modules)} module(s): "
          f"{len(errors)} errors, {len(warnings)} warnings")
    sys.exit(1 if errors else 0)


if __name__ == "__main__":
    main()
