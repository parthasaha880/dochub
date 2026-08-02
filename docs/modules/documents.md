# Document Management Module

## Scope (this delivery)

Nested folders, upload / multi-upload / drag-drop UI, metadata, version control, check-in/out locking, move/copy/rename, recycle bin (soft delete / restore / permanent delete), in-app preview (PDF, images, video/audio, Word docx, text), secure download stream (path never exposed to clients).

## Database

- `folders` — nested via `parent_id`, color/icon/favorite
- `document_categories` — category / subcategory ready
- `documents` — full EDAMS metadata + file pointers + lock fields
- `document_versions` — immutable version history
- `document_tags` + `document_tag` pivot
- MySQL FULLTEXT on title/reference/keywords (skipped on SQLite tests)

## API (`/api/v1`, auth:sanctum)

| Action | Endpoint |
|--------|----------|
| List / CRUD | `GET/POST/PUT/DELETE /documents` |
| Bulk upload | `POST /documents/bulk-upload` |
| Replace version | `POST /documents/{id}/replace` |
| Rename / move / copy | `POST .../rename|move|copy` |
| Check-out / check-in | `POST .../check-out|check-in` |
| Preview (inline) | `GET /documents/{id}/preview` |
| Download | `GET /documents/{id}/download` |
| Trash / restore / force | `GET /documents/trash`, `POST .../restore`, `DELETE .../force` |
| Folder tree / CRUD | `GET /folders/tree`, `POST/PUT/DELETE /folders` |

Public share preview: `GET /public/shares/{token}/preview`

## Permissions

`documents.view`, `documents.upload`, `documents.download`, `documents.manage`, `documents.delete`, `folders.manage`

## Frontend

Route: `/documents`

- Organization selector
- Folder tree + root with rename / lock / unlock / hide / unhide / delete
- Toggle to show hidden folders
- Drag & drop / multi-file upload
- Document list with **View** (eye) + title click → in-app viewer
- Checkout/in, copy, move, download, recycle bin
- Viewer supports PDF, images, video, audio, DOCX, and text; other types offer download

## Security

- Files stored on configured disk under hashed private paths
- Previews/downloads streamed via authenticated API (no raw storage path returned)
- Checked-out documents locked from other users’ edits
- Locked folders block uploads, moves, rename, and delete until unlocked
