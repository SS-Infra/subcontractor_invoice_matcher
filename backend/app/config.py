from pydantic_settings import BaseSettings


class Settings(BaseSettings):
    # Database connection string
    # NOTE: docker-compose also sets DATABASE_URL env to this value.
    DATABASE_URL: str = "sqlite+aiosqlite:///./data/db.sqlite3"

    # Jotform
    JOTFORM_API_KEY: str = "changeme"
    JOTFORM_FORM_ID: str = "changeme"

    # Travel settings (for future use if you hook in Google Maps etc.)
    GOOGLE_MAPS_API_KEY: str | None = None
    TRAVEL_EXTRA_MIN_PER_TRIP: int = 15

    # Rates – default rates; operator-specific overrides are stored in DB
    RATE_MAIN_OPERATOR: float = 25.0
    RATE_SECOND_OPERATOR: float = 18.0
    RATE_YARD: float = 17.0
    RATE_TRAVEL_DRIVER: float = 17.0
    RATE_TRAVEL_PASSENGER: float = 13.0
    YARD_DAY_MAX_HOURS: float = 9.0
    FULL_SHIFT_HOURS: float = 8.5

    class Config:
        # Load overrides from a .env file if present
        env_file = ".env"


settings = Settings()
