# Himam API — what changed, and what the app must do

Everything here changed in the API today. It is written for the mobile client:
each section says what changed, what it means, and what the app has to do about
it. Every payload is captured from a running API, not written from memory.

The full reference is **api.md**; the examination flow in detail is
**quiz-apis.md**. This document is only the delta.

---

## At a glance

| # | Change | App must act |
|---|---|---|
| 1 | Reading is recorded by `POST`, not by fetching | **Yes** |
| 2 | A book may be examined as a whole, not per section | **Yes** |
| 3 | `exam` block says where the exam is | **Yes** |
| 4 | The exam button's text and visibility come from the API | **Yes** |
| 5 | Progress reports reading and passing separately | **Yes** |
| 6 | A failed attempt no longer returns the answers | **Yes** |
| 7 | Both exams answer with the same body | Simplifies |
| 8 | A certificate per book, not only per level | **Yes** |
| 9 | New notification categories: `certificate`, `badge` | Minor |
| 10 | Exam 404s now explain themselves | Minor |
| 11 | Media fields are absolute URLs | Check |
| 12 | A section's document is per language | Check |
| 13 | Deactivating a level withdraws its books | Handle |

---

## 1. Reading is recorded explicitly

**Before:** fetching `GET /api/sections/{id}` marked the section read.
**Now:** it does not. The app says so.

```http
POST /api/sections/{section}/read
POST /api/books/{book}/read
```

```json
{
  "data": {
    "id": 1,
    "read": true,
    "passed": false
  }
}
```

Why: a prefetch, a retry or a cache warm is not a reader, and all three were
being counted as one.

**What to do.** Send the `POST` when the reader actually opens the material.
Use the book form only for a book with no sections — a book that has sections
is tracked by those, and recording both gives two answers to how far they have
got. If the call fails, ignore it; never block reading on it.

---

## 2. A book may be examined as a whole

A book is laid out in sections or as one document, and examined per section or
once over the whole thing. These are now **separate** decisions, so three
shapes exist:

| | Layout | Exam | Finished when |
|---|---|---|---|
| **A** | one document | the book's | its exam is passed |
| **B** | sections | the book's | its exam is passed |
| **C** | sections | one per section | every section is passed |

**B is new** and is what the programme mostly wants: the reader works through
the sections, then sits one exam over the book.

Endpoints:

```http
GET  /api/books/{book}/quiz        POST /api/books/{book}/quiz
GET  /api/sections/{section}/quiz  POST /api/sections/{section}/quiz
```

Only one of the two exists for any given book. The other answers `404`.

---

## 3. `exam` says where the exam is

Do not work this out from section counts. `GET /api/books/{id}` tells you.

A book in sections, examined as a whole (**shape B**):

```json
{
  "scope": "book",
  "button": {
    "visible": true,
    "label": "ابدأ الاختبار"
  },
  "questions_total": 9
}
```

A book examined section by section (**shape C**):

```json
{
  "scope": "sections",
  "button": {
    "visible": false,
    "label": "ابدأ الاختبار"
  },
  "sections_with_quiz": 3
}
```

| `exam.scope` | Call |
|---|---|
| `"book"` | `/api/books/{book}/quiz` |
| `"sections"` | `/api/sections/{section}/quiz`, for each section whose `has_quiz` is true |
| `"none"` | nothing — no questions written yet |

Each section now carries `has_quiz` as well:

```json
{
  "id": 4,
  "book_id": 2,
  "title": "لماذا نقرأ بانتظام",
  "position": 1,
  "questions_count": 3,
  "has_quiz": true,
  "pdf": "http://localhost:8000/storage/uploads/sample-section.pdf",
  "read": false,
  "passed": false
}
```

`questions_count` drops to zero when the book claims the questions for its own
exam, so it reads as "no quiz here" only if you already know that rule.
`has_quiz` says it outright.

