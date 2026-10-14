# Himam — the quiz, end to end

How a reader is examined, what the API asks for at each step, and what it
answers. Every request and response below was captured from a running API
against a reader created for the purpose; none of it was written by hand.

Base path `/api`. Every endpoint here needs `Authorization: Bearer <token>`
from `POST /api/auth/login` or `POST /api/auth/register`. `Accept-Language`
decides the language of the questions, the button wording and the messages; the
examples use `en`.

---

## 1. Two ways a book is read

A book is read **in sections**, each with its own short quiz, or **as one
document** with one exam over all of it. The dashboard decides which by whether
the book has sections — there is no separate switch, because a switch could
disagree with the sections themselves.

A book may also carry the whole text as one document *beside* its sections. The
document is then the full text and the sections are the way through it.

| | Read in sections | Read as one document |
|---|---|---|
| Material | one document per section | one document for the book |
| Exam | one per section | one for the whole book |
| Exam endpoint | `/sections/{id}/quiz` | `/books/{id}/quiz` |
| Marked as read | `POST /sections/{id}/read` | `POST /books/{id}/read` |
| Points | per section | the book's own `points` |
| Finished when | every section passed | the book's exam passed |

Both kinds issue a certificate for the book once it is finished.

### Telling them apart

`sections_count` and `has_own_quiz` on the book say which kind it is.

#### A book read as one document

No sections, a document of its own, and its own exam.

```http
GET /api/books/9
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`200`

```json
{
  "data": {
    "id": 9,
    "title": "A book without sections",
    "author": "An author",
    "description": "Read as a whole",
    "cover": null,
    "pdf": "http://127.0.0.1:8000/storage/uploads/sample-section.pdf",
    "has_own_quiz": true,
    "quiz_button": {
      "visible": true,
      "label": "Revised exam"
    },
    "pages": 80,
    "points": 500,
    "position": 99,
    "level": {
      "id": 1,
      "name": "First Level",
      "description": "An introduction to structured reading and its basic tools.",
      "position": 1
    },
    "sections": [],
    "progress": {
      "sections_passed": 0,
      "sections_total": 0,
      "completed": false,
      "percent": 0
    }
  }
}
```

#### A book read in sections

Three sections; `has_own_quiz` is false, so there is no book-level exam even though the whole text is attached as a document.

```http
GET /api/books/1
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`200`

```json
{
  "data": {
    "id": 1,
    "title": "Introduction to Structured Reading",
    "author": "Prepared by the programme administration",
    "description": "A practical introduction to what structured reading means and the tools it uses, setting out a weekly plan that begins with a goal and ends with a review.",
    "cover": "http://127.0.0.1:8000/assets/banner.svg",
    "pdf": "http://127.0.0.1:8000/storage/uploads/sample-section.pdf",
    "has_own_quiz": false,
    "quiz_button": {
      "visible": false,
      "label": "Take the quiz"
    },
    "pages": 140,
    "points": 400,
    "position": 0,
    "level": {
      "id": 1,
      "name": "First Level",
      "description": "An introduction to structured reading and its basic tools.",
      "position": 1
    },
    "sections": [
      {
        "id": 1,
        "book_id": 1,
        "title": "Why We Read Regularly",
        "position": 1,
        "questions_count": 3,
        "pdf": "http://127.0.0.1:8000/storage/uploads/sample-section.pdf",
        "read": false,
        "passed": false
      },
      {
        "id": 2,
        "book_id": 1,
        "title": "Building a Reading Plan",
        "position": 2,
        "questions_count": 3,
        "pdf": "http://127.0.0.1:8000/storage/uploads/sample-section.pdf",
        "read": false,
        "passed": false
      },
      {
        "id": 3,
        "book_id": 1,
        "title": "Review and Summary",
        "position": 3,
        "questions_count": 3,
        "pdf": "http://127.0.0.1:8000/storage/uploads/sample-section.pdf",
        "read": false,
        "passed": false
      }
    ],
    "progress": {
  …
```

