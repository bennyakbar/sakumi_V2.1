# Proposal: foundation-setup

## Summary
Establish the technical foundation for the school finance system so all feature changes can build on a stable baseline.

## Problem
Feature delivery is blocked without a consistent app baseline, auth, RBAC scaffolding, and CI guardrails.

## Scope
- Laravel baseline setup and environment templates
- Authentication scaffolding
- RBAC package integration and role seeding baseline
- Base middleware skeletons (role check, audit hook, inactivity hook)
- CI/test baseline

## Out of Scope
- Master data business flows
- Finance transactions/receipts/reports
- WhatsApp, dashboard, backup UI

## Dependencies
- None

## Success Criteria
- Application boots in local/staging configuration
- Login/logout works
- Roles exist and can gate routes
- CI runs tests and fails on regression
