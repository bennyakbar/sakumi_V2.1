You are a senior software architect and documentation specialist.

Your task is to analyze the SAKUMI Laravel application and generate
complete operational documentation.

IMPORTANT:

Use AI_INDEX.md to identify the source of truth.

Only analyze files referenced in AI_INDEX.md.

Do NOT scan the entire repository.

Ignore:

vendor/
node_modules/
storage/

--------------------------------

DOCUMENTATION TO GENERATE

1. SYSTEM OVERVIEW

Explain:

- purpose of SAKUMI
- core modules
- system architecture
- financial workflow

--------------------------------

2. BUSINESS PROCESS FLOW

Explain the lifecycle:

Student
→ Invoice
→ Payment
→ Settlement
→ Receipt
→ Reports

--------------------------------

3. USER ROLE GUIDE

Describe responsibilities of:

Admin TU
Bendahara
Kepala Sekolah
Yayasan
Auditor

--------------------------------

4. SOP OPERASIONAL

Create SOP for:

Create Invoice
Receive Payment
Settlement Processing
Print Receipt
Check Arrears
Generate Reports

--------------------------------

5. JUKNIS SISTEM

Explain how to operate the system:

Menu navigation
Step-by-step instructions
Button references

--------------------------------

6. JUKLAK IMPLEMENTASI

Explain operational policies:

role permissions
financial validation
audit rules

--------------------------------

7. DATA FLOW

Explain relationships:

Student
Invoice
Payment
Settlement
Receipt
Reports

--------------------------------

8. SYSTEM DIAGRAMS

Generate Mermaid diagrams:

Invoice flow
Payment flow
Settlement flow
Reporting flow

--------------------------------

9. INTERNAL CONTROL

Explain safeguards such as:

duplicate payment prevention
invoice locking
audit trail

--------------------------------

10. IMPLEMENTATION GUIDE

Explain how schools implement SAKUMI:

training
data migration
system rollout

--------------------------------

OUTPUT

Generate files in:

docs/generated/

Files:

SAKUMI_SYSTEM_OVERVIEW.md
SAKUMI_BUSINESS_PROCESS.md
SAKUMI_SOP_OPERASIONAL.md
SAKUMI_JUKNIS_USER.md
SAKUMI_JUKLAK_IMPLEMENTASI.md
SAKUMI_DATA_FLOW.md
SAKUMI_AUDIT_CONTROL.md
