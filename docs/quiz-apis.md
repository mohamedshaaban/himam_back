# Himam — the quiz, end to end

How a reader is examined, what the API asks for at each step, and what it
answers. Every request and response below was captured from a running API
against a reader created for the purpose; none of it was written by hand.

Base path `/api`. Every endpoint here needs `Authorization: Bearer <token>`
from `POST /api/auth/login` or `POST /api/auth/register`. `Accept-Language`
decides the language of the questions, the button wording and the messages; the
examples use `en`.

---

## 1. Layout and examination are separate decisions

**How a book is laid out** follows from whether it has sections. A book with
sections is read part by part; a book without them is read as one document.

**Where its exam lives** follows from where the questions were written. If a
book has questions of its own, it is examined once over all of it and the
section quizzes stop being offered. If it has none, each section carries its
own quiz.

The two are independent, so three shapes exist in practice:

| | Layout | Exam | Finished when |
|---|---|---|---|
| **A** | one document | the book's own | the book's exam is passed |
| **B** | sections | the book's own | the book's exam is passed |
| **C** | sections | one per section | every section is passed |

Shape **B** is the one the programme asks for most: the sections are the way
through the reading, and the examination happens once at the end.

A book can also carry the whole text as one document beside its sections, in
any shape.

### Reading each shape off the payload

`sections` and `has_own_quiz` say which shape a book is in.

| Field | Meaning |
|---|---|
| `sections` / `sections_count` | empty means it is read as one document |
| `has_own_quiz` | true means the exam is the book's, not the sections' |
| `quiz_button.visible` | whether to offer the book's exam at all |
| `quiz_button.label` | what the button should say, in the requested language |
| `pdf` | the whole-book document, if one was uploaded |

#### Read as one document, examined as a whole

Shape **A**. No sections, a document of its own, its own exam.

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
      "sections_total": 0,
      "sections_read": 0,
      "sections_passed": 0,
      "read_percent": 0,
      "percent": 0,
      "completed": false
    }
  }
}
```

#### Read in sections, examined as a whole

Shape **B**. Three sections for the reading, and `has_own_quiz` is true — so the exam is the book's, and the sections carry none.

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
    "has_own_quiz": true,
    "quiz_button": {
      "visible": true,
      "label": "Exam on the whole book"
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

#### Read in sections, examined section by section

Shape **C**. `has_own_quiz` is false and `quiz_button.visible` with it; each section is examined on its own.

```http
GET /api/books/2
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`200`

```json
{
  "data": {
    "id": 2,
    "title": "Foundations of Systematic Thinking",
    "author": "Prepared by the programme administration",
    "description": "Sets out the rules of ordered thinking and how to apply them to what you read, so a text becomes understanding rather than scattered facts.",
    "cover": "http://127.0.0.1:8000/assets/banner.svg",
    "pdf": null,
    "has_own_quiz": false,
    "quiz_button": {
      "visible": false,
      "label": "Take the quiz"
    },
    "pages": 165,
    "points": 400,
    "position": 1,
    "level": {
      "id": 1,
      "name": "First Level",
      "description": "An introduction to structured reading and its basic tools.",
      "position": 1
    },
    "sections": [
      {
        "id": 4,
        "book_id": 2,
        "title": "Why We Read Regularly",
        "position": 1,
        "questions_count": 3,
        "pdf": "http://127.0.0.1:8000/storage/uploads/sample-section.pdf",
        "read": false,
        "passed": false
      },
      {
        "id": 5,
        "book_id": 2,
        "title": "Building a Reading Plan",
        "position": 2,
        "questions_count": 3,
        "pdf": "http://127.0.0.1:8000/storage/uploads/sample-section.pdf",
        "read": false,
        "passed": false
      },
      {
        "id": 6,
        "book_id": 2,
        "title": "Review and Summary",
        "position": 3,
        "questions_count": 3,
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

`visible` is the dashboard's switch **and** the existence of the book's own
questions: a book examined by section, or one whose questions have not been
written yet, reports `false` whatever the switch says. `label` falls back to
the translated default when the dashboard has set nothing.

The client renders what it is told. It must not decide for itself that a book
should show a button.

The dashboard sets both through `PUT /api/admin/books/{id}`:

```json
{
  "quiz_button_visible": true,
  "quiz_button_label": { "ar": "اختبار الكتاب كاملاً", "en": "Exam on the whole book" }
}
```

and writes the book's own questions through `POST /api/admin/books/{id}/questions`,
which is what moves the exam from the sections to the book.

---

## 3. Opening the material

Opening the material is what counts as reading it. The API does not infer this
from a fetch of the questions — a prefetch or a retry is not a reader — so the
client says so explicitly.

#### Record that a whole-book document was opened

For a book read as one document.

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

For a section. A book that has sections is tracked by those, so a client does not also record a read against the book — that would give two answers to how far the reader has got.

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

### Reading and passing are reported separately

A book examined as a whole has no section passes to count. If progress were a
single number drawn from passes, a reader who had read every page would sit at
zero with nothing to show for it. So the `progress` block carries both.

#### Reading progress after one section

One of three sections read, none passed — because on this book there is nothing to pass until the book's own exam.

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
    "has_own_quiz": true,
    "quiz_button": {
      "visible": true,
      "label": "Exam on the whole book"
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
        "read": true,
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
  …
```

