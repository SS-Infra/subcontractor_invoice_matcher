import os
import logging
from functools import lru_cache
from typing import Optional, Tuple, Dict, Any

import openrouteservice

logger = logging.getLogger(__name__)

# Stock Sweepers depot:
# Stock Sweepers, Innovation House, Speculation Road, Forest Vale Industrial Estate, GL14 2YD
# Lat / lon from postcode GL14 2YD
# Source: public postcode data (Doogal) – 51.833546, -2.511965
DEPOT_LON = -2.511965
DEPOT_LAT = 51.833546
DEPOT_COORDS = (DEPOT_LON, DEPOT_LAT)  # (lon, lat)


@lru_cache(maxsize=1)
def _get_ors_client() -> openrouteservice.Client:
    """
    Lazily create and cache an OpenRouteService client.

    Requires the environment variable OPENROUTESERVICE_API_KEY to be set.
    """
    api_key = os.getenv("OPENROUTESERVICE_API_KEY")
    if not api_key:
        raise RuntimeError(
            "OPENROUTESERVICE_API_KEY is not set. "
            "Get a key from openrouteservice.org and set it on the backend container."
        )
    return openrouteservice.Client(key=api_key)


def estimate_travel_hours_to_postcode(
    postcode: str,
    country: str = "UK",
) -> Tuple[Optional[float], str]:
    """
    Estimate one-way driving time in hours from the depot to the given postcode.

    Returns:
        (hours, debug_message)
        hours will be None if we couldn't get a route.
    """
    query_text = f"{postcode}, {country}"

    try:
        client = _get_ors_client()
    except RuntimeError as e:
        # Config problem – don't crash the app, just report it.
        logger.warning("Travel estimator misconfigured: %s", e)
        return None, str(e)

    try:
        # 1) Geocode destination
        geocode = client.pelias_search(text=query_text, size=1)
        features = geocode.get("features") or []
        if not features:
            msg = f"No coordinates found for '{query_text}'"
            logger.info(msg)
            return None, msg

        dest_feature = features[0]
        dest_lon, dest_lat = dest_feature["geometry"]["coordinates"]

        # 2) Get driving route
        route = client.directions(
            coordinates=[DEPOT_COORDS, (dest_lon, dest_lat)],
            profile="driving-car",
            format="json",
        )

        seconds = route["routes"][0]["summary"]["duration"]
        hours = seconds / 3600.0

        debug = (
            f"ORS driving-car route from depot ({DEPOT_LAT:.6f},{DEPOT_LON:.6f}) "
            f"to dest ({dest_lat:.6f},{dest_lon:.6f}) – {seconds:.0f}s "
            f"≈ {hours:.2f} hours"
        )
        return hours, debug

    except Exception as exc:  # noqa: BLE001 – we want to catch ORS errors generically
        logger.exception("Error estimating travel time to %s", postcode)
        return None, f"openrouteservice error: {exc!r}"


def check_travel_time_claim(
    destination_postcode: str,
    claimed_travel_hours: float,
    tolerance_hours: float = 1.0,
    country: str = "UK",
) -> Dict[str, Any]:
    """
    Compare a claimed travel time against the ORS estimate.

    Args:
        destination_postcode: e.g. 'BS1 4DJ'
        claimed_travel_hours: what the operator says they travelled (one way or total – up to your convention)
        tolerance_hours: how far off (±) we're happy with before flagging
        country: left flexible, but realistically 'UK' for you

    Returns:
        {
          "ok": bool,              # within tolerance?
          "estimated_hours": float | None,
          "claimed_hours": float,
          "delta_hours": float | None,
          "tolerance_hours": float,
          "debug": str,
        }
    """
    est_hours, debug = estimate_travel_hours_to_postcode(
        postcode=destination_postcode,
        country=country,
    )

    if est_hours is None:
        # Couldn't estimate – don't fail hard, just report that we couldn't check it.
        return {
            "ok": True,  # we don't know, so don't auto-fail
            "estimated_hours": None,
            "claimed_hours": claimed_travel_hours,
            "delta_hours": None,
            "tolerance_hours": tolerance_hours,
            "debug": f"Could not estimate travel time: {debug}",
        }

    delta = claimed_travel_hours - est_hours
    ok = abs(delta) <= tolerance_hours

    return {
        "ok": ok,
        "estimated_hours": est_hours,
        "claimed_hours": claimed_travel_hours,
        "delta_hours": delta,
        "tolerance_hours": tolerance_hours,
        "debug": debug,
    }
