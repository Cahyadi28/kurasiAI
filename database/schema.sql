-- Skema database KurasiAI (SQLite).
-- Waktu disimpan sebagai teks format DATE_ATOM, contoh: 2026-10-05T09:15:00+07:00
-- Nilai di CHECK harus sama dengan nilai enum PHP (Role, ContentType, DraftStatus);
-- tests/Integration/SchemaTest.php menjaga keduanya tetap sejalan.

CREATE TABLE users (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    name          TEXT    NOT NULL,
    email         TEXT    NOT NULL UNIQUE,
    role          TEXT    NOT NULL CHECK (role IN ('writer', 'editor')),
    password_hash TEXT    NOT NULL,
    created_at    TEXT    NOT NULL
);

CREATE TABLE prompt_templates (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    name        TEXT    NOT NULL,
    text        TEXT    NOT NULL,
    target_type TEXT    NOT NULL CHECK (target_type IN ('article', 'video_script', 'caption')),
    created_by  INTEGER NOT NULL REFERENCES users (id),
    created_at  TEXT    NOT NULL,
    updated_at  TEXT    NOT NULL
);

CREATE TABLE drafts (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    author_id     INTEGER NOT NULL REFERENCES users (id),
    type          TEXT    NOT NULL CHECK (type IN ('article', 'video_script', 'caption')),
    title         TEXT    NOT NULL,
    body          TEXT    NOT NULL,
    prompt        TEXT    NOT NULL,
    status        TEXT    NOT NULL DEFAULT 'draft'
                  CHECK (status IN ('draft', 'in_review', 'approved', 'rejected', 'published')),
    input_tokens  INTEGER CHECK (input_tokens >= 0),   -- NULL bila draf tidak dibuat oleh AI
    output_tokens INTEGER CHECK (output_tokens >= 0),
    created_at    TEXT    NOT NULL,
    updated_at    TEXT    NOT NULL
);

CREATE INDEX idx_drafts_author ON drafts (author_id);
CREATE INDEX idx_drafts_status ON drafts (status);

CREATE TABLE status_changes (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    draft_id    INTEGER NOT NULL REFERENCES drafts (id) ON DELETE CASCADE,
    from_status TEXT    NOT NULL CHECK (from_status IN ('draft', 'in_review', 'approved', 'rejected', 'published')),
    to_status   TEXT    NOT NULL CHECK (to_status IN ('draft', 'in_review', 'approved', 'rejected', 'published')),
    actor_id    INTEGER NOT NULL REFERENCES users (id),
    note        TEXT,
    changed_at  TEXT    NOT NULL
);

CREATE INDEX idx_status_changes_draft ON status_changes (draft_id);

CREATE TABLE usage_logs (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id       INTEGER NOT NULL REFERENCES users (id),
    draft_id      INTEGER REFERENCES drafts (id) ON DELETE SET NULL,
    provider      TEXT    NOT NULL,
    input_tokens  INTEGER NOT NULL CHECK (input_tokens >= 0),
    output_tokens INTEGER NOT NULL CHECK (output_tokens >= 0),
    created_at    TEXT    NOT NULL
);

CREATE INDEX idx_usage_logs_user ON usage_logs (user_id);