| Field | Meaning |
|---|---|
| `sections_total` | how many sections the book has |
| `sections_read` | how many the reader has opened |
| `sections_passed` | how many section quizzes they have passed — always `0` in shapes A and B |
| `read_percent` | reading progress |
| `percent` | passing progress |
| `completed` | the book is finished: its own exam passed, or every section passed |

`completed` is the one to trust for "has this reader finished the book". It
follows whichever rule applies to that book's shape.

---

## 4. Fetching the paper

The questions come without the answers. The key is held server-side and is
never sent before grading.

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
      "id": 46,
      "serial": "CERT-2026-TSUF0M",
      "title": "A book without sections",
      "issued_at": "2026-10-15",
      "verification_url": "http://127.0.0.1:8000/api/certificates/verify/2261ecdd-a84f-4bc3-a44b-77e27a196e06"
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
section 9.

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

## 8. A section exam, where one still applies

On a book in shape **C**, each section is examined on its own. The request and
the response have the same shape as the book exam, so one screen serves both.

#### A section exam

The paper for one section.

```http
GET /api/sections/4/quiz
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`200`

```json
{
  "data": [
    {
      "id": 10,
      "text": "What does the section begin by settling?",
      "position": 0,
      "options": [
        {
          "id": 28,
          "text": "Settling the purpose of reading",
          "position": 0
        },
        {
          "id": 29,
          "text": "Memorising the table of contents",
          "position": 1
        },
        {
          "id": 30,
          "text": "Reading the conclusion first",
          "position": 2
        }
      ]
    },
    {
      "id": 11,
      "text": "Which statement matches the structured reading method?",
      "position": 1,
      "options": [
        {
          "id": 31,
          "text": "Finishing the book in one sitting",
          "position": 0
        },
        {
          "id": 32,
          "text": "A few pages every day",
          "position": 1
        },
        {
          "id": 33,
          "text": "Reading only when time allows",
          "position": 2
        }
      ]
    },
    {
      "id": 12,
      "text": "What is asked of the reader before moving on to the quiz?",
      "position": 2,
      "options": [
        {
  …
```

#### Passing a section exam

The same body the book exam returns. The book's certificate is issued once the last of its sections is passed.

```http
POST /api/sections/4/quiz
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
Content-Type: application/json

{
  "answers": {
    "10": 28,
    "11": 32,
    "12": 35
  }
}
```

`200`

```json
{
  "score": 3,
  "total": 3,
  "passed": true,
  "points_awarded": 150,
  "correct_options": {
    "10": 28,
    "11": 32,
    "12": 35
  },
  "incorrect_questions": [],
  "new_badges": [
    {
      "id": 1,
      "name": "The Persistent",
      "description": "Passed your first section of the programme.",
      "image": "http://127.0.0.1:8000/assets/badge.png",
      "criteria_type": "sections_passed",
      "criteria_value": 1,
      "position": 0
    }
  ],
  "new_certificates": [],
  "stats": {
    "points": 650,
    "sections_passed": 1,
    "sections_total": 18,
    "books_completed": 1,
    "books_total": 7,
    "badges_earned": 2,
    "certificates": 1
  }
}
```

---

## 9. What the reader is told

A certificate and a badge each arrive in the reader's own notification feed, as
an announcement addressed to them. This is the same inbox that carries the
dashboard's general announcements, so it already has an unread count on the bell
and the reader can mute either kind.

#### The notification feed

The newest entries are the badges and certificates from the passes above; the rest are the dashboard's general announcements.

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
      "id": 115,
      "tag": "Badge",
      "title": "New badge: The Persistent",
      "body": "Congratulations — “The Persistent” has been added to your badges.",
      "image": "http://127.0.0.1:8000/assets/badge.png",
      "category": "badge",
      "published_at": "2026-10-15T09:51:54+00:00",
      "read": false
    },
    {
      "id": 114,
      "tag": "Certificate",
      "title": "Your certificate for “A book without sections” has been issued",
      "body": "Congratulations — your certificate for “A book without sections” has been issued with serial CERT-2026-TSUF0M. You will find it on the certificates page.",
      "image": null,
      "category": "certificate",
      "published_at": "2026-10-15T09:51:51+00:00",
      "read": false
    },
    {
      "id": 113,
      "tag": "Badge",
      "title": "New badge: Book Finisher",
      "body": "Congratulations — “Book Finisher” has been added to your badges.",
      "image": "http://127.0.0.1:8000/assets/badge.png",
      "category": "badge",
      "published_at": "2026-10-15T09:51:50+00:00",
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
  …
```

Categories: `certificate` and `badge` are the automatic ones. `program`,
`exam`, `results`, `honor` and `general` are posted from the dashboard. A reader
mutes any of them through `PUT /api/notification-preferences`.

Only a **new** certificate announces itself. Re-dating one after a retake is not
news, so the unread count does not move.

---

## 10. The certificates themselves

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
      "id": 46,
      "serial": "CERT-2026-TSUF0M",
      "title": "A book without sections",
      "issued_at": "2026-10-15",
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
      "verification_url": "http://127.0.0.1:8000/api/certificates/verify/2261ecdd-a84f-4bc3-a44b-77e27a196e06"
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
      "sections_passed": 0,
      "sections_total": 3,
      "percent": 0
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
      "sections_passed": 1,
      "sections_total": 3,
      "percent": 33
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
  …
```

---

## 11. Errors

#### A section quiz on a book examined as a whole

The exam moved to the book, so the section no longer has one. 404 rather than an empty paper, because "here is nothing" invites a client to render an empty exam.

```http
GET /api/sections/1/quiz
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
      "line": 85,
      "function": "abort"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\app\\Http\\Controllers\\Api\\QuizController.php",
      "line": 37,
      "function": "abort_if"
    },
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\ControllerDispatcher.php",
      "line": 46,
      "function": "show",
      "class": "App\\Http\\Controllers\\Api\\QuizController",
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

#### A book exam on a book examined by section

The mirror image: this book is examined section by section, so it has no exam of its own.

```http
GET /api/books/2/quiz
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

## 12. The whole thing in order

**Shapes A and B — the exam belongs to the book.**

1. `GET /api/books/{id}` — the layout, the document, and whether to show a button
2. `POST /api/books/{id}/read`, or `POST /api/sections/{id}/read` per section
3. `GET /api/books/{id}/quiz` — the paper, plus any earlier attempts
4. `POST /api/books/{id}/quiz` — the answers; back come the score, the points,
   any badges and certificates, and the new standing
5. On a failure: back to 3 as often as the reader likes
6. On a pass: `completed` turns true, and the certificate and badges are already
   in the feed

**Shape C — the exam belongs to each section.**

1. `GET /api/sections/{id}` — the section; `meta.book.has_own_quiz` confirms
   which exam applies
2. `POST /api/sections/{id}/read`
3. `GET /api/sections/{id}/quiz`
4. `POST /api/sections/{id}/quiz`

and the book's certificate is issued once the last of its sections is passed.
