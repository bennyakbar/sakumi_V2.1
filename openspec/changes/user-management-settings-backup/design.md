# Design: user-management-settings-backup

## Architecture Touchpoints
- Admin controllers/policies/middleware
- Settings repository and cache behavior
- Backup command integration and status readouts
- Health endpoint authorization

## Key Decisions
- Secrets remain env-only; UI edits non-secret settings only
- Role-sensitive actions audited and restricted to super admin
- Separate public liveness vs protected readiness endpoints

## Risks
- Privilege escalation through overlooked endpoint
- Misconfigured backup storage/retention

## Validation
- Authorization tests for role/user actions
- Settings and backup workflow tests
- Health endpoint access tests
