# Himam — the exam, end to end

How a reader is examined: what opens the exam, what the API asks for at each
step, and what it answers. Every payload below was captured from a running API
against an account created for the purpose.

The complete interface is **api.md**. What the mobile client has to build is
**mobile-changes.md**, which covers the same ground more briefly.

---

## The rule, in one paragraph

A book is **read in sections** and **examined once, over the whole of it**.
Sections are the path through the reading; they carry no exam and nothing
passes them. When every section has been read, the book's exam opens. Passing
it finishes the book, and awards the points, any badges, and the book's
certificate.

A book may instead be one document with no sections. Then opening the book is
the reading, and the same exam rule applies.

---

## 1. The one flag

`GET /api/books/{book}` carries `quiz_button`. It is the only thing to consult
when deciding whether to offer the exam.

```json
{
  "visible": false,
  "label": "ابدأ الاختبار",
  "reason": "unread"
}
```

| `reason` | Meaning | What to show |
|---|---|---|
| `null` | the exam is open | the button, using `label` |
| `unread` | the reading is not finished | no button; "finish the book first" |
| `perfect` | already scored full marks | no button — there is nothing to gain |
| `no_questions` | the exam has not been written | no button |
| `hidden` | an editor turned it off | no button |
| `not_signed_in` | nobody to examine | the sign-in prompt |

`label` arrives already in the requested language. Render it; do not hard-code
one — **it changes**. A reader who has passed but not perfectly is offered a
better mark rather than the exam, and the label says so:

```json
{ "visible": true, "label": "تحسين نتيجة الاختبار", "reason": null }
```

And once they score full marks it goes entirely:

```json
{ "visible": false, "label": "ابدأ الاختبار", "reason": "perfect" }
```

When `visible` is false, ignore `label`.

### It opens as the reading finishes

The same book, after each section is marked read:

```
0 of 3 read   visible=false  reason=unread
1 of 3 read   visible=false  reason=unread
2 of 3 read   visible=false  reason=unread
3 of 3 read   visible=true   reason=null
```

```json
{
  "visible": true,
  "label": "ابدأ الاختبار",
  "reason": null
}
```

---

## 2. Reading is recorded by the app

Opening a section is what counts as reading it, and the API does not infer that
from a fetch — a prefetch, a retry or a cache warm is not a reader. So the app
says so:

```http
POST /api/sections/{section}/read
```

```json
{
  "data": {
    "id": 1,
    "read": true
  }
}
```

For a book that has **no sections**, the same thing at book level:

```http
POST /api/books/{book}/read
```

Do not send both for one book. A book with sections is tracked by those.

If the call fails, ignore it — never block reading on it.

### What a section carries now

```json
{
  "id": 1,
  "book_id": 1,
  "title": "لماذا نقرأ بانتظام",
  "position": 1,
  "body": "يبدأ هذا القسم بتحديد الغاية من القراءة: ما الذي يريد القارئ الوصول إليه بعد إتمام الكتاب، وكيف يُترجم ذلك إلى خطوات أسبوعية واضحة.\n\nثم ينتقل إلى الأدوات العملية: تقسيم الكتاب إلى أقسام متساوية، وتحديد وقت ثابت للقراءة، وتدوين الملاحظات على هامش النص أو في بطاقة مستقلة.\n\nويؤكد أن القراءة المنظمة ليست سرعة في الإنجاز، بل انتظام في المتابعة؛ فصفحات قليلة كل يوم أنفع من قراءة متقطعة تنقطع بعد أيام.\n\nوفي ختام القسم يُطلب من القارئ مراجعة ما قرأه بإيجاز، وكتابة ثلاث أفكار رئيسية بلغته الخاصة قبل الانتقال إلى الاختبار.",
  "pdf": "http://localhost:8000/storage/uploads/sample-section.pdf",
  "read": true
}
```

`read` is the only progress a section reports. There is no `passed`, no
`has_quiz`, no `questions_count` — a section cannot be examined.

---

## 3. Progress

Every book carries a `progress` block for the signed-in reader:

```json
{
  "sections_total": 3,
  "sections_read": 1,
  "read_percent": 33,
  "percent": 25,
  "completed": false
}
```

| Field | Meaning |
|---|---|
| `sections_total` | how many sections the book has |
| `sections_read` | how many the reader has opened |
| `read_percent` | how far through the **reading** |
| `percent` | how far through the **book**, exam included |
| `completed` | the book is finished — its exam passed |