**What to do.** Branch on `exam.scope`. In shape B or A, show one exam button
for the book and **no** per-section exam buttons. In shape C, show a button per
section.

---

## 4. The exam button comes from the API

Its text and whether it appears at all are set in the dashboard:

```json
"quiz_button": { "visible": true, "label": "اختبار الكتاب كاملاً" }
```

`visible` is the dashboard's switch **and** whether questions exist — a book
whose exam has not been written reports `false` whatever the switch says.
`label` is already in the requested language.

**What to do.** Render the label the API gives; do not hard-code one. Show the
button only when `visible` is true. This is how a book gets published for
reading weeks before its exam exists.

---

## 5. Progress reports reading and passing separately

`progress` on every book gained two fields:

```json
{
  "sections_total": 3,
  "sections_read": 1,
  "sections_passed": 0,
  "read_percent": 33,
  "percent": 0,
  "completed": false
}
```

| Field | Meaning |
|---|---|
| `sections_read` / `read_percent` | how far through the **reading** |
| `sections_passed` / `percent` | how many section exams **passed** |
| `completed` | the book is finished, by whichever rule applies to it |

In shapes A and B there are no section exams, so `sections_passed` and
`percent` stay at `0` for ever.

**What to do.** Drive the progress bar from `read_percent`, not `percent`, for
any book whose `exam.scope` is `"book"` — otherwise a reader who has read every
page sits at 0%. Use `completed` for "finished", never `percent == 100`.

---

## 6. A failed attempt no longer returns the answers

A failure:

```json
{
  "score": 0,
  "total": 3,
  "passed": false,
  "points_awarded": 0,
  "correct_options": null,
  "incorrect_questions": [
    55,
    56,
    57
  ],
  "new_badges": [],
  "new_certificates": []
}
```

A pass:

```json
{
  "score": 3,
  "total": 3,
  "passed": true,
  "points_awarded": 500,
  "correct_options": {
    "55": 164,
    "56": 167,
    "57": 170
  },
  "incorrect_questions": []
}
```

- **Failed** → `correct_options` is `null`, and `incorrect_questions` lists the
  question ids answered wrongly.
- **Passed** → `correct_options` maps question id to the correct option id.

Why: a resit is free and unlimited, so returning the key after a failure would
make the first attempt a way of being told the answers and the second a
formality.

**What to do.** After a pass, mark right and wrong as before. After a failure,
mark the reader's own answers on the questions in `incorrect_questions` and
mark **nothing** as correct. Reading a missing `correct_options` as "everything
is wrong" would redden a paper the reader got most of right.

---

## 7. Both exams answer with the same body

`POST /api/books/{id}/quiz` used to wrap its result in `data` and `meta` while
the section exam returned a flat body. Both are flat now:

```
score, total, passed, points_awarded,
correct_options, incorrect_questions,
new_badges, new_certificates, stats
```

**What to do.** One result screen serves both. If you wrote a branch for the
two shapes, delete it.

`GET` on either exam returns `data` (the questions) and `meta`, where `meta`
carries `taken`, `attempts`, `passed`, `best_score` and `last_attempt`:

```json
{
  "book": {
    "id": 7,
    "title": "الرسالة في أصول الفقه"
  },
  "questions_total": 3,
  "pass_ratio": 0.6,
  "quiz_button": {
    "visible": true,
    "label": "اختبار الكتاب كاملاً"
  },
  "taken": true,
  "attempts": 2,
  "passed": true,
  "best_score": 3,
  "last_attempt": {
    "score": 0,
    "total": 3,
    "passed": false,
    "created_at": "2026-10-16T18:24:16+00:00"
  }
}
```

**What to do.** Use `taken` to label the button "retake" rather than "start",
and show `attempts` and `best_score` before the reader begins. `passed` stays
true after a later failure — a resit cannot take a pass away.

A retake is recorded but pays nothing:

```json
{
  "score": 3,
  "total": 3,
  "passed": true,
  "points_awarded": 0,
  "new_badges": [],
  "new_certificates": []
}
```

