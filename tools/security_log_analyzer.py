#!/usr/bin/env python3
"""
Regex-based security log analyzer for UniShop.

Teaching note:
This script is intentionally simple: it reads PHP JSON-line logs, applies regex
patterns, counts suspicious activity, and reports likely attack categories. In a
production system, this role is usually handled by a SIEM or log platform.
"""

from __future__ import annotations

import argparse
import json
import re
from collections import Counter, defaultdict
from datetime import datetime, timezone
from pathlib import Path
from typing import Any


DEFAULT_LOG_PATH = Path(__file__).resolve().parents[1] / "logs" / "security.log"

DETECTION_PATTERNS = {
    "sql_injection": re.compile(
        r"(\bunion\b|\bselect\b|\binsert\b|\bupdate\b|\bdrop\b|\bdelete\b|--|#|/\*|\*/|;|\bor\b\s+1\s*=\s*1)",
        re.IGNORECASE,
    ),
    "xss_attempt": re.compile(
        r"(<script|</script|javascript:|onerror\s*=|onload\s*=|<iframe|<svg)",
        re.IGNORECASE,
    ),
    "path_traversal": re.compile(
        r"(\.\./|\.\.\\|%2e%2e%2f|%2e%2e\\)",
        re.IGNORECASE,
    ),
    "command_injection": re.compile(
        r"(\|\||&&|`|\$\(|\bcat\s+/etc/passwd\b|\bcurl\s+|\bwget\s+)",
        re.IGNORECASE,
    ),
}


def parse_timestamp(value: str) -> datetime | None:
    try:
        return datetime.fromisoformat(value.replace("Z", "+00:00"))
    except ValueError:
        return None


def flatten_event(event: dict[str, Any]) -> str:
    pieces = [
        str(event.get("event_type", "")),
        str(event.get("ip", "")),
        str(event.get("method", "")),
        str(event.get("path", "")),
        str(event.get("user_agent", "")),
        json.dumps(event.get("context", {}), sort_keys=True),
    ]
    return " ".join(pieces)


def load_events(log_path: Path) -> list[dict[str, Any]]:
    events: list[dict[str, Any]] = []

    if not log_path.exists():
        return events

    for line_number, line in enumerate(log_path.read_text(encoding="utf-8", errors="replace").splitlines(), start=1):
        if not line.strip():
            continue

        try:
            event = json.loads(line)
        except json.JSONDecodeError:
            event = {
                "timestamp": "",
                "event_type": "unparsed_log_line",
                "ip": "unknown",
                "method": "unknown",
                "path": "",
                "user_agent": "",
                "context": {"line_number": line_number, "raw": line[:500]},
            }

        event["_line_number"] = line_number
        events.append(event)

    return events


def classify_events(events: list[dict[str, Any]], brute_force_threshold: int) -> dict[str, Any]:
    pattern_hits: Counter[str] = Counter()
    event_type_counts: Counter[str] = Counter()
    ip_counts: Counter[str] = Counter()
    failed_login_by_ip: defaultdict[str, list[dict[str, Any]]] = defaultdict(list)
    flagged_events: list[dict[str, Any]] = []

    for event in events:
        event_type = str(event.get("event_type", "unknown"))
        ip = str(event.get("ip", "unknown"))
        event_type_counts[event_type] += 1
        ip_counts[ip] += 1

        if event_type == "failed_login_attempt":
            failed_login_by_ip[ip].append(event)

        haystack = flatten_event(event)
        matches = [name for name, pattern in DETECTION_PATTERNS.items() if pattern.search(haystack)]

        for match in matches:
            pattern_hits[match] += 1

        if matches:
            flagged_events.append(
                {
                    "line": event.get("_line_number"),
                    "ip": ip,
                    "event_type": event_type,
                    "matches": matches,
                    "path": event.get("path", ""),
                }
            )

    brute_force_ips = {
        ip: attempts
        for ip, attempts in failed_login_by_ip.items()
        if len(attempts) >= brute_force_threshold
    }

    return {
        "total_events": len(events),
        "event_type_counts": event_type_counts,
        "ip_counts": ip_counts,
        "pattern_hits": pattern_hits,
        "flagged_events": flagged_events,
        "brute_force_ips": brute_force_ips,
    }


def print_report(results: dict[str, Any], log_path: Path, brute_force_threshold: int) -> None:
    print("UniShop Security Log Analysis")
    print("=" * 30)
    print(f"Log file: {log_path}")
    print(f"Analyzed at: {datetime.now(timezone.utc).isoformat()}")
    print(f"Total events: {results['total_events']}")
    print()

    print("Event Types")
    print("-" * 11)
    if results["event_type_counts"]:
        for event_type, count in results["event_type_counts"].most_common():
            print(f"{event_type}: {count}")
    else:
        print("No events found.")
    print()

    print("Regex Detections")
    print("-" * 16)
    if results["pattern_hits"]:
        for pattern_name, count in results["pattern_hits"].most_common():
            print(f"{pattern_name}: {count}")
    else:
        print("No regex attack patterns detected.")
    print()

    print("Bruteforce Attempts")
    print("-" * 19)
    brute_force_ips = results["brute_force_ips"]
    if brute_force_ips:
        for ip, attempts in sorted(brute_force_ips.items(), key=lambda item: len(item[1]), reverse=True):
            first = parse_timestamp(str(attempts[0].get("timestamp", "")))
            last = parse_timestamp(str(attempts[-1].get("timestamp", "")))
            window = "unknown window"
            if first and last:
                window = str(last - first)
            print(f"{ip}: {len(attempts)} failed logins, threshold {brute_force_threshold}, window {window}")
    else:
        print("No IP reached the bruteforce threshold.")
    print()

    print("Flagged Events")
    print("-" * 14)
    flagged_events = results["flagged_events"][:20]
    if flagged_events:
        for event in flagged_events:
            matches = ", ".join(event["matches"])
            print(f"line {event['line']} | {event['ip']} | {event['event_type']} | {matches} | {event['path']}")
    else:
        print("No individual events flagged.")

    if len(results["flagged_events"]) > 20:
        print(f"... {len(results['flagged_events']) - 20} more flagged events omitted.")


def main() -> int:
    parser = argparse.ArgumentParser(description="Analyze UniShop security.log for suspicious patterns.")
    parser.add_argument(
        "--log",
        type=Path,
        default=DEFAULT_LOG_PATH,
        help="Path to security.log. Defaults to logs/security.log.",
    )
    parser.add_argument(
        "--bruteforce-threshold",
        type=int,
        default=5,
        help="Failed login count per IP required to flag bruteforce activity.",
    )
    args = parser.parse_args()

    events = load_events(args.log)
    results = classify_events(events, args.bruteforce_threshold)
    print_report(results, args.log, args.bruteforce_threshold)

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
