# Design: receipts

## Architecture Touchpoints
- Receipt service and template
- Queue/after-commit job dispatch
- Storage path + retrieval

## Key Decisions
- Generate receipt after DB commit
- Keep receipt path mutable as system-managed artifact

## Risks
- PDF rendering failures
- Storage permission misconfiguration

## Validation
- Receipt generation tests
- Reprint tests
- Cancellation watermark tests