The certificate is re-dated, not reissued: its serial and verification code
stay the same, because they are printed on copies that must keep verifying.

---

## 8. A certificate per book

Certificates used to be per level only. A book now earns one the moment it is
finished. `GET /api/certificates/available` says which is which:

```json
{
  "scope": "book",
  "book": {
    "id": 1,
    "title": "مقدمة في القراءة المنظمة"
  },
  "level": null,
  "title": "مقدمة في القراءة المنظمة",
  "earned": false,
  "serial": null,
  "issued_at": null,
  "verification_url": null,
  "sections_passed": 0,
  "sections_total": 3,
  "percent": 0
}
```

Each row carries `scope`, either `"book"` or `"level"`, plus `percent` towards
it.

**What to do.** Group or label by `scope`. A reader finishing the last book of
a level receives **two** certificates at once, so a screen that assumes one per
event will drop the other.

---

## 9. Two new notification categories

`certificate` and `badge` join `program`, `exam`, `results`, `honor` and
`general`. They arrive in the same feed and count towards `meta.unread`:

```json
[
  {
    "id": 79,
    "category": "badge",
    "title": "حصلت على وسام متمم الكتاب"
  },
  {
    "id": 80,
    "category": "certificate",
    "title": "صدرت شهادتك في «الرسالة في أصول الفقه»"
  },
  {
    "id": 1,
    "category": "program",
    "title": "إطلاق برنامج القراءة المنظمة — الفصل الأول"
  }
]
```

**What to do.** Give the two new categories an icon and a label. They are
mutable like any other through `PUT /api/notification-preferences`, so add them
to the notification settings screen.

---

## 10. Exam 404s explain themselves

Asking for the wrong exam used to answer `404` with an empty message. Now:

```json
{
  "GET /api/books/2/quiz": {
    "message": "هذا الكتاب يُختبر على أقسامه، فليس له اختبار على الكتاب كاملاً."
  },
  "GET /api/sections/1/quiz": {
    "message": "هذا الكتاب يُختبر كاملاً، فليس لأقسامه اختبارات منفصلة."
  }
}
```

**What to do.** Nothing required, but these are worth surfacing while
integrating — an empty 404 is what made this hard to diagnose in the first
place.

---

## 11. Media fields are absolute URLs

`cover`, `image`, `avatar` and `pdf` come back ready to use:

```json
"cover": "https://himam-back.onrender.com/assets/banner.svg"
```

**What to do.** Do not prefix them with a base URL. `null` means nothing was
uploaded.

---

## 12. A section's document is per language

`pdf` on a section, and on a book, is resolved to the requested language before
it is sent — one string, not a map. Send `Accept-Language` (or `?lang=`) and
you get that language's document, falling back when it has none.

---

## 13. Deactivating a level withdraws its books

A level with `is_active = false` now removes its books from the reader-facing
API entirely: they leave `/api/books`, they leave reading programmes, and the
book, its sections and its exams all answer `404`. They also leave both sides
of every total, so `books_total` and `sections_total` count only what the
reader can reach.

**What to do.** Handle a `404` on a book or section the app has cached — it may
have been withdrawn since. Treat it as "no longer available" rather than an
error, and send the reader back to the catalogue.

---

## Suggested order of work

1. **Reading** — switch to `POST .../read` (§1). Without it nothing is recorded.
2. **Exam routing** — branch on `exam.scope` (§2, §3) and render the API's
   button (§4). This is what unblocks the reader stuck after finishing a book.
3. **Result screen** — handle the withheld key (§6) and the single body (§7).
4. **Progress** — read from `read_percent` and `completed` (§5).
5. **Certificates and notifications** — `scope`, and the two new categories
   (§8, §9).
6. **Robustness** — absolute media (§11), withdrawn content (§13).

Steps 1 and 2 are the ones a reader notices immediately. The rest can follow.
