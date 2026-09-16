# P3 document library and ingestion plan

Status: in progress. Started 16 September 2026.

## Scope

Implement FR4's private upload/list/delete foundation for PDF, DOCX, and TXT files up to 100 MB. Files are owned by one user, stored under a generated private key, and shown with explicit processing state. Queue processing only after the document transaction commits. Initial extraction supports safe UTF-8 TXT files; PDF/DOCX extraction and embeddings are deliberately held behind a provider/extractor choice rather than being treated as complete.

## Security and lifecycle

- Validate declared extension and detected MIME type; do not trust client filenames.
- Store originals only on the non-public `local` disk for now; downloads require an owner-scoped endpoint.
- Statuses: `uploaded`, `extracting`, `ready`, `failed`, `deleting`, `deleted`. Deletion tombstones first and blocks delayed jobs from restoring a document.
- The upload request has a 100 MB application limit. Align PHP/Nginx/proxy limits before external testing.
- No OCR, archive extraction, cloud storage, embeddings, or AI retrieval in this slice.

## Acceptance

- A verified user can upload, list, download, and delete only their own supported files.
- Unsupported files and files over 100 MB are rejected before persistence.
- TXT extraction runs through the queue and transitions a document to ready; failed work has an actionable state.
- Delete removes the private original and prevents queued work from publishing a result.

## Open decisions

Apache Tika is selected as the private PDF/DOCX/TXT extraction service (D16); OCR remains deferred. Student storage is 20 documents/1 GB and Pro storage is 100 documents/10 GB, with 100 MB maximum file size (D17). Benchmark representative PDFs/DOCX files, verify timeout/memory bounds, and seed the resulting plan feature codes before enforcing entitlements.

Local development uses `apache/tika:3.3.1.0` exposed only on loopback port 9998. Start it with `docker compose up -d`; production must place it on a private network and pin an image digest.
