---
name: english-code-only
description: Enforce English-only technical writing, sensible code commenting, SOLID-oriented structure, database conventions, and CRUD page separation across software artifacts. Use when creating or refactoring code, tests, migrations, routes, and documentation to ensure identifiers, comments, and developer-facing text are in English, meaningful logic is documented, design follows SOLID where practical, schema standards (UUID primary keys, soft deletes, audit columns) are preserved, and create/edit views are implemented as dedicated pages.
---

# English Code Only

Use this skill to keep technical artifacts consistent and searchable across a codebase.

## Workflow

1. Detect non-English text in files being edited.
2. Translate technical text to clear English.
3. Rename symbols and references safely.
4. Validate behavior and tests after refactoring.

## Keep In English

- Identifiers: variables, methods, classes, interfaces, enums, constants.
- Comments: inline, block, docblocks, TODO notes.
- Developer docs: READMEs, ADRs, architecture notes, changelog entries.
- Commit and PR text when generated as part of the task.
- Test names and test descriptions.
- Database artifacts: migration names, table/column names, enum values (unless business data requires localized values).

## Commenting Standard

- Comment every sensible piece of code that is not immediately obvious from names and structure.
- Explain intent, business rule, edge case, or non-trivial tradeoff; avoid comments that only restate syntax.
- Keep comments short and precise, and keep them in English.
- Add docblocks for public APIs and complex functions where parameters, return values, or side effects need clarification.

## Design Principles

- Prefer SOLID principles as much as practical for the current scope.
- Keep responsibilities focused (Single Responsibility) and split classes/modules when one unit serves unrelated concerns.
- Favor extension over risky modification in stable code paths (Open/Closed).
- Preserve substitutability when introducing abstractions and inheritance (Liskov).
- Keep interfaces narrow and task-specific (Interface Segregation).
- Depend on abstractions at boundaries to reduce coupling (Dependency Inversion).

## Data Model Conventions

- Use UUID as the primary key for all new tables.
- Support soft deletes for all entities unless a documented exception exists.
- Include audit fields on every table: `created_by`, `updated_by`, `created_at`, `updated_at`.
- Keep migration, model, and relationship code aligned with these conventions.

## CRUD Page Structure

- Implement dedicated pages for create and edit flows for each entity (for example: users, reservations, clients).
- Do not embed full create/edit forms directly into the index/listing page.
- Use explicit routes such as `resource/create` and `resource/edit/{id}` (or framework-equivalent named routes with the same separation).
- Keep navigation links discoverable from the listing page to those dedicated create/edit pages.

## Date Format Standard

- Format all document-facing dates as `DD.MM.YYYY`.
- Apply this format consistently in invoices, contracts, generated PDFs, and date placeholders rendered into documents.
- Do not mix ISO (`YYYY-MM-DD`) and local display date formats inside the same document output.

## Refactor Rules

- Preserve behavior; change naming only when semantics stay the same.
- Rename in dependency-safe order:
  1. Symbols
  2. Imports/usages
  3. Routes/config keys
  4. Tests and fixtures
- Keep user-facing product language separate through translation files if localization is required.
- Avoid mixed-language identifiers in the same module.

## Translation Style

- Prefer concise domain terms used in software engineering.
- Use consistent vocabulary across modules.
- Expand ambiguous abbreviations when refactoring improves clarity.
- Do not transliterate source language into English-like spelling; use proper English terms.

## Validation Checklist

- Run lint and tests for touched files.
- Confirm no stale references remain (search for old identifiers).
- Confirm generated docs/comments are English-only.
- Confirm sensible non-obvious logic is commented.
- Confirm changed design does not violate SOLID without a clear tradeoff reason.
- Confirm new or changed tables use UUID primary keys.
- Confirm new or changed entities support soft deletes.
- Confirm new or changed tables include `created_by`, `updated_by`, `created_at`, and `updated_at`.
- Confirm each managed entity has separate create and edit pages.
- Confirm create/edit flows are not implemented inline in index/list pages.
- Confirm routes follow dedicated patterns like `resource/create` and `resource/edit/{id}`.
- Confirm all invoice and contract dates are rendered in `DD.MM.YYYY` format.
