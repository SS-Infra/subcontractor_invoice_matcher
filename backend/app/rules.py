from typing import List
from .config import settings
from .models import InvoiceLine, MatchStatus, RoleType


def expected_rate_for_role(role: RoleType) -> float:
    if role == RoleType.MAIN_OPERATOR:
        return settings.RATE_MAIN_OPERATOR
    if role == RoleType.SECOND_OPERATOR:
        return settings.RATE_SECOND_OPERATOR
    if role == RoleType.YARD:
        return settings.RATE_YARD
    if role == RoleType.TRAVEL_DRIVER:
        return settings.RATE_TRAVEL_DRIVER
    if role == RoleType.TRAVEL_PASSENGER:
        return settings.RATE_TRAVEL_PASSENGER
    return 0.0


def check_rates(line: InvoiceLine, issues: List[str]):
    expected_rate = expected_rate_for_role(line.role)
    if abs(line.rate_per_hour - expected_rate) > 0.01:
        issues.append(f"Rate mismatch: expected {expected_rate}, got {line.rate_per_hour}")


def check_yard_hours(line: InvoiceLine, issues: List[str]):
    if line.hours_yard > 0 and line.hours_yard > settings.YARD_DAY_MAX_HOURS + 0.1:
        issues.append(f"Yard hours exceed max {settings.YARD_DAY_MAX_HOURS}h")


def check_full_shift(line: InvoiceLine, issues: List[str]):
    if line.hours_on_site > 0 and abs(line.hours_on_site - settings.FULL_SHIFT_HOURS) > 0.5:
        issues.append(
            f"On-site hours {line.hours_on_site} differ from standard {settings.FULL_SHIFT_HOURS}h (±0.5h)"
        )


def check_maths(line: InvoiceLine, issues: List[str]):
    calc = (line.hours_on_site + line.hours_travel + line.hours_yard) * line.rate_per_hour
    if abs(calc - line.line_total) > 0.5:
        issues.append(f"Maths error: expected {calc:.2f}, got {line.line_total:.2f}")


def compute_status(issues: List[str], has_jobsheet: bool, has_yard_record: bool) -> MatchStatus:
    if "Missing job sheet" in issues or "No yard sign-in" in issues:
        return MatchStatus.NEEDS_REVIEW
    if issues:
        return MatchStatus.PARTIAL
    if has_jobsheet or has_yard_record:
        return MatchStatus.MATCHED
    return MatchStatus.NEEDS_REVIEW


def apply_rules(line: InvoiceLine, has_jobsheet: bool, has_yard_record: bool) -> None:
    issues: List[str] = []

    if not has_jobsheet and line.hours_on_site > 0:
        issues.append("Missing job sheet")
    if line.hours_yard > 0 and not has_yard_record:
        issues.append("No yard sign-in")

    check_rates(line, issues)
    check_yard_hours(line, issues)
    check_full_shift(line, issues)
    check_maths(line, issues)

    line.match_status = compute_status(issues, has_jobsheet, has_yard_record)
    line.match_notes = "; ".join(issues)
    line.match_score = max(0.0, 1.0 - 0.1 * len(issues))
