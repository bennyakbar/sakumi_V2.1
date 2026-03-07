# SAKUMI Architecture Diagrams

This folder contains Mermaid diagram sources for the SAKUMI system.

Files:
01_system_architecture.mmd
02_financial_flow.mmd
03_application_modules.mmd
04_server_architecture.mmd
05_database_er_diagram.mmd

To convert them to SVG or PNG you can use Mermaid CLI:

Install:

npm install -g @mermaid-js/mermaid-cli

Generate SVG:

mmdc -i 01_system_architecture.mmd -o 01_system_architecture.svg

Generate PNG:

mmdc -i 01_system_architecture.mmd -o 01_system_architecture.png

Repeat the command for other files as needed.
