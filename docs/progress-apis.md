# Himam — progress, badges, the honour board and certificates

What the API reports about how far a reader has got, and what they have earned
for it. Every payload below was captured from a running API against a reader
with real history; none of it is written from memory.

The complete interface is **api.md**; examination is **quiz-apis.md**.

---

## Before anything else: two rules that shape every figure here

**Sections are read, not passed.** A section records one thing: that it was
opened. There is no section exam, so nothing passes a section.

**A book is finished by passing its exam**, which opens once every section has
been read. The field that answers "is this book done" is `completed` — never
`read_percent == 100`, which only means the reading is finished and the exam
is now available.

---

## الرئيسية — `GET /api/dashboard`

The home screen: the headline counters, the next badges to aim at, and the
newest announcements.

**Note the shape.** This endpoint and `/api/progress` answer with their fields
at the top level, *not* wrapped in `data` the way most of the API is.

```json
{
  "stats": {
    "points": 1800,
    "sections_read": 12,
    "sections_total": 18,
    "books_completed": 4,
    "books_total": 6,
    "badges_earned": 5,
    "certificates": 6
  },
  "unread_announcements": 16,
  "badges": [
    {
      "id": 1,
      "name": "المثابر",
      "description": "أنهيت أول قسم من أقسام البرنامج.",
      "image": "http://localhost:8000/assets/badge.png",
      "criteria_type": "sections_read",
      "criteria_value": 1,
      "position": 0,
      "earned": true,
      "awarded_at": "2026-10-17 09:37:01"
    },
    {
      "id": 2,
      "name": "القارئ المنتظم",
      "description": "أنهيت خمسة أقسام بنجاح.",
      "image": "http://localhost:8000/assets/badge.png",
      "criteria_type": "sections_read",
      "criteria_value": 5,
      "position": 1,
      "earned": true,
      "awarded_at": "2026-10-17 09:37:01"
    },
    {
      "id": 3,
      "name": "متمم الكتاب",
      "description": "أتممت كتاباً كاملاً بجميع أقسامه.",
      "image": "http://localhost:8000/assets/badge.png",
      "criteria_type": "books_completed",
      "criteria_value": 1,
      "position": 2,
      "earned": true,
      "awarded_at": "2026-10-17 09:37:01"
    },
    {
      "id": 4,
      "name": "قارئ المستوى",
      "description": "أتممت كتابين كاملين.",
      "image": "http://localhost:8000/assets/badge.png",
      "criteria_type": "books_completed",
      "criteria_value": 2,
      "position": 3,
  …
```

---

## تقدمي — `GET /api/progress`

The whole picture: counters, every active level with its books and sections,
the recent attempts and the points earned per month.

```json
{
  "points": 1800,
  "sections_read": 12,
  "sections_total": 18,
  "books_completed": 4,
  "books_total": 6,
  "badges_earned": 5,
  "certificates": 6
}
```

### What the counters mean

| Field | Meaning |
|---|---|
| `points` | lifetime points |
| `sections_read` / `sections_total` | sections opened, out of the sections a reader can reach |
| `books_completed` / `books_total` | books finished, out of the books a reader can reach |
| `badges_earned` | badges held |
| `certificates` | certificates held, book and level together |

**"Can reach" is the important part.** Both totals count only published books on
an **active** level. Deactivating a level withdraws its books from the
programme entirely — they leave the catalogue and they leave both sides of
these ratios, so the figure always describes what is in front of the reader.

### One level, with its books

```json
{
  "id": 1,
  "name": "المستوى الأول",
  "books_total": 2,
  "books_completed": 2,
  "sections_total": 6,
  "sections_read": 6,
  "read_percent": 100,
  "certificate": {
    "id": 3,
    "serial": "CERT-2026-RZE4AR",
    "issued_at": "2026-10-17"
  },
  "books": [
    {
      "id": 1,
      "title": "مقدمة في القراءة المنظمة",
      "points": 400,
      "pages": 140,
      "has_own_quiz": true,
      "sections_total": 3,
      "sections_read": 3,
      "read_percent": 100,
      "completed": true,
      "sections": [
        {
          "id": 1,
          "title": "لماذا نقرأ بانتظام",
          "position": 1,
          "read": true,
          "read_at": "2026-10-17"
        },
        {
          "id": 2,
          "title": "بناء خطة القراءة",
          "position": 2,
          "read": true,
          "read_at": "2026-10-17"
        },
        {
          "id": 3,
          "title": "المراجعة والتلخيص",
          "position": 3,
          "read": true,
          "read_at": "2026-10-17"
        }
      ]
    },
    {
      "id": 2,
      "title": "أصول التفكير المنهجي",
      "points": 400,
      "pages": 165,
      "has_own_quiz": true,
      "sections_total": 3,
      "sections_read": 3,
      "read_percent": 100,
      "completed": true,
      "sections": [
        {
          "id": 4,
          "title": "لماذا نقرأ بانتظام",
          "position": 1,
          "read": true,
          "read_at": "2026-10-17"
        },
        {
          "id": 5,
          "title": "بناء خطة القراءة",
          "position": 2,
          "read": true,
  …
```

