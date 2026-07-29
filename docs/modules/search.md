# Search Module

## Scope

Global document search with:

- **FULLTEXT** on MySQL (`title`, `reference_no`, `archive_no`, `description`, `keywords`) with relevance ranking
- **LIKE fallback** on SQLite (tests / non-MySQL)
- Structured filters (approval, status, confidentiality, extension, dates, tags, folder, department, etc.)
- Facet counts for filter UI
- **Saved searches** (private or org-shared)

## Database

| Table | Purpose |
|-------|---------|
| `saved_searches` | Named criteria JSON per user/org; soft deletes; optional `is_shared` |

Uses existing `documents_search_fulltext` index from Document Management.

## API (`/api/v1`, auth:sanctum)

| Action | Endpoint |
|--------|----------|
| Search | `GET /search/documents?organization_id=&q=&…` |
| Facets | `GET /search/facets?organization_id=` |
| List saved | `GET /search/saved` |
| Create saved | `POST /search/saved` |
| Show / update / delete | `GET/PUT/DELETE /search/saved/{id}` |

### Key query params

`q`, `folder_id`, `department_id`, `category_id`, `status`, `approval_status`, `confidentiality_level`, `document_type`, `extension`, `mime_type`, `uploader_id`, `owner_id`, `created_from`, `created_to`, `tags[]`

## Permissions

- `search.view` — run search, manage own saved searches
- `search.saved.manage` — edit/delete others’ saved (incl. shared); org admins
- Also requires `documents.view` to execute search

## Frontend

Route: `/search`

- Keyword box + filter sidebar driven by facets
- Results table with relevance score (MySQL)
- Save / load / delete saved searches
- Jump to Documents library

## Notes

BOOLEAN MODE terms use `+term*` prefixes for partial matching. Very short tokens (&lt; 2 chars) are skipped.
