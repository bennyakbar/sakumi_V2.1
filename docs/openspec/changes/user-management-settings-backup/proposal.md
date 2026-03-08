# Proposal: user-management-settings-backup

## Summary
Implement admin hardening: secure user/role management, operational settings UI, backups, and health diagnostics access controls.

## Problem
Production operations require strong governance controls and recoverability tooling.

## Scope
- User management and role-escalation protections
- Audit log viewer
- Settings UI for non-secret operational configs
- Backup manual trigger/status UI
- Health endpoint access model (`/health/live` public, `/health` protected)

## Out of Scope
- Core finance module behavior changes
- WhatsApp business flow logic

## Dependencies
- foundation-setup

## Success Criteria
- Unauthorized role escalation is blocked
- Settings update works without exposing secrets
- Backup actions and health diagnostics are operable
