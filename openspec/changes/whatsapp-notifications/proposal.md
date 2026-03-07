# Proposal: whatsapp-notifications

## Summary
Implement WhatsApp payment confirmations and arrears reminders with delivery tracking and retries.

## Problem
Manual parent communication is slow and inconsistent.

## Scope
- Payment success notifications
- Arrears reminder notifications
- Template placeholders
- Delivery status logging and retry handling
- Gateway integration (env-managed secret)

## Out of Scope
- General settings module beyond notification fields
- Non-WhatsApp channels

## Dependencies
- transactions
- obligations-arrears

## Success Criteria
- Notifications send on both trigger types
- Failed sends are visible and retryable
- API key remains env-only
