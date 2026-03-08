# Design: foundation-setup

## Architecture Touchpoints
- App bootstrap and config
- Auth + RBAC integration
- Base middleware registration
- CI pipeline definition

## Key Decisions
- Use Laravel Breeze for auth baseline
- Use Spatie permission package for RBAC
- Keep middleware minimal and reusable for later modules

## Risks
- Role naming drift across modules
- Environment mismatch between local/staging

## Validation
- Auth smoke tests
- Route authorization smoke tests
- CI pipeline green run