**Two figures, because they answer different questions.** The exam counts as
one step beside the sections, so a three-section book has four things to do:

```
nothing done    read_percent   0    percent   0
1 of 3 read                   33             25
2 of 3 read                   67             50
3 of 3 read                  100             75   ← the exam opens here
exam passed                  100            100
```

Use `percent` for a completion bar and `read_percent` for a reading bar. Use
`completed` for "finished" — never `percent == 100`, though they agree.

```json
{
  "sections_total": 3,
  "sections_read": 3,
  "read_percent": 100,
  "percent": 100,
  "completed": true
}
```

Anonymous visitors get no `progress` block at all, rather than zeroes that
would read as "you have done nothing".

---

## 4. The exam

```http
GET  /api/books/{book}/quiz
POST /api/books/{book}/quiz
```

There is no other exam endpoint. `/api/sections/{id}/quiz` has been removed.

### Asking too early

The flag is not the only guard — the endpoint enforces the same rule, so a
client that ignores `quiz_button` still cannot sit the exam early:

`403`

```json
{
  "message": "اقرأ الكتاب كاملاً قبل أداء اختباره.",
  "exception": "Symfony\\Component\\HttpKernel\\Exception\\HttpException",
  "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Application.php",
  "line": 1440,
  "trace": [
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\helpers.php",
      "line": 67,
      "function": "abort",
      "class": "Illuminate\\Foundation\\Application",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\helpers.php",
      "line": 104,
      "function": "abort"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\app\\Http\\Controllers\\Api\\BookQuizController.php",
      "line": 138,
      "function": "abort_unless"
    },
    {
  …
```

### The paper

Questions, without the answers. The key is held server-side and never sent
before grading.

```json
{
  "id": 1,
  "text": "ما الغاية الأولى التي يبدأ بها القسم؟",
  "position": 1,
  "options": [
    {
      "id": 1,
      "text": "تحديد الغاية من القراءة",
      "position": 0
    },
    {
      "id": 2,
      "text": "حفظ فهرس الكتاب",
      "position": 1
    },
    {
      "id": 3,
      "text": "قراءة الخاتمة أولاً",
      "position": 2
    }
  ]
}
```

`meta` carries what the screen needs to present it:

```json
{
  "book": {
    "id": 1,
    "title": "مقدمة في القراءة المنظمة"
  },
  "questions_total": 9,
  "pass_ratio": 0.6,
  "quiz_button": {
    "visible": false,
    "label": "ابدأ الاختبار",
    "reason": "not_signed_in"
  },
  "taken": false,
  "attempts": 0,
  "passed": false,
  "best_score": null,
  "last_attempt": null
}
```

| Field | Use |
|---|---|
| `taken`, `attempts` | label the button "retake" rather than "start" |
| `passed` | stays true after a later failure — a resit cannot undo a pass |
| `best_score` | show it before they begin |
| `last_attempt` | score, total, result and time of the most recent try |
| `pass_ratio` | the fraction needed, e.g. `0.6` |

### Submitting

```json
{ "answers": { "<question id>": <option id>, … } }
```

A question left out counts as wrong.

---

## 5. Results

**A failure returns no answers.** A resit is free and unlimited, so handing
back the key would make the first attempt a way of being told them:

```json
{
  "score": 5,
  "total": 9,
  "passed": false,
  "points_awarded": 0,
  "correct_options": null,
  "incorrect_questions": [
    2,
    3,
    6,
    8
  ],
  "new_badges": [
    {
      "id": 1,
      "name": "المثابر",
      "description": "أنهيت أول قسم من أقسام البرنامج.",
      "image": "http://localhost:8000/assets/badge.png",
      "criteria_type": "sections_read",
      "criteria_value": 1,
      "position": 0
    }
  ],
  "new_certificates": []
}
```

**A pass returns everything it produced** — the key, the points, the badges,
the certificates, and the reader's recalculated standing:

```json
{
  "score": 9,
  "total": 9,
  "passed": true,
  "points_awarded": 400,
  "correct_options": {
    "1": 1,
    "2": 5,
    "3": 8,
    "4": 10,
    "5": 13,
    "6": 17,
    "7": 19,
    "8": 23,
    "9": 25
  },
  "incorrect_questions": [],
  "new_certificates": [
    {
      "id": 29,
      "serial": "CERT-2026-WUL0TB",
      "title": "مقدمة في القراءة المنظمة",
      "issued_at": "2026-10-17",
      "verification_url": "http://localhost/api/certificates/verify/5783c6bc-557f-40c9-bc2b-b457a4b738fd"
    }
  ]
}
```

- **Failed** → `correct_options` is `null`; `incorrect_questions` lists what
  to go back and read.
- **Passed** → `correct_options` maps question id to the correct option id.

Mark the reader's own wrong answers either way. After a failure, mark
**nothing** as correct — reading a missing `correct_options` as "everything is
wrong" would redden a paper they got most of right.

### A retake is free and pays nothing

```json
{
  "score": 9,
  "total": 9,
  "passed": true,
  "points_awarded": 0,
  "incorrect_questions": [],
  "new_badges": [],
  "new_certificates": []
}
```

The certificate is **re-dated, not reissued**: its serial and verification
code stay the same, because they are printed on copies that must keep
verifying.

---

## 6. Certificates

A certificate per book, and one per level when all its books are done.
`GET /api/certificates/available` lists everything issuable:

```json
{
  "scope": "book",
  "book": {
    "id": 1,
    "title": "مقدمة في القراءة المنظمة"
  },
  "level": null,
  "title": "مقدمة في القراءة المنظمة",
  "earned": true,
  "serial": "CERT-2026-WUL0TB",
  "issued_at": "2026-10-17",
  "verification_url": "http://localhost/api/certificates/verify/5783c6bc-557f-40c9-bc2b-b457a4b738fd",
  "sections_read": 3,
  "sections_total": 3,
  "percent": 100
}
```

`scope` is `book` or `level`. A reader finishing the last book of a level
earns **both at once**, so a screen that assumes one per event will drop the
other.

---

## 7. Notifications

`certificate` and `badge` are issued automatically and arrive in the same feed
as the dashboard's announcements, counting towards `meta.unread`:

```json
[
  {
    "category": "badge",
    "title": "حصلت على وسام المثابر"
  },
  {
    "category": "badge",
    "title": "حصلت على وسام متمم الكتاب"
  },
  {
    "category": "certificate",
    "title": "صدرت شهادتك في «مقدمة في القراءة المنظمة»"
  }
]
```

The full set: `certificate`, `badge`, `program`, `exam`, `results`, `honor`,
`general`. All mutable through `PUT /api/notification-preferences`.

---

## 8. Errors

| Status | When | What to do |
|---|---|---|
| `401` | no token, or revoked | send them back to sign in |
| `403` | the book is not read yet, or an earlier book in a sequential programme is unfinished | show the message; it is written for the reader |
| `404` | unpublished, withdrawn with its level, or in a selective programme they are not assigned to | treat as absent, not as an error |
| `422` | the body failed validation | show `errors` against the fields |

A `404` on a book the app has cached is normal — it may have been withdrawn
since. Treat it as "no longer available" and return to the catalogue.

```json
{
  "message": "The answers field must be present.",
  "errors": {
    "answers": [
      "The answers field must be present."
    ]
  }
}
```

---

## 9. Other things worth knowing

**Media fields are absolute URLs.** `cover`, `image`, `avatar` and `pdf` come
back ready to use. Do not prefix them. `null` means nothing was uploaded.

**A document is per language.** `pdf` on a book or section is resolved to the
requested language before it is sent — one string, not a map.

**Language** comes from `?lang=ar`, `X-Locale`, `Accept-Language`, the reader's
saved language, then the dashboard default — first match wins. A field with no
translation falls back rather than coming back empty.

**Deactivating a level withdraws its books**: they leave the catalogue, their
sections and exam answer `404`, and they leave the totals.

---

## The whole thing in order

1. `GET /api/books/{id}` — the book, and whether its exam is open
2. `POST /api/sections/{id}/read` for each section, or
   `POST /api/books/{id}/read` for a book that is one document
3. `GET /api/books/{id}/quiz` — the paper, once the reading is done
4. `POST /api/books/{id}/quiz` — the answers; back come the score, the points,
   any badges and certificates, and the new standing
5. On a failure: back to 3, as often as the reader likes
6. On a pass: `completed` turns true, and the certificate and badges are
   already in the feed