---

## 2. The quiz button comes from the API

Whether the exam is offered, and what the button says, are set in the dashboard
rather than built into the apps. A book can be published for reading weeks
before its questions are written, and the wording differs between a first
sitting and a resit; neither should need a release.

```json
"quiz_button": {
  "visible": true,
  "label": "Exam on the whole book"
}
```

`visible` is the dashboard's switch **and** the existence of questions: a book
with no questions yet reports `false` whatever the switch says, because a button
leading to an empty paper is worse than no button. `label` falls back to the
translated default when the dashboard has set nothing.

The client renders what it is told. It must not decide for itself that a book
with questions should show a button.

The dashboard sets both through `PUT /api/admin/books/{id}`:

```json
{
  "quiz_button_visible": true,
  "quiz_button_label": { "ar": "اختبار الكتاب كاملاً", "en": "Exam on the whole book" }
}
```

---

## 3. Opening the book

Opening the material is what counts as reading it. The API does not infer this
from a fetch of the questions — a prefetch or a retry is not a reader — so the
client says so explicitly.

#### Record that the book was opened

Sent once, when the reader opens a book read as one document. Failing to record a read must never stop the reader reading, so a client ignores the error.

```http
POST /api/books/9/read
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`200`

```json
{
  "data": {
    "id": 9,
    "read": true
  }
}
```

#### Record that a section was opened

The same thing for a section.

```http
POST /api/sections/1/read
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`200`

```json
{
  "data": {
    "id": 1,
    "read": true,
    "passed": false
  }
}
```

A book that also has sections is tracked by those, so a client does not record
a read against the book as well — that would give two answers to how far the
reader has got.

---

## 4. Fetching the paper

The questions come without the answers. The key is held server-side and is never
sent before grading.

#### The exam, before any attempt

`meta` carries everything a screen needs to say "start" rather than "retake": `taken`, `attempts`, `passed`, `best_score` and the last attempt. All of them are empty here.