Each book carries both measures side by side:

| Field | Meaning |
|---|---|
| `sections_read` / `read_percent` | how far through the **reading** |
| `completed` | the book's exam has been passed |

Drive a progress bar from `read_percent`, and use `completed` for "finished".
Reaching 100% opens the exam; passing it is what makes `completed` true.

### المحاولات الأخيرة و النقاط الشهرية

`recent_attempts` lists the last ten graded attempts. `monthly_points` gives twelve entries, one per month including the empty
ones, so a chart shows real gaps rather than silently compressing inactive
months.

```json
[
  {
    "month": "2025-11",
    "label": "نوفمبر",
    "points": 0
  },
  {
    "month": "2025-12",
    "label": "ديسمبر",
    "points": 0
  },
  {
    "month": "2026-01",
    "label": "يناير",
    "points": 0
  },
  {
    "month": "2026-02",
    "label": "فبراير",
    "points": 0
  },
  {
    "month": "2026-03",
    "label": "مارس",
    "points": 0
  },
  {
    "month": "2026-04",
    "label": "أبريل",
    "points": 0
  },
  {
  …
```

---

## الأوسمة — `GET /api/badges`

Every active badge, and whether this reader holds it.

```json
{
  "id": 1,
  "name": "المثابر",
  "description": "أنهيت أول قسم من أقسام البرنامج.",
  "image": "http://localhost:8000/assets/badge.png",
  "criteria_type": "sections_read",
  "criteria_value": 1,
  "position": 0,
  "earned": true
}
```

`criteria_type` is `sections_read`, `books_completed`, `points` or `manual`.
The first three are awarded automatically when an exam is graded; a `manual`
one is given by an administrator.

Badges are **never revoked**. Once earned they stay, even if the work behind
them is later withdrawn with its level.

Public without a token — the badge list is part of the pitch — but `earned_at`
only appears for a signed-in reader:

```json
{
  "id": 1,
  "name": "المثابر",
  "description": "أنهيت أول قسم من أقسام البرنامج.",
  "image": "http://localhost:8000/assets/badge.png",
  "criteria_type": "sections_read",
  "criteria_value": 1,
  "position": 0,
  "earned": false
}
```

`next_badges` on `/api/dashboard` and `/api/progress` reports the three the
reader is closest to, with `current`, `remaining` and `percent`, so a screen
can show something to aim at rather than only what is already done.

---

## لوحة الشرف — `GET /api/honor-board`

Readers ranked by points.

```json
[
  {
    "rank": 1,
    "id": 2,
    "name": "عبدالله سالم",
    "avatar": "http://localhost:8000/assets/avatar-1.svg",
    "level": "المستوى الثالث",
    "books": 4,
    "points": 1800
  },
  {
    "rank": 2,
    "id": 3,
    "name": "محمد احمد",
    "avatar": "http://localhost:8000/assets/avatar-2.svg",
    "level": "المستوى الثاني",
    "books": 3,
    "points": 1300
  },
  {
    "rank": 3,
    "id": 4,
    "name": "خالد يوسف",
    "avatar": "http://localhost:8000/assets/avatar-3.svg",
    "level": "المستوى الثاني",
    "books": 3,
    "points": 1300
  },
  {
    "rank": 4,
    "id": 5,
    "name": "سعود ناصر",
    "avatar": "http://localhost:8000/assets/avatar-1.svg",
    "level": "المستوى الأول",
    "books": 2,
    "points": 800
  },
  {
    "rank": 5,
    "id": 6,
    "name": "فهد عبدالعزيز",
    "avatar": "http://localhost:8000/assets/avatar-2.svg",
    "level": "المستوى الأول",
    "books": 2,
    "points": 800
  }
]
```

`scope` takes `all`, `month` or `year`; `limit` defaults to 20 and caps at 100.

The scoped boards **sum the attempts in that window** rather than repeating the
lifetime total, so a monthly board really is monthly:

```json
[
  {
    "rank": 1,
    "id": 2,
    "name": "عبدالله سالم",
    "avatar": "http://localhost:8000/assets/avatar-1.svg",
    "level": "المستوى الثالث",
    "books": 4,
    "points": 1800
  },
  {
    "rank": 2,
    "id": 3,
    "name": "محمد احمد",
    "avatar": "http://localhost:8000/assets/avatar-2.svg",
    "level": "المستوى الثاني",
    "books": 3,
    "points": 1300
  },
  {
    "rank": 3,
    "id": 4,
    "name": "خالد يوسف",
    "avatar": "http://localhost:8000/assets/avatar-3.svg",
  …
```

`books` on each row counts books that reader has finished, by the same rule as
everywhere else. Public, with or without a token.

---

## الشهادات، الحاصل عليها — `GET /api/certificates`

