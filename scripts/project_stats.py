#!/usr/bin/env python3
from __future__ import annotations

import argparse
import json
import re
from pathlib import Path
from typing import Iterable


EXCLUDE_PARTS = {"vendor", "node_modules", ".git"}


def walk_files(base: Path) -> Iterable[Path]:
    for path in base.rglob("*"):
        if path.is_file() and not any(part in EXCLUDE_PARTS for part in path.parts):
            yield path


def count_lines(paths: Iterable[Path]) -> int:
    total = 0
    for path in paths:
        try:
            with path.open("r", encoding="utf-8", errors="ignore") as file:
                total += sum(1 for _ in file)
        except OSError:
            continue
    return total


def count_regex_in_files(paths: Iterable[Path], pattern: re.Pattern[str]) -> int:
    count = 0
    for path in paths:
        try:
            text = path.read_text(encoding="utf-8", errors="ignore")
        except OSError:
            continue
        count += len(pattern.findall(text))
    return count


def count_languages(language_file: Path) -> int:
    if not language_file.exists():
        return 0

    text = language_file.read_text(encoding="utf-8", errors="ignore")
    match = re.search(r"AVAILABLE\s*=\s*\[(.*?)\];", text, re.S)
    if not match:
        return 0

    return len(re.findall(r"'([a-z]{2})'\s*=>", match.group(1)))


def count_i18n_strings(i18n_file: Path) -> int:
    if not i18n_file.exists():
        return 0

    text = i18n_file.read_text(encoding="utf-8", errors="ignore")
    return len(re.findall(r"^\s*public\s+const\s+[A-Z0-9_]+\s*=", text, re.M))


def collect_metrics(repo_root: Path) -> dict[str, int]:
    html_root = repo_root / "html"
    tests_legacy_root = repo_root / "tests"
    templates_root = repo_root / "templates"

    all_files = list(walk_files(html_root))
    php_files = [path for path in all_files if path.suffix == ".php"]
    js_files = [path for path in all_files if path.suffix == ".js"]
    css_files = [path for path in all_files if path.suffix == ".css"]

    src_php_files = list((html_root / "src").rglob("*.php"))
    domain_files = list((html_root / "src" / "Domain").rglob("*.php"))
    controller_files = list((html_root / "src" / "Controllers").rglob("*.php"))

    class_pattern = re.compile(r"^\s*(?:final\s+|abstract\s+)?class\s+[A-Za-z_][A-Za-z0-9_]*", re.M)
    enum_pattern = re.compile(r"^\s*enum\s+[A-Za-z_][A-Za-z0-9_]*", re.M)
    route_pattern = re.compile(r"#\[\s*Route\s*\(")

    active_test_files = list((html_root / "tests").rglob("*Test.php"))
    legacy_test_files = list(tests_legacy_root.rglob("*Test.php")) if tests_legacy_root.exists() else []

    test_method_pattern = re.compile(r"function\s+test[A-Za-z0-9_]*\s*\(")
    test_attribute_pattern = re.compile(r"#\[\s*Test\s*\]")
    test_methods_estimate = count_regex_in_files(active_test_files, test_method_pattern) + count_regex_in_files(active_test_files, test_attribute_pattern)

    template_files = [
        path
        for path in templates_root.rglob("*.php")
        if ".grok" not in path.parts
    ] if templates_root.exists() else []

    metrics = {
        "app_files_excl_vendor": len(all_files),
        "php_files_excl_vendor": len(php_files),
        "js_files_excl_vendor": len(js_files),
        "css_files_excl_vendor": len(css_files),
        "src_php_files": len(src_php_files),
        "domain_files": len(domain_files),
        "controller_files": len(controller_files),
        "class_count": count_regex_in_files(src_php_files, class_pattern),
        "enum_count": count_regex_in_files(src_php_files, enum_pattern),
        "route_attribute_count": count_regex_in_files(controller_files, route_pattern),
        "language_count": count_languages(html_root / "src" / "Domain" / "Language.php"),
        "i18n_string_count": count_i18n_strings(html_root / "src" / "i18n.php"),
        "template_count": len(template_files),
        "loc_src_php": count_lines(src_php_files),
        "loc_tests_php_active": count_lines((html_root / "tests").rglob("*.php")),
        "loc_js": count_lines(js_files),
        "loc_css": count_lines(css_files),
        "test_files_active": len(active_test_files),
        "test_files_legacy": len(legacy_test_files),
        "test_methods_estimate": test_methods_estimate,
    }

    return metrics


def to_markdown(metrics: dict[str, int]) -> str:
    return "\n".join([
        "| Metric | Value |",
        "|---|---:|",
        f"| App files (excl vendor) | {metrics['app_files_excl_vendor']} |",
        f"| PHP files (excl vendor) | {metrics['php_files_excl_vendor']} |",
        f"| Source PHP files | {metrics['src_php_files']} |",
        f"| Domain files | {metrics['domain_files']} |",
        f"| Controller files | {metrics['controller_files']} |",
        f"| Classes | {metrics['class_count']} |",
        f"| Enums | {metrics['enum_count']} |",
        f"| Route attributes | {metrics['route_attribute_count']} |",
        f"| Supported languages | {metrics['language_count']} |",
        f"| i18n strings | {metrics['i18n_string_count']} |",
        f"| Templates | {metrics['template_count']} |",
        f"| Active test files | {metrics['test_files_active']} |",
        f"| Legacy test files | {metrics['test_files_legacy']} |",
        f"| Estimated active test methods | {metrics['test_methods_estimate']} |",
        f"| Source PHP LOC | {metrics['loc_src_php']} |",
        f"| Active test PHP LOC | {metrics['loc_tests_php_active']} |",
    ])


def main() -> None:
    parser = argparse.ArgumentParser(description="Generate PayCal project statistics")
    parser.add_argument("--format", choices=["kv", "json", "md"], default="kv", help="Output format")
    parser.add_argument("--repo-root", default=".", help="Repository root path")
    args = parser.parse_args()

    repo_root = Path(args.repo_root).resolve()
    metrics = collect_metrics(repo_root)

    if args.format == "json":
        print(json.dumps(metrics, indent=2, sort_keys=True))
        return

    if args.format == "md":
        print(to_markdown(metrics))
        return

    for key, value in metrics.items():
        print(f"{key}={value}")


if __name__ == "__main__":
    main()