```http
GET /api/books/9/quiz
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`200`

```json
{
  "data": [
    {
      "id": 55,
      "text": "Question 1?",
      "position": 1,
      "options": [
        {
          "id": 163,
          "text": "Option 1",
          "position": 1
        },
        {
          "id": 164,
          "text": "Option 2",
          "position": 2
        },
        {
          "id": 165,
          "text": "Option 3",
          "position": 3
        },
        {
          "id": 166,
          "text": "Option 4",
          "position": 4
        }
      ]
    },
    {
      "id": 56,
      "text": "Question 2?",
      "position": 2,
      "options": [
        {
          "id": 167,
          "text": "Option 1",
          "position": 1
        },
        {
          "id": 168,
          "text": "Option 2",
          "position": 2
        },
        {
          "id": 169,
          "text": "Option 3",
          "position": 3
        },
        {
          "id": 170,
          "text": "Option 4",
          "position": 4
        }
      ]
    },
    {
      "id": 57,
      "text": "Question 3?",
      "position": 3,
      "options": [
        {
          "id": 171,
          "text": "Option 1",
          "position": 1
        },
        {
          "id": 172,
          "text": "Option 2",
          "position": 2
        },
        {
          "id": 173,
          "text": "Option 3",
          "position": 3
        },
        {
          "id": 174,
          "text": "Option 4",
          "position": 4
        }
      ]
    },
    {
      "id": 58,
      "text": "Question 4?",
      "position": 4,
      "options": [
        {
  …
```

### What `meta` means

| Field | Meaning |
|---|---|
| `taken` | whether this reader has sat the exam at all |
| `attempts` | how many times |
| `passed` | whether any attempt passed — a later failure does not take this back |
| `best_score` | the highest score across attempts |
| `last_attempt` | the most recent attempt's score, total, result and time |
| `pass_ratio` | the fraction of correct answers needed, e.g. `0.6` |
| `questions_total` | how many questions the paper has |
| `quiz_button` | as above |

---

## 5. Submitting

The body is one object: `answers`, mapping question id to the chosen option id.
Every question the paper asked should be a key; a question left out counts as
wrong.

#### A failing attempt

Every answer wrong on purpose. Note what comes back and what does not.

```http
POST /api/books/9/quiz
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
Content-Type: application/json

{
  "answers": {
    "55": 163,
    "56": 167,
    "57": 171,
    "58": 175,
    "59": 179,
    "60": 183
  }
}
```

`200`

```json
{
  "score": 0,
  "total": 6,
  "passed": false,
  "points_awarded": 0,
  "correct_options": null,
  "incorrect_questions": [
    55,
    56,
    57,
    58,
    59,
    60
  ],
  "new_badges": [],
  "new_certificates": [],
  "stats": {
    "points": 0,
    "sections_passed": 0,
    "sections_total": 18,
    "books_completed": 0,
    "books_total": 7,
    "badges_earned": 0,
    "certificates": 0
  }
}
```

**The key is withheld from a failed attempt.** A resit is free and unlimited,
so returning the answers after a failure would turn the first attempt into a way
of being told them and the second into a formality. Instead the reader is told
*which questions* they got wrong, which is what they need in order to go back
and read again:

- `correct_options` — `null`
- `incorrect_questions` — the ids of the questions answered wrongly

Nothing is charged for a failure: `points_awarded` is `0`, no badge, no
certificate, and the reader's standing is unchanged.

#### The exam, after failing

`taken` is now true and `passed` is still false, so a screen can offer a resit and show what the last attempt scored.

```http
GET /api/books/9/quiz
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`200`

```json
{
  "data": [
    {
      "id": 55,
      "text": "Question 1?",
      "position": 1,
      "options": [
        {
          "id": 163,
          "text": "Option 1",
          "position": 1
        },
        {
          "id": 164,
          "text": "Option 2",
          "position": 2
        },
        {
          "id": 165,
          "text": "Option 3",
          "position": 3
        },
        {
          "id": 166,
          "text": "Option 4",
          "position": 4
        }
      ]
    },
    {
      "id": 56,
      "text": "Question 2?",
      "position": 2,
      "options": [
        {
          "id": 167,
          "text": "Option 1",
          "position": 1
        },
        {
          "id": 168,
          "text": "Option 2",
          "position": 2
        },
        {
          "id": 169,
          "text": "Option 3",
          "position": 3
        },
        {
          "id": 170,
          "text": "Option 4",
  …
```

---

## 6. Passing

Passing means the fraction of correct answers reaches `pass_ratio`.

#### A passing attempt

Now the key comes back, along with everything the pass produced.

```http
POST /api/books/9/quiz
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
Content-Type: application/json

{
  "answers": {
    "55": 165,
    "56": 169,
    "57": 173,
    "58": 177,
    "59": 181,
    "60": 184
  }
}
```

`200`

```json
{
  "score": 6,
  "total": 6,
  "passed": true,
  "points_awarded": 500,
  "correct_options": {
    "55": 165,
    "56": 169,
    "57": 173,
    "58": 177,
    "59": 181,
    "60": 184
  },
  "incorrect_questions": [],
  "new_badges": [
    {
      "id": 3,
      "name": "Book Finisher",
      "description": "Completed every section of one book.",
      "image": "http://127.0.0.1:8000/assets/badge.png",
      "criteria_type": "books_completed",
      "criteria_value": 1,
      "position": 2
    }
  ],
  "new_certificates": [
    {
      "id": 41,
      "serial": "CERT-2026-59YYKL",
      "title": "A book without sections",
      "issued_at": "2026-10-14",
      "verification_url": "http://127.0.0.1:8000/api/certificates/verify/76f5b1c3-df05-42b3-b87c-36c8cf5b8057"
    }
  ],
  "stats": {
    "points": 500,
    "sections_passed": 0,
    "sections_total": 18,
    "books_completed": 1,
    "books_total": 7,
    "badges_earned": 1,
    "certificates": 1
  }
}
```

A pass does four things at once, and the response says so rather than making
the client go and look:

1. **Points** — `points_awarded`, credited once. The book's own `points`, or the
   programme default when the book sets none.
2. **Badges** — `new_badges`, any whose threshold this pass crossed.
3. **Certificates** — `new_certificates`, for the book just finished, and for
   its level if that was its last book.
4. **Standing** — `stats`, the same counters the home screen shows, already
   recalculated.

Every certificate and every badge also arrives as a notification, covered in
section 8.

---

## 7. Sitting it again

A reader may sit the exam as often as they like, whether they failed or passed.

#### A retake after passing

A perfect score again. `points_awarded` is `0` and nothing new is issued.

```http
POST /api/books/9/quiz
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
Content-Type: application/json

{
  "answers": {
    "55": 165,
    "56": 169,
    "57": 173,
    "58": 177,
    "59": 181,
    "60": 184
  }
}
```

`200`

```json
{
  "score": 6,
  "total": 6,
  "passed": true,
  "points_awarded": 0,
  "correct_options": {
    "55": 165,
    "56": 169,
    "57": 173,
    "58": 177,
    "59": 181,
    "60": 184
  },
  "incorrect_questions": [],
  "new_badges": [],
  "new_certificates": [],
  "stats": {
    "points": 500,
    "sections_passed": 0,
    "sections_total": 18,
    "books_completed": 1,
    "books_total": 7,
    "badges_earned": 1,
    "certificates": 1
  }
}
```

### What a retake changes, and what it does not

| | |
|---|---|
| The attempt is recorded | **yes** — `attempts` rises, `best_score` keeps the highest |
| Points paid again | **no** — they are credited the first time only |
| A second certificate | **no** |
| The certificate's date | **updated** to the day of the new pass |
| Its serial and verification code | **unchanged** |
| A second notification | **no** |

The certificate is re-dated rather than reissued because its serial and
verification code are printed on copies and shared with people who are not
readers of this app. A reissue would quietly stop every one of those verifying.

A failure after a pass takes nothing back: `passed` in `meta` stays true, the
certificate stands, and `best_score` keeps the better result.

---

## 8. What the reader is told

A certificate and a badge each arrive in the reader's own notification feed, as
an announcement addressed to them. This is the same inbox that carries the
dashboard's general announcements, so it already has an unread count on the bell
and the reader can mute either kind.

#### The notification feed

The three newest are the two badges and the certificate from the pass above; the rest are the dashboard's general announcements.

```http
GET /api/announcements
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`200`

```json
{
  "data": [
    {
      "id": 104,
      "tag": "Badge",
      "title": "New badge: The Persistent",
      "body": "Congratulations — “The Persistent” has been added to your badges.",
      "image": "http://127.0.0.1:8000/assets/badge.png",
      "category": "badge",
      "published_at": "2026-10-14T17:48:01+00:00",
      "read": false
    },
    {
      "id": 102,
      "tag": "Badge",
      "title": "New badge: Book Finisher",
      "body": "Congratulations — “Book Finisher” has been added to your badges.",
      "image": "http://127.0.0.1:8000/assets/badge.png",
      "category": "badge",
      "published_at": "2026-10-14T17:47:56+00:00",
      "read": false
    },
    {
      "id": 103,
      "tag": "Certificate",
      "title": "Your certificate for “A book without sections” has been issued",
      "body": "Congratulations — your certificate for “A book without sections” has been issued with serial CERT-2026-59YYKL. You will find it on the certificates page.",
      "image": null,
      "category": "certificate",
      "published_at": "2026-10-14T17:47:56+00:00",
      "read": false
    },
    {
      "id": 1,
      "tag": "New programme",
      "title": "Structured Reading programme opens — first term",
      "body": "Registration is open until the end of the week.",
      "image": "http://127.0.0.1:8000/assets/banner.svg",
      "category": "program",
      "published_at": "2026-10-14T16:14:41+00:00",
      "read": false
    },
    {
      "id": 2,
      "tag": "Exam",
      "title": "Second level exam date",
      "body": "Sunday 16 August 2026, 7 p.m.",
      "image": "http://127.0.0.1:8000/assets/banner.svg",
      "category": "exam",
      "published_at": "2026-10-13T17:14:41+00:00",
      "read": false
    },
    {
      "id": 3,
      "tag": "Results",
      "title": "First level results are out",
  …
```

Categories: `certificate` and `badge` are the automatic ones. `program`,
`exam`, `results`, `honor` and `general` are posted from the dashboard. A reader
mutes any of them through `PUT /api/notification-preferences`.

Only a **new** certificate announces itself. Re-dating one after a retake is not
news, so the unread count does not move.

---

## 9. The certificates themselves

#### Certificates held

What the reader holds. A certificate carries either a `book` or a `level`.

```http
GET /api/certificates
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`200`

```json
{
  "data": [
    {
      "id": 41,
      "serial": "CERT-2026-59YYKL",
      "title": "A book without sections",
      "issued_at": "2026-10-14",
      "level": null,
      "book": {
        "id": 9,
        "title": "A book without sections",
        "author": "An author",
        "description": "Read as a whole",
        "cover": null,
        "pdf": "http://127.0.0.1:8000/storage/uploads/sample-section.pdf",
        "has_own_quiz": true,
        "quiz_button": {
          "visible": true,
          "label": "Revised exam"
        },
        "pages": 80,
        "points": 500,
        "position": 99
      },
      "verification_url": "http://127.0.0.1:8000/api/certificates/verify/76f5b1c3-df05-42b3-b87c-36c8cf5b8057"
    }
  ]
}
```

#### Every certificate, held or not

Everything issuable, with how far along the reader is. `scope` says whether a row is for a book or a level; `percent` is the progress towards it. A screen can show what is still ahead rather than only what is done.

```http
GET /api/certificates/available
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`200`

```json
{
  "data": [
    {
      "scope": "book",
      "book": {
        "id": 1,
        "title": "Introduction to Structured Reading"
      },
      "level": null,
      "title": "Introduction to Structured Reading",
      "earned": false,
      "serial": null,
      "issued_at": null,
      "verification_url": null,
      "sections_passed": 1,
      "sections_total": 3,
      "percent": 33
    },
    {
      "scope": "book",
      "book": {
        "id": 2,
        "title": "Foundations of Systematic Thinking"
      },
      "level": null,
      "title": "Foundations of Systematic Thinking",
      "earned": false,
      "serial": null,
      "issued_at": null,
      "verification_url": null,
      "sections_passed": 0,
      "sections_total": 3,
      "percent": 0
    },
    {
      "scope": "book",
      "book": {
        "id": 3,
        "title": "The Great Books: A Reader's Guide"
      },
      "level": null,
      "title": "The Great Books: A Reader's Guide",
      "earned": false,
      "serial": null,
      "issued_at": null,
      "verification_url": null,
      "sections_passed": 0,
      "sections_total": 3,
      "percent": 0
    },
    {
      "scope": "book",
      "book": {
        "id": 4,
        "title": "Cognitive Awareness"
      },
      "level": null,
      "title": "Cognitive Awareness",
      "earned": false,
      "serial": null,
      "issued_at": null,
      "verification_url": null,
      "sections_passed": 0,
      "sections_total": 3,
      "percent": 0
    },
    {
      "scope": "book",
      "book": {
        "id": 5,
        "title": "Summarising Skills"
      },
      "level": null,
      "title": "Summarising Skills",
      "earned": false,
      "serial": null,
      "issued_at": null,
      "verification_url": null,
      "sections_passed": 0,
      "sections_total": 3,
      "percent": 0
    },
    {
  …
```

---

## 10. Errors

#### No whole-book exam on a book read in sections

A book read in sections has no book-level exam. The API answers 404 rather than an empty paper, because "here is nothing" invites a client to render an empty exam.

```http
GET /api/books/1/quiz
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`404`

```json
{
  "message": "",
  "exception": "Symfony\\Component\\HttpKernel\\Exception\\NotFoundHttpException",
  "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Application.php",
  "line": 1437,
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
      "line": 40,
      "function": "abort_unless"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\ControllerDispatcher.php",
      "line": 46,
      "function": "show",
      "class": "App\\Http\\Controllers\\Api\\BookQuizController",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Route.php",
      "line": 265,
      "function": "dispatch",
      "class": "Illuminate\\Routing\\ControllerDispatcher",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Route.php",
      "line": 211,
      "function": "runController",
      "class": "Illuminate\\Routing\\Route",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php",
      "line": 822,
      "function": "run",
      "class": "Illuminate\\Routing\\Route",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
      "line": 180,
      "function": "Illuminate\\Routing\\{closure}",
      "class": "Illuminate\\Routing\\Router",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Middleware\\SubstituteBindings.php",
      "line": 50,
      "function": "Illuminate\\Pipeline\\{closure}",
      "class": "Illuminate\\Pipeline\\Pipeline",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
      "line": 219,
      "function": "handle",
      "class": "Illuminate\\Routing\\Middleware\\SubstituteBindings",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Auth\\Middleware\\Authenticate.php",
      "line": 63,
      "function": "Illuminate\\Pipeline\\{closure}",
      "class": "Illuminate\\Pipeline\\Pipeline",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
      "line": 219,
      "function": "handle",
      "class": "Illuminate\\Auth\\Middleware\\Authenticate",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\app\\Http\\Middleware\\SetLocale.php",
      "line": 51,
      "function": "Illuminate\\Pipeline\\{closure}",
      "class": "Illuminate\\Pipeline\\Pipeline",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
      "line": 219,
      "function": "handle",
      "class": "App\\Http\\Middleware\\SetLocale",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\app\\Http\\Middleware\\ResolveOptionalUser.php",
      "line": 35,
      "function": "Illuminate\\Pipeline\\{closure}",
      "class": "Illuminate\\Pipeline\\Pipeline",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
      "line": 219,
      "function": "handle",
      "class": "App\\Http\\Middleware\\ResolveOptionalUser",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
      "line": 137,
      "function": "Illuminate\\Pipeline\\{closure}",
      "class": "Illuminate\\Pipeline\\Pipeline",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php",
      "line": 821,
      "function": "then",
      "class": "Illuminate\\Pipeline\\Pipeline",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php",
      "line": 800,
      "function": "runRouteWithinStack",
      "class": "Illuminate\\Routing\\Router",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php",
      "line": 764,
      "function": "runRoute",
      "class": "Illuminate\\Routing\\Router",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php",
      "line": 753,
      "function": "dispatchToRoute",
      "class": "Illuminate\\Routing\\Router",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php",
      "line": 200,
      "function": "dispatch",
      "class": "Illuminate\\Routing\\Router",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
      "line": 180,
      "function": "Illuminate\\Foundation\\Http\\{closure}",
      "class": "Illuminate\\Foundation\\Http\\Kernel",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest.php",
      "line": 21,
      "function": "Illuminate\\Pipeline\\{closure}",
      "class": "Illuminate\\Pipeline\\Pipeline",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull.php",
      "line": 31,
      "function": "handle",
      "class": "Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
      "line": 219,
      "function": "handle",
      "class": "Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest.php",
      "line": 21,
      "function": "Illuminate\\Pipeline\\{closure}",
      "class": "Illuminate\\Pipeline\\Pipeline",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\TrimStrings.php",
      "line": 51,
      "function": "handle",
      "class": "Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
      "line": 219,
      "function": "handle",
      "class": "Illuminate\\Foundation\\Http\\Middleware\\TrimStrings",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\ValidatePostSize.php",
      "line": 27,
      "function": "Illuminate\\Pipeline\\{closure}",
      "class": "Illuminate\\Pipeline\\Pipeline",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
      "line": 219,
      "function": "handle",
      "class": "Illuminate\\Http\\Middleware\\ValidatePostSize",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance.php",
      "line": 109,
      "function": "Illuminate\\Pipeline\\{closure}",
      "class": "Illuminate\\Pipeline\\Pipeline",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
      "line": 219,
      "function": "handle",
      "class": "Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\HandleCors.php",
      "line": 74,
      "function": "Illuminate\\Pipeline\\{closure}",
      "class": "Illuminate\\Pipeline\\Pipeline",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
      "line": 219,
      "function": "handle",
      "class": "Illuminate\\Http\\Middleware\\HandleCors",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\TrustProxies.php",
      "line": 58,
      "function": "Illuminate\\Pipeline\\{closure}",
      "class": "Illuminate\\Pipeline\\Pipeline",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
      "line": 219,
      "function": "handle",
      "class": "Illuminate\\Http\\Middleware\\TrustProxies",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks.php",
      "line": 22,
      "function": "Illuminate\\Pipeline\\{closure}",
      "class": "Illuminate\\Pipeline\\Pipeline",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
      "line": 219,
      "function": "handle",
      "class": "Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\ValidatePathEncoding.php",
      "line": 26,
      "function": "Illuminate\\Pipeline\\{closure}",
      "class": "Illuminate\\Pipeline\\Pipeline",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
      "line": 219,
      "function": "handle",
      "class": "Illuminate\\Http\\Middleware\\ValidatePathEncoding",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
      "line": 137,
      "function": "Illuminate\\Pipeline\\{closure}",
      "class": "Illuminate\\Pipeline\\Pipeline",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php",
      "line": 175,
      "function": "then",
      "class": "Illuminate\\Pipeline\\Pipeline",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php",
      "line": 144,
      "function": "sendRequestThroughRouter",
      "class": "Illuminate\\Foundation\\Http\\Kernel",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Application.php",
      "line": 1220,
      "function": "handle",
      "class": "Illuminate\\Foundation\\Http\\Kernel",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\public\\index.php",
      "line": 20,
      "function": "handleRequest",
      "class": "Illuminate\\Foundation\\Application",
      "type": "->"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\resources\\server.php",
      "line": 23,
      "function": "require_once"
    }
  ]
}
```

#### A malformed submission

`answers` must be present, even when empty. The message is translated by `Accept-Language`.

```http
POST /api/books/9/quiz
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
Content-Type: application/json

{}
```

`422`

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

### Other responses worth handling

| Status | When |
|---|---|
| `401` | the token is missing, expired or has been revoked |
| `403` | the book is locked — an earlier book in a sequential programme is unfinished, or the reader was not assigned to a selective one |
| `404` | the book or section is unpublished, or has no exam of the kind asked for |
| `422` | `answers` missing, or an option id that is not an integer |

A `403` carries the reason, so the screen can say which book is in the way
rather than only that something is.

---

## 11. The whole thing in order

A book read as one document:

1. `GET /api/books/{id}` — the document, and whether to show a quiz button
2. `POST /api/books/{id}/read` — the reader opened it
3. `GET /api/books/{id}/quiz` — the paper, plus any earlier attempts
4. `POST /api/books/{id}/quiz` — the answers; back come the score, the points,
   any badges and certificates, and the new standing
5. On a failure: back to 3 as often as the reader likes
6. On a pass: the certificate and the badges are already in the feed

A book read in sections, per section:

1. `GET /api/sections/{id}` — the section
2. `POST /api/sections/{id}/read`
3. `GET /api/sections/{id}/quiz`
4. `POST /api/sections/{id}/quiz`

and the book's certificate is issued once the last of its sections is passed.
