# Design: dashboard

## Architecture Touchpoints
- Dashboard controller/service
- Chart data adapters
- Cache key strategy

## Key Decisions
- Dashboard reads from report-aligned query contracts
- Cache aggressively for expensive aggregates

## Risks
- Metric drift from report definitions
- Cache invalidation errors

## Validation
- Metric parity tests against report outputs
- Response time checks