What the reader holds.

```json
[
  {
    "id": 6,
    "serial": "CERT-2026-INTT26",
    "title": "المستوى الثاني",
    "issued_at": "2026-10-17",
    "level": {
      "id": 2,
      "name": "المستوى الثاني",
      "description": "التوسع في أمهات الكتب وبناء الوعي المعرفي.",
      "position": 2
    },
    "book": null,
    "verification_url": "http://localhost/api/certificates/verify/cc01e562-7b0d-435e-bad8-f4b65939440b"
  },
  {
    "id": 5,
    "serial": "CERT-2026-HWIQIA",
    "title": "الوعي المعرفي",
    "issued_at": "2026-10-17",
    "level": null,
    "book": {
      "id": 4,
      "title": "الوعي المعرفي",
      "author": "إعداد إدارة البرنامج",
      "description": "يبني عند القارئ وعياً بما يقرأ: كيف يميّز الرأي من الدليل، والمحكم من المتشابه.",
      "cover": "http://localhost:8000/assets/banner.svg",
      "pdf": null,
      "has_own_quiz": true,
      "quiz_button": {
        "visible": false,
        "label": "ابدأ الاختبار",
  …
```

A certificate carries **either** a `book` or a `level`:

- **book** — issued the moment that book is finished
- **level** — issued when every book on that level is finished

A reader finishing the last book of a level earns **both at once**, so a screen
that assumes one certificate per event will drop the other.

`GET /api/certificates/{id}` returns one, with the holder attached.

### Retakes do not reissue

Sitting a passed exam again **re-dates** the certificate. Its `serial` and
verification code stay the same, because they are printed on copies held by
people who are not readers of this app — a reissue would quietly stop every one
of those verifying.

---

## الشهادات، المتاحة — `GET /api/certificates/available`

Everything issuable, held or not, so a screen can show what is still ahead.

```json
[
  {
    "scope": "book",
    "book": {
      "id": 1,
      "title": "مقدمة في القراءة المنظمة"
    },
    "level": null,
    "title": "مقدمة في القراءة المنظمة",
    "earned": true,
    "serial": "CERT-2026-FX4ZQ0",
    "issued_at": "2026-10-17",
    "verification_url": "http://localhost/api/certificates/verify/2691db78-2b1d-4024-a2f2-6df9105550f8",
    "sections_read": 3,
    "sections_total": 3,
    "percent": 100
  },
  {
    "scope": "book",
    "book": {
      "id": 2,
      "title": "أصول التفكير المنهجي"
    },
    "level": null,
    "title": "أصول التفكير المنهجي",
    "earned": true,
    "serial": "CERT-2026-A5QJGE",
    "issued_at": "2026-10-17",
    "verification_url": "http://localhost/api/certificates/verify/ba47d2f9-da75-467e-b770-00cb1bafaed5",
    "sections_read": 3,
    "sections_total": 3,
    "percent": 100
  },
  {
    "scope": "book",
    "book": {
      "id": 3,
      "title": "أمهات الكتب: دليل القارئ"
    },
    "level": null,
    "title": "أمهات الكتب: دليل القارئ",
    "earned": true,
    "serial": "CERT-2026-PVOEKL",
    "issued_at": "2026-10-17",
  …
```

| Field | Meaning |
|---|---|
| `scope` | `book` or `level` |
| `earned` | whether it is held |
| `serial`, `issued_at`, `verification_url` | present only when earned |
| `sections_read` / `sections_total` | progress towards it |
| `percent` | the same, as a percentage |

A book that is one document has no sections to count, so it is one step: opened or not.

---

## التحقق — `GET /api/certificates/verify/{code}`

Public, and behind the QR code printed on every certificate.

```json
{
  "valid": true,
  "serial": "CERT-2026-INTT26",
  "holder": "عبدالله سالم",
  "title": "المستوى الثاني",
  "level": "المستوى الثاني",
  "issued_at": "2026-10-17"
}
```

No token, by design: the person checking a certificate is usually not a reader
of this app.

---

## Passing a language

Every translatable field is resolved to one string before it leaves the API.
The language is chosen from, in order: `?lang=ar`, `X-Locale: ar`,
`Accept-Language: ar`, the reader's saved language, then the default set in the
dashboard. A field with no translation in the requested language falls back
rather than coming back empty.

```http
GET /api/progress?lang=en
Accept: application/json
Authorization: Bearer <token>
```

---

## Two things worth knowing

**Counters move on their own.** Points, badges and certificates are all decided
when an exam is graded, and the grading response already carries what changed —
`points_awarded`, `new_badges`, `new_certificates` and a recalculated `stats`.
A client does not need to re-fetch this document's endpoints to find out what a
pass produced.

**Nothing earned is taken back.** Badges are never revoked and certificates are
never withdrawn. What can move is progress *towards* something unearned: if an
administrator deactivates a level, the work done on it leaves the ratios along
with the books, because those books are no longer part of the programme.
