# Himam — API reference

The complete HTTP interface behind the Himam reading programme: what each
endpoint takes, what it answers, and the rules it enforces.

Every reader response below was captured from a running API against an account
created for the purpose. Every admin request body is read from that
controller's own validation rules. The route index at the end is read from the
router. None of it is transcribed by hand, so the document cannot quietly drift
from the API.

Three documents go deeper on particular areas: **quiz-apis.md** on examination
end to end, **programs-apis.md** on reading programmes, and **auth-apis.md** on
registration and password recovery.

---

## 1. Using the API

### Base URL

| | |
|---|---|
| Production | `https://himam-back.onrender.com/api` |
| Local | `http://127.0.0.1:8000/api` |

### Authentication

Sanctum personal access tokens. `POST /auth/register` and `POST /auth/login`
both return one; send it on every authenticated call:

```http
Authorization: Bearer 14|8hlAJ3vgplHUW6oLWdIS4824oMHVsQ7c…
```

A token lasts until it is used to sign out, or until an administrator removes
the account. A request without one, or with a revoked one, gets `401` — which a
client should treat as "sign in again" rather than retrying.

Three tiers of access:

| Tier | Needs | Examples |
|---|---|---|
| Public | nothing | the catalogue, the honour board, content pages, certificate verification |
| Reader | a token | progress, exams, certificates, notifications |
| Administrator | a token on an `admin` account | everything under `/api/admin` |

A public endpoint still reads the token when one is sent. `GET /books` returns
each book's `progress` for a signed-in reader and omits the block entirely for
a visitor, rather than reporting zeroes that would read as "you have done
nothing".

### Language

Every translatable field is resolved to one string before it leaves the API.
Four inputs are consulted, in this order, and the first that names a language
the installation has wins:

1. `?lang=ar` on the query string
2. `X-Locale: ar`
3. `Accept-Language: ar`
4. the signed-in reader's saved language
5. the default language set in the dashboard

The languages that ship are Arabic, English, French and Urdu, but they are rows
in a table rather than constants: adding one in the dashboard makes it available
to every translatable field without a deploy. `GET /locales` lists what is
installed.

A field with no translation in the requested language falls back to the default
language, and then to any language it does have, rather than coming back empty.

Arabic is the default and is right-to-left; `GET /locales` reports `dir` for
each language so a client can set direction from the data.

### Response shape

Most endpoints answer with `data`, and add `meta` when there is something to
say about the payload as a whole:

```json
{
  "data": [ … ],
  "meta": { "unread": 3 }
}
```

Not every endpoint does. The ones that answer with their fields at the top
level instead:

| Endpoint | What comes back |
|---|---|
| `POST /auth/register`, `POST /auth/login` | `token` and `user` |
| `POST /books/{book}/quiz`, `POST /sections/{section}/quiz` | the result, flat |
| `GET /dashboard`, `GET /progress` | the screen's sections |
| `GET /certificates/verify/{code}` | the verification result |

The quiz pair is deliberate: one screen submits to either endpoint, and an
envelope on one but not the other would make it read the result two different
ways. The rest are endpoints that return a composite rather than a resource,
where a `data` key would wrap a thing that is not one.

### Media

Images and documents come back as absolute URLs, ready to use:

```json
"cover": "https://himam-back.onrender.com/assets/banner.svg"
```

A `null` means nothing was uploaded. A client should not build these paths
itself — where files are stored is a deployment decision, and the API resolves
it.

### Errors

| Status | Meaning | What a client should do |
|---|---|---|
| `401` | no token, or it has been revoked | send the reader back to sign in |
| `403` | the content is locked for this reader | show the reason the body carries |
| `404` | not found, unpublished, or no exam of that kind | treat as absent, not as an error to retry |
| `422` | the body failed validation | show `errors` against the fields |
| `429` | too many attempts | wait; password reset is rate-limited |

A `422` names every field that failed, already translated:

```json
{
  "message": "The answers field must be present.",
  "errors": { "answers": ["The answers field must be present."] }
}
```

---

## 2. Signing in

#### Register

Creates the account and returns a token straight away, so a reader is never asked to sign in again right after signing up.

```http
POST /api/auth/register
Accept: application/json
Accept-Language: en
Content-Type: application/json

{
  "name": "A Reader",
  "email": "api1792173473@himam.test",
  "password": "password123",
  "password_confirmation": "password123",
  "gender": "male",
  "country": "KW",
  "city": "Kuwait City",
  "accepts_email": true,
  "consent": true
}
```

`201`

```json
{
  "token": "7|q0NepUCxfRXIrkoY4qnzhOdasL5Vw16eDqfCRyyl3c61a5ec",
  "user": {
    "id": 11,
    "name": "A Reader",
    "email": "api1792173473@himam.test",
    "gender": "male",
    "city": "Kuwait City",
    "country": "KW",
    "accepts_email": true,
    "avatar": null,
    "role": "student",
    "locale": "en",
    "points": 0,
    "level": null,
    "created_at": "2026-10-16T17:57:55+00:00"
  }
}
```

#### Sign in

The token is a Sanctum personal access token. Send it as `Authorization: Bearer <token>` on every authenticated call.

```http
POST /api/auth/login
Accept: application/json
Accept-Language: en
Content-Type: application/json

{
  "email": "api1792173473@himam.test",
  "password": "password123"
}
```

`200`

```json
{
  "token": "8|BDiKbOLBvqthytCMAGKj4TXyPUwF4Zcwnb02eDXm25ce526d",
  "user": {
    "id": 11,
    "name": "A Reader",
    "email": "api1792173473@himam.test",
    "gender": "male",
    "city": "Kuwait City",
    "country": "KW",
    "accepts_email": true,
    "avatar": null,
    "role": "student",
    "locale": "en",
    "points": 0,
    "level": null,
    "created_at": "2026-10-16T17:57:55+00:00"
  }
}
```

#### The signed-in reader

```http
GET /api/auth/me
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`200`

```json
{
  "data": {
    "id": 11,
    "name": "A Reader",
    "email": "api1792173473@himam.test",
    "gender": "male",
    "city": "Kuwait City",
    "country": "KW",
    "accepts_email": true,
    "avatar": null,
    "role": "student",
    "locale": "en",
    "points": 0,
    "level": null,
    "badges_count": 0,
    "certificates_count": 0,
    "created_at": "2026-10-16T17:57:55+00:00"
  }
}
```

#### Request a reset link

The reply is identical whether or not the address is known, so the endpoint cannot be used to discover who has an account.

```http
POST /api/auth/forgot-password
Accept: application/json
Accept-Language: en
Content-Type: application/json

{
  "email": "api1792173473@himam.test"
}
```

`200`

```json
{
  "message": "If that address has an account, a reset link is on its way."
}
```

`POST /auth/reset-password` completes the recovery, taking `token`, `email`,
`password` and `password_confirmation`. The token is single-use and every other
token on the account is revoked when the password changes, so a session someone
else is holding does not survive the reset. **auth-apis.md** covers the flow in
full.

---

## 3. Reference data

Lists a client needs before it can render its forms and screens.

#### Languages

Driven by the database, not by a deploy: adding a language here makes it available to every translatable field at once.

```http
GET /api/locales
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`200`

```json
{
  "data": [
    {
      "code": "ar",
      "name": "العربية",
      "english_name": "Arabic",
      "dir": "rtl"
    },
    {
      "code": "en",
      "name": "English",
      "english_name": "English",
      "dir": "ltr"
    },
    {
      "code": "fr",
      "name": "Français",
      "english_name": "French",
      "dir": "ltr"
    },
    {
      "code": "ur",
      "name": "اردو",
      "english_name": "Urdu",
      "dir": "rtl"
    }
  ],
  "meta": {
    "default": "ar",
    "fallback": "ar",
    "current": "en"
  }
}
```

#### Countries

```http
GET /api/countries
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`200`

```json
{
  "data": [
    {
      "code": "AF",
      "name": "Afghanistan",
      "dial_code": "+93"
    },
    {
      "code": "AL",
      "name": "Albania",
      "dial_code": "+355"
    },
    {
      "code": "DZ",
      "name": "Algeria",
      "dial_code": "+213"
    },
    {
      "code": "AR",
      "name": "Argentina",
      "dial_code": "+54"
    },
    {
      "code": "AM",
      "name": "Armenia",
      "dial_code": "+374"
    },
    {
      "code": "AU",
      "name": "Australia",
      "dial_code": "+61"
    },
    {
      "code": "AT",
      "name": "Austria",
      "dial_code": "+43"
    },
    {
      "code": "AZ",
      "name": "Azerbaijan",
      "dial_code": "+994"
    },
    {
      "code": "BH",
      "name": "Bahrain",
  …
```

#### Levels

```http
GET /api/levels
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`200`

```json
{
  "data": [
    {
      "id": 1,
      "name": "First Level",
      "description": "An introduction to structured reading and its basic tools.",
      "position": 1,
      "books_count": 4
    },
    {
      "id": 2,
      "name": "Second Level",
      "description": "Moving into the great books and building cognitive awareness.",
      "position": 2,
      "books_count": 2
    },
    {
      "id": 3,
      "name": "Third Level",
      "description": "Mastering summarising skills and managing reading time.",
      "position": 3,
      "books_count": 2
    }
  ]
}
```

#### Slides for one screen

```http
GET /api/slides/books
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`200`

```json
{
  "data": [
    {
      "id": 4,
      "screen": "books",
      "image": "http://127.0.0.1:8005/assets/banner.svg",
      "caption": "First level books",
      "href": null,
      "position": 0
    },
    {
      "id": 5,
      "screen": "books",
      "image": "http://127.0.0.1:8005/assets/certificate.png",
      "caption": "Level completion certificate",
      "href": null,
      "position": 1
    },
    {
      "id": 6,
      "screen": "books",
      "image": "http://127.0.0.1:8005/assets/badge.png",
      "caption": "Reading badges",
      "href": null,
      "position": 2
    }
  ]
}
```

---

## 4. Static content

The pages a reader can read without an account: about, privacy, support, and
the FAQ.

#### A content page

Pages are keyed by slug: `about`, `privacy`, `support` and whatever else the dashboard adds. No route change is needed for a new one.

```http
GET /api/pages/about
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`200`

```json
{
  "data": {
    "slug": "about",
    "title": "About Himam",
    "body": "<p>Himam is a scholarly programme from the <strong>Murtaqi Scientific Association</strong>, built to make structured reading achievable — to turn it from an intermittent wish into a settled habit.</p><h3>How the programme works</h3><p>It draws on the great books and trusted scholarly sources, divided into short sections, each followed by a brief quiz that settles what was read. Every section passed adds points, unlocks badges, and brings the level certificate closer.</p><h3>Our aim</h3><p>That every student of knowledge has a clear reading plan to follow, and sees the effect in themselves before they see it in a points total.</p>",
    "updated_at": "2026-10-15T10:06:12+00:00"
  }
}
```

#### Frequently asked questions

```http
GET /api/faqs
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`200`

```json
{
  "data": [
    {
      "id": 1,
      "question": "How do I start?",
      "answer": "Pick a book from the Books page and start with its first section: read it, then answer the short quiz. Passing the quiz credits the section's points to your balance.",
      "category": null
    },
    {
      "id": 2,
      "question": "What score do I need to pass?",
      "answer": "At least 60% of the section's questions. If you do not pass, you can retry as many times as you like.",
      "category": null
    },
    {
      "id": 3,
      "question": "Do retries earn points again?",
      "answer": "A section's points are credited once, on your first pass. Retaking a quiz afterwards is free and useful for revision, but earns nothing further.",
      "category": null
    },
    {
      "id": 4,
  …
```

#### Contact and support details

```http
GET /api/contact
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`200`

```json
{
  "data": {
    "email": "support@himam.test",
    "phone": "+966 11 000 0000",
    "whatsapp": "+966 55 000 0000",
    "website": "https://murtaqi.example.com",
    "address": "Riyadh, Saudi Arabia",
    "working_hours": "Sunday to Thursday, 9am to 5pm",
    "note": "For any question about the programme or trouble with your account, get in touch and we will reply within one working day.",
    "social": [
      {
        "platform": "x",
        "url": "https://x.com/"
      },
      {
        "platform": "youtube",
        "url": "https://youtube.com/"
      },
      {
        "platform": "telegram",
        "url": "https://t.me/"
      }
    ]
  }
}
```

---

## 5. The catalogue

### How a book is laid out, and how it is examined

Two separate decisions, which is why three shapes exist:

| | Layout | Exam | Finished when |
|---|---|---|---|
| **A** | one document | the book's own | the book's exam is passed |
| **B** | sections | the book's own | the book's exam is passed |
| **C** | sections | one per section | every section is passed |

`exam.scope` says it outright, so a client never has to infer it:

| `exam.scope` | Where the exam is | What to call |
|---|---|---|
| `book` | one exam over the whole book | `/api/books/{book}/quiz` |
| `sections` | one per section | `/api/sections/{section}/quiz` for each section whose `has_quiz` is true |
| `none` | no questions written yet | nothing to offer |

`exam.button` carries the dashboard's wording and whether to show a book-level
button at all; it is always `visible: false` unless `scope` is `book`. Each
section also reports `has_quiz`, because `questions_count` drops to zero when
the book claims the questions for its own exam — which reads as "this section
has no quiz" only if you already know that rule.

A book may also carry the whole text as one document beside its sections, in
any shape.

#### Every published book

Public. With a token it also carries each book's `progress` for that reader; without one the block is absent rather than zeroed.

```http
GET /api/books
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`200`

```json
{
  "data": [
    {
      "id": 1,
      "title": "Introduction to Structured Reading",
      "author": "Prepared by the programme administration",
      "description": "A practical introduction to what structured reading means and the tools it uses, setting out a weekly plan that begins with a goal and ends with a review.",
      "cover": "http://127.0.0.1:8005/assets/banner.svg",
      "pdf": null,
      "has_own_quiz": true,
      "quiz_button": {
        "visible": true,
        "label": "Take the quiz"
      },
      "exam": {
        "scope": "book",
        "button": {
          "visible": true,
          "label": "Take the quiz"
        },
        "questions_total": 9
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
      "sections_count": 3,
      "progress": {
        "sections_total": 3,
        "sections_read": 0,
        "sections_passed": 0,
        "read_percent": 0,
        "percent": 0,
        "completed": false
      }
    },
    {
      "id": 2,
      "title": "Foundations of Systematic Thinking",
      "author": "Prepared by the programme administration",
  …
```

#### A book read as one document

```http
GET /api/books/7
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`200`

```json
{
  "data": {
    "id": 7,
    "title": "A book read whole",
    "author": "The author",
    "description": "Read as one document.",
    "cover": null,
    "pdf": "http://127.0.0.1:8005/storage/uploads/sample-section.pdf",
    "has_own_quiz": true,
    "quiz_button": {
      "visible": true,
      "label": "Exam on the whole book"
    },
    "exam": {
      "scope": "book",
      "button": {
        "visible": true,
        "label": "Exam on the whole book"
      },
      "questions_total": 3
    },
    "pages": 120,
    "points": 500,
    "position": 10,
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

#### A book read in sections, examined as a whole

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
    "cover": "http://127.0.0.1:8005/assets/banner.svg",
    "pdf": null,
    "has_own_quiz": true,
    "quiz_button": {
      "visible": true,
      "label": "Take the quiz"
    },
    "exam": {
      "scope": "book",
      "button": {
        "visible": true,
        "label": "Take the quiz"
      },
      "questions_total": 9
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
        "questions_count": 0,
        "has_quiz": false,
        "pdf": "http://127.0.0.1:8005/storage/uploads/sample-section.pdf",
        "read": false,
        "passed": false
      },
      {
        "id": 2,
        "book_id": 1,
        "title": "Building a Reading Plan",
        "position": 2,
        "questions_count": 0,
  …
```

#### A book examined section by section

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
    "cover": "http://127.0.0.1:8005/assets/banner.svg",
    "pdf": null,
    "has_own_quiz": false,
    "quiz_button": {
      "visible": false,
      "label": "Take the quiz"
    },
    "exam": {
      "scope": "sections",
      "button": {
        "visible": false,
        "label": "Take the quiz"
      },
      "sections_with_quiz": 3
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
        "has_quiz": true,
        "pdf": "http://127.0.0.1:8005/storage/uploads/sample-section.pdf",
        "read": false,
        "passed": false
      },
      {
        "id": 5,
        "book_id": 2,
        "title": "Building a Reading Plan",
        "position": 2,
        "questions_count": 3,
  …
```

#### One section

```http
GET /api/sections/4
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`200`

```json
{
  "data": {
    "id": 4,
    "book_id": 2,
    "title": "Why We Read Regularly",
    "position": 1,
    "questions_count": 3,
    "has_quiz": true,
    "body": "This section opens by settling the purpose of reading: what the reader wants to arrive at once the book is finished, and how that translates into clear weekly steps.\n\nIt then turns to the practical tools: dividing the book into equal sections, fixing a set time to read, and taking notes in the margin or on a separate card.\n\nIt insists that structured reading is not speed of completion but regularity of follow-through; a few pages every day serve better than bursts of reading that stop after a week.\n\nThe section closes by asking the reader to review briefly what they have read, and to write three main ideas in their own words before moving on to the quiz.",
    "pdf": "http://127.0.0.1:8005/storage/uploads/sample-section.pdf",
    "read": false,
    "passed": false
  },
  "meta": {
    "book": {
      "id": 2,
      "title": "Foundations of Systematic Thinking",
      "has_own_quiz": false,
      "quiz_button": {
        "visible": false,
        "label": "Take the quiz"
      }
    },
    "position": 1,
    "sections_total": 3
  }
}
```

---

## 6. Reading programmes

A programme is a curated shelf. **General** programmes are open to everyone,
**sequential** ones unlock a book at a time, and **selective** ones are visible
only to readers assigned to them.

The lock is enforced server-side. `unlocked`, `status` and `blocked_by` tell a
client what to render; a client that ignores them and fetches a locked book
gets `403` with the reason. **programs-apis.md** covers the rules in full.

#### Programmes this reader may see

A selective programme is simply absent for anyone not assigned to it, rather than present and forbidden.

```http
GET /api/programs
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`200`

```json
{
  "data": [
    {
      "id": 1,
      "title": "General programme",
      "description": "Open to everyone, and its books may be read in any order.",
      "type": "general",
      "cover": null,
      "enrolled": false,
      "books_count": 3,
      "books_completed": 0,
      "percent": 0
    },
    {
      "id": 2,
      "title": "Sequential path",
      "description": "Each book unlocks once the one before it is finished.",
      "type": "sequential",
      "cover": null,
      "enrolled": false,
      "books_count": 4,
      "books_completed": 0,
      "percent": 0
    }
  ]
}
```

#### One programme, with its books

`unlocked`, `status` and `blocked_by` are decided server-side. A client that ignores them gets a 403 from the reading endpoints.

```http
GET /api/programs/1
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`200`

```json
{
  "data": {
    "id": 1,
    "title": "General programme",
    "description": "Open to everyone, and its books may be read in any order.",
    "type": "general",
    "cover": null,
    "enrolled": false,
    "books": [
      {
        "id": 1,
        "title": "Introduction to Structured Reading",
        "author": "Prepared by the programme administration",
        "cover": "http://127.0.0.1:8005/assets/banner.svg",
        "points": 400,
        "order_index": 0,
        "unlocked": true,
        "status": "available",
        "blocked_by": null,
        "completed": false,
        "sections_passed": 0,
        "sections_total": 3
      },
      {
        "id": 2,
        "title": "Foundations of Systematic Thinking",
        "author": "Prepared by the programme administration",
        "cover": "http://127.0.0.1:8005/assets/banner.svg",
        "points": 400,
        "order_index": 1,
        "unlocked": true,
        "status": "available",
        "blocked_by": null,
        "completed": false,
        "sections_passed": 0,
        "sections_total": 3
      },
      {
        "id": 3,
  …
```

#### Enrol

Enrolling is the reader saying what they are working on. It grants nothing they could not already see.

```http
POST /api/programs/1/enroll
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`200`

```json
{
  "data": {
    "program_id": 1,
    "enrolled": true
  }
}
```

#### Leave a programme

```http
DELETE /api/programs/1/enroll
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`200`

```json
{
  "data": {
    "program_id": 1,
    "enrolled": false
  }
}
```

---

## 7. Reading and examination

Opening the material is recorded explicitly, not inferred from a fetch — a
prefetch or a retry is not a reader. The exam is then fetched and submitted
against whichever endpoint that book's shape calls for.

**quiz-apis.md** documents this area end to end, including what a retake
changes and what it does not.

#### Record that a section was opened

Reading is recorded here rather than by fetching the section: a prefetch or a retry is not a reader.

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

#### Record that a whole-book document was opened

```http
POST /api/books/7/read
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`200`

```json
{
  "data": {
    "id": 7,
    "read": true
  }
}
```

#### Fetch an exam

Questions without the answers. The key is held server-side and never sent before grading.

```http
GET /api/books/7/quiz
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
        }
      ]
    },
    {
      "id": 56,
      "text": "Question 2?",
      "position": 2,
      "options": [
        {
          "id": 166,
          "text": "Option 1",
          "position": 1
        },
        {
          "id": 167,
          "text": "Option 2",
          "position": 2
        },
        {
          "id": 168,
          "text": "Option 3",
          "position": 3
        }
      ]
    },
    {
      "id": 57,
      "text": "Question 3?",
      "position": 3,
      "options": [
        {
          "id": 169,
          "text": "Option 1",
          "position": 1
        },
        {
          "id": 170,
          "text": "Option 2",
          "position": 2
        },
        {
          "id": 171,
          "text": "Option 3",
  …
```

#### A failing attempt

`correct_options` is null and `incorrect_questions` names what to go back and read. A free resit plus the answer key would make the first attempt a way of being told them.

```http
POST /api/books/7/quiz
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
Content-Type: application/json

{
  "answers": {
    "55": 163,
    "56": 166,
    "57": 169
  }
}
```

`200`

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
  "new_certificates": [],
  "stats": {
    "points": 0,
    "sections_passed": 0,
    "sections_total": 19,
    "books_completed": 0,
    "books_total": 8,
    "badges_earned": 0,
    "certificates": 0
  }
}
```

#### A passing attempt

One response carries everything the pass produced: the points, any badges, any certificates, and the reader's recalculated standing.

```http
POST /api/books/7/quiz
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
Content-Type: application/json

{
  "answers": {
    "55": 164,
    "56": 167,
    "57": 170
  }
}
```

`200`

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
  "incorrect_questions": [],
  "new_badges": [
    {
      "id": 3,
      "name": "Book Finisher",
      "description": "Completed every section of one book.",
      "image": "http://127.0.0.1:8005/assets/badge.png",
      "criteria_type": "books_completed",
      "criteria_value": 1,
      "position": 2
    }
  ],
  "new_certificates": [
    {
      "id": 29,
      "serial": "CERT-2026-ULZYKD",
      "title": "A book read whole",
      "issued_at": "2026-10-16",
      "verification_url": "http://127.0.0.1:8005/api/certificates/verify/b383064b-7591-4881-9e1b-8d6a1bbcdbc1"
    }
  ],
  "stats": {
    "points": 500,
    "sections_passed": 0,
    "sections_total": 19,
    "books_completed": 1,
    "books_total": 8,
    "badges_earned": 1,
    "certificates": 1
  }
}
```

#### A retake

Recorded, but paid nothing. The certificate is re-dated rather than reissued, so a serial already shared keeps verifying.

```http
POST /api/books/7/quiz
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
Content-Type: application/json

{
  "answers": {
    "55": 164,
    "56": 167,
    "57": 170
  }
}
```

`200`

```json
{
  "score": 3,
  "total": 3,
  "passed": true,
  "points_awarded": 0,
  "correct_options": {
    "55": 164,
    "56": 167,
    "57": 170
  },
  "incorrect_questions": [],
  "new_badges": [],
  "new_certificates": [],
  "stats": {
    "points": 500,
    "sections_passed": 0,
    "sections_total": 19,
    "books_completed": 1,
    "books_total": 8,
    "badges_earned": 1,
    "certificates": 1
  }
}
```

#### A section exam

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

---

## 8. Progress and standing

#### The home screen

```http
GET /api/dashboard
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`200`

```json
{
  "stats": {
    "points": 500,
    "sections_passed": 0,
    "sections_total": 19,
    "books_completed": 1,
    "books_total": 8,
    "badges_earned": 1,
    "certificates": 1
  },
  "unread_announcements": 7,
  "badges": [
    {
      "id": 3,
      "name": "Book Finisher",
      "description": "Completed every section of one book.",
      "image": "http://127.0.0.1:8005/assets/badge.png",
      "criteria_type": "books_completed",
      "criteria_value": 1,
      "position": 2,
      "earned": true,
      "awarded_at": "2026-10-16 17:58:08"
    }
  ],
  "certificates": [
    {
      "id": 29,
      "serial": "CERT-2026-ULZYKD",
      "title": "A book read whole",
      "issued_at": "2026-10-16",
      "level": null,
      "verification_url": "http://127.0.0.1:8005/api/certificates/verify/b383064b-7591-4881-9e1b-8d6a1bbcdbc1"
    }
  ],
  "honor_board": [
    {
      "rank": 1,
      "id": 2,
      "name": "عبدالله سالم",
      "avatar": "http://127.0.0.1:8005/assets/avatar-1.svg",
      "points": 1800,
      "level": "Third Level"
    },
    {
      "rank": 2,
      "id": 3,
      "name": "محمد احمد",
  …
```

#### The full progress picture

Resolved from three bulk reads rather than a query per section, so it stays a handful of queries however large the catalogue grows.

```http
GET /api/progress
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`200`

```json
{
  "stats": {
    "points": 500,
    "sections_passed": 0,
    "sections_total": 19,
    "books_completed": 1,
    "books_total": 8,
    "badges_earned": 1,
    "certificates": 1
  },
  "levels": [
    {
      "id": 1,
      "name": "First Level",
      "books_total": 4,
      "books_completed": 1,
      "sections_total": 7,
      "sections_passed": 0,
      "percent": 0,
      "certificate": null,
      "books": [
        {
          "id": 1,
          "title": "Introduction to Structured Reading",
          "points": 400,
          "pages": 140,
          "has_own_quiz": true,
          "sections_total": 3,
          "sections_read": 1,
          "sections_passed": 0,
          "read_percent": 33,
          "percent": 0,
          "completed": false,
          "sections": [
            {
              "id": 1,
              "title": "Why We Read Regularly",
              "position": 1,
              "read": true,
              "passed": false,
              "attempts": 0,
              "best_score": null,
              "questions": 0,
              "passed_at": null
            },
            {
              "id": 2,
  …
```

#### Badges

```http
GET /api/badges
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`200`

```json
{
  "data": [
    {
      "id": 1,
      "name": "The Persistent",
      "description": "Passed your first section of the programme.",
      "image": "http://127.0.0.1:8005/assets/badge.png",
      "criteria_type": "sections_passed",
      "criteria_value": 1,
      "position": 0,
      "earned": false
    },
    {
      "id": 2,
      "name": "The Regular Reader",
      "description": "Passed five sections.",
      "image": "http://127.0.0.1:8005/assets/badge.png",
      "criteria_type": "sections_passed",
      "criteria_value": 5,
      "position": 1,
      "earned": false
    },
    {
      "id": 3,
      "name": "Book Finisher",
      "description": "Completed every section of one book.",
      "image": "http://127.0.0.1:8005/assets/badge.png",
      "criteria_type": "books_completed",
      "criteria_value": 1,
      "position": 2,
      "earned": true
    },
    {
      "id": 4,
      "name": "Level Reader",
      "description": "Completed two whole books.",
      "image": "http://127.0.0.1:8005/assets/badge.png",
      "criteria_type": "books_completed",
      "criteria_value": 2,
      "position": 3,
  …
```

#### The honour board

`scope` may be `all`, `month` or `year`. The scoped boards sum the attempts in that window rather than repeating the lifetime total.

```http
GET /api/honor-board?scope=all&limit=5
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`200`

```json
{
  "data": [
    {
      "rank": 1,
      "id": 2,
      "name": "عبدالله سالم",
      "avatar": "http://127.0.0.1:8005/assets/avatar-1.svg",
      "level": "Third Level",
      "books": 4,
      "points": 1800
    },
    {
      "rank": 2,
      "id": 3,
      "name": "محمد احمد",
      "avatar": "http://127.0.0.1:8005/assets/avatar-2.svg",
      "level": "Second Level",
      "books": 3,
      "points": 1500
    },
    {
      "rank": 3,
      "id": 4,
      "name": "خالد يوسف",
      "avatar": "http://127.0.0.1:8005/assets/avatar-3.svg",
      "level": "Second Level",
      "books": 3,
      "points": 1350
    },
    {
      "rank": 4,
      "id": 5,
      "name": "سعود ناصر",
      "avatar": "http://127.0.0.1:8005/assets/avatar-1.svg",
      "level": "First Level",
      "books": 2,
      "points": 1200
    },
    {
      "rank": 5,
      "id": 6,
      "name": "فهد عبدالعزيز",
      "avatar": "http://127.0.0.1:8005/assets/avatar-2.svg",
      "level": "First Level",
      "books": 2,
      "points": 1050
    }
  ],
  "meta": {
    "scope": "all"
  }
}
```

#### Certificates held

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
      "id": 29,
      "serial": "CERT-2026-ULZYKD",
      "title": "A book read whole",
      "issued_at": "2026-10-16",
      "level": null,
      "book": {
        "id": 7,
        "title": "A book read whole",
        "author": "The author",
        "description": "Read as one document.",
        "cover": null,
        "pdf": "http://127.0.0.1:8005/storage/uploads/sample-section.pdf",
        "has_own_quiz": true,
        "quiz_button": {
          "visible": true,
          "label": "Exam on the whole book"
        },
        "exam": {
          "scope": "book",
          "button": {
            "visible": true,
            "label": "Exam on the whole book"
          },
          "questions_total": 3
        },
        "pages": 120,
        "points": 500,
        "position": 10
      },
      "verification_url": "http://127.0.0.1:8005/api/certificates/verify/b383064b-7591-4881-9e1b-8d6a1bbcdbc1"
    }
  ]
}
```

#### Every certificate, held or not

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
  …
```

#### Verify a certificate

Public, and behind the QR code printed on every certificate.

```http
GET /api/certificates/verify/b383064b-7591-4881-9e1b-8d6a1bbcdbc1
Accept: application/json
Accept-Language: en
```

`200`

```json
{
  "valid": true,
  "serial": "CERT-2026-ULZYKD",
  "holder": "A Reader",
  "title": "A book read whole",
  "level": null,
  "issued_at": "2026-10-16"
}
```

---

## 9. Notifications

One inbox carries both the dashboard's announcements and the automatic notices
a reader earns. Categories: `certificate` and `badge` are issued automatically;
`program`, `exam`, `results`, `honor` and `general` are posted from the
dashboard. A reader can mute any of them.

#### The feed

Broadcast announcements plus the ones addressed to this reader. `meta.unread` is what the bell shows.

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
      "id": 72,
      "tag": "Badge",
      "title": "New badge: Book Finisher",
      "body": "Congratulations — “Book Finisher” has been added to your badges.",
      "image": "http://127.0.0.1:8005/assets/badge.png",
      "category": "badge",
      "published_at": "2026-10-16T17:58:08+00:00",
      "read": false
    },
    {
      "id": 73,
      "tag": "Certificate",
      "title": "Your certificate for “A book read whole” has been issued",
      "body": "Congratulations — your certificate for “A book read whole” has been issued with serial CERT-2026-ULZYKD. You will find it on the certificates page.",
      "image": null,
      "category": "certificate",
      "published_at": "2026-10-16T17:58:08+00:00",
      "read": false
    },
    {
      "id": 1,
      "tag": "New programme",
  …
```

#### One announcement

```http
GET /api/announcements/72
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`200`

```json
{
  "data": {
    "id": 72,
    "tag": "Badge",
    "title": "New badge: Book Finisher",
    "body": "Congratulations — “Book Finisher” has been added to your badges.",
    "image": "http://127.0.0.1:8005/assets/badge.png",
    "category": "badge",
    "published_at": "2026-10-16T17:58:08+00:00",
    "read": true
  }
}
```

#### Mark one read

```http
POST /api/announcements/72/read
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`200`

```json
{
  "data": {
    "id": 72,
    "read": true
  },
  "meta": {
    "unread": 6
  }
}
```

#### Mark everything read

```http
POST /api/announcements/read-all
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`200`

```json
{
  "message": "All notifications marked as read."
}
```

#### What this reader has muted

```http
GET /api/notification-preferences
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`200`

```json
{
  "data": [
    {
      "category": "program",
      "enabled": true
    },
    {
      "category": "exam",
      "enabled": true
    },
    {
      "category": "results",
      "enabled": true
    },
    {
      "category": "honor",
      "enabled": true
    },
    {
      "category": "certificate",
      "enabled": true
    },
    {
      "category": "badge",
      "enabled": true
    },
    {
      "category": "general",
      "enabled": true
    }
  ]
}
```

#### Mute a category

```http
PUT /api/notification-preferences
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
Content-Type: application/json

{
  "category": "honor",
  "enabled": false
}
```

`200`

```json
{
  "data": [
    {
      "category": "program",
      "enabled": true
    },
    {
      "category": "exam",
      "enabled": true
    },
    {
      "category": "results",
      "enabled": true
    },
    {
      "category": "honor",
      "enabled": false
    },
    {
      "category": "certificate",
      "enabled": true
    },
    {
      "category": "badge",
      "enabled": true
    },
    {
      "category": "general",
      "enabled": true
    }
  ]
}
```

---

## 10. The account

#### Update the profile

```http
PUT /api/profile
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
Content-Type: application/json

{
  "name": "A Reader",
  "city": "Kuwait City",
  "locale": "en"
}
```

`200`

```json
{
  "data": {
    "id": 11,
    "name": "A Reader",
    "email": "api1792173473@himam.test",
    "gender": "male",
    "city": "Kuwait City",
    "country": "KW",
    "accepts_email": true,
    "avatar": null,
    "role": "student",
    "locale": "en",
    "points": 500,
    "level": null,
    "created_at": "2026-10-16T17:57:55+00:00"
  }
}
```

#### Change the password

```http
PUT /api/profile/password
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
Content-Type: application/json

{
  "current_password": "password123",
  "password": "password123",
  "password_confirmation": "password123"
}
```

`200`

```json
{
  "message": "Password updated."
}
```

Changing the password revokes every other token on the account, so the
caller's own token keeps working and anything else signed in is turned out.

---

## 11. Errors in practice

#### Something that does not exist

```http
GET /api/books/99999
Accept: application/json
Accept-Language: en
```

`404`

```json
{
  "message": "No query results for model [App\\Models\\Book] 99999",
  "exception": "Symfony\\Component\\HttpKernel\\Exception\\NotFoundHttpException",
  "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Exceptions\\Handler.php",
  "line": 668,
  "trace": [
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Exceptions\\Handler.php",
      "line": 616,
      "function": "prepareException",
  …
```

#### Wrong credentials

```http
POST /api/auth/login
Accept: application/json
Accept-Language: en
Content-Type: application/json

{
  "email": "api1792173473@himam.test",
  "password": "wrong"
}
```

`422`

```json
{
  "message": "These credentials do not match our records.",
  "errors": {
    "email": [
      "These credentials do not match our records."
    ]
  }
}
```

#### A malformed body

```http
POST /api/books/7/quiz
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

#### An exam that moved to the book

Book 1 is examined as a whole, so its sections carry no quiz.

```http
GET /api/sections/1/quiz
Accept: application/json
Accept-Language: en
Authorization: Bearer <token>
```

`404`

```json
{
  "message": "This book is examined as a whole, so its sections have no exam of their own.",
  "exception": "Symfony\\Component\\HttpKernel\\Exception\\NotFoundHttpException",
  "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Application.php",
  "line": 1437,
  "trace": [
    {
      "file": "D:\\xa73\\htdocs\\Himam-Backend\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\helpers.php",
      "line": 67,
      "function": "abort",
  …
```

#### No token

```http
GET /api/progress
Accept: application/json
Accept-Language: en
```

`401`

```json
{
  "message": "Unauthenticated."
}
```

---

## 12. The admin API

Everything under `/api/admin` needs a token on an account whose `role` is
`admin`. A reader's token gets `403`.

The request bodies below are read from each controller's own validation rules.
A field marked *translatable* takes an object keyed by language code —
`{"ar": "…", "en": "…"}` — and at least one language must be filled unless the
rules say otherwise.

### Books

| Field | Rules |
|---|---|
| `title` | translatable object, e.g. {"ar": "…", "en": "…"} — at least one language |
| `author` | translatable object, e.g. {"ar": "…", "en": "…"} |
| `description` | translatable object, e.g. {"ar": "…", "en": "…"} |
| `pdf` | translatable object, e.g. {"ar": "…", "en": "…"} |
| `quiz_button_label` | translatable object, e.g. {"ar": "…", "en": "…"} |
| `level_id` | required, exists:levels,id |
| `cover` | nullable, string, max:255 |
| `pages` | nullable, integer, min:0 |
| `points` | nullable, integer, min:0 |
| `position` | nullable, integer, min:0 |
| `is_published` | nullable, boolean |
| `quiz_button_visible` | nullable, boolean |

`pdf` and `quiz_button_label` are translatable, so a programme taught in four
languages can carry four documents and four button labels. `quiz_button_visible`
hides the exam without deleting its questions.

### Sections

| Field | Rules |
|---|---|
| `title` | translatable object, e.g. {"ar": "…", "en": "…"} — at least one language |
| `body` | translatable object, e.g. {"ar": "…", "en": "…"} |
| `pdf` | translatable object, e.g. {"ar": "…", "en": "…"} |
| `position` | nullable, integer, min:0 |

### Questions

`POST /api/admin/sections/{section}/questions` writes a question for one
section; `POST /api/admin/books/{book}/questions` writes one for the book
itself, which is what moves the exam from the sections to the book. Both take
the same body.

| Field | Rules |
|---|---|
| `text` | translatable object, e.g. {"ar": "…", "en": "…"} — at least one language |
| `position` | nullable, integer, min:0 |
| `options` | required, array, min:2 |
| `options.*.is_correct` | required, boolean |
| `options.*.text` | required, array |

Exactly one option must be marked correct, and every option needs text in at
least one language. Updating a question replaces its options wholesale rather
than editing them in place, which would leave stale rows behind when an author
removes a choice.

### Levels

| Field | Rules |
|---|---|
| `name` | translatable object, e.g. {"ar": "…", "en": "…"} — at least one language |
| `description` | translatable object, e.g. {"ar": "…", "en": "…"} |
| `position` | nullable, integer, min:0 |
| `is_active` | nullable, boolean |

### Reading programmes

| Field | Rules |
|---|---|
| `description` | translatable object, e.g. {"ar": "…", "en": "…"} |
| `type` | required, sometimes, one of a fixed set |
| `cover` | nullable, string, max:255 |
| `position` | nullable, integer, min:0 |
| `is_public` | sometimes, boolean |
| `is_active` | sometimes, boolean |

`PUT /api/admin/programs/{program}/books` sets the shelf and its order;
`PUT /api/admin/programs/{program}/members` sets who a selective programme is
for.

| Field | Rules |
|---|---|
| `book_ids` | present, array |
| `book_ids.*` | integer, exists:books,id |

### Badges

| Field | Rules |
|---|---|
| `description` | translatable object, e.g. {"ar": "…", "en": "…"} |
| `image` | nullable, string, max:255 |
| `criteria_type` | required, sometimes, one of a fixed set |
| `criteria_value` | nullable, integer, min:0 |
| `position` | nullable, integer, min:0 |
| `is_active` | nullable, boolean |

`criteria_type` is `sections_passed`, `books_completed`, `points` or `manual`.
Automatic badges are awarded when a quiz is graded; a manual one is awarded with
`POST /api/admin/badges/{badge}/award` and taken back with `revoke`.

### Announcements

| Field | Rules |
|---|---|
| `tag` | translatable object, e.g. {"ar": "…", "en": "…"} |
| `body` | translatable object, e.g. {"ar": "…", "en": "…"} |
| `image` | nullable, string, max:255 |
| `category` | required, sometimes, one of a fixed set |
| `user_id` | nullable, exists:users,id |
| `published_at` | nullable, date |

Leaving `user_id` empty broadcasts to everyone; setting it addresses one
reader. `POST /api/admin/announcements/{announcement}/publish` toggles whether
it is live.

### Slides

| Field | Rules |
|---|---|
| `caption` | translatable object, e.g. {"ar": "…", "en": "…"} |
| `screen` | required, sometimes, string, max:60 |
| `image` | required, sometimes, string, max:255 |
| `href` | nullable, string, max:255 |
| `position` | nullable, integer, min:0 |
| `is_active` | nullable, boolean |

### Content pages

| Field | Rules |
|---|---|
| `body` | translatable object, e.g. {"ar": "…", "en": "…"} |
| `slug` | required, sometimes, string, max:100 |
| `is_published` | sometimes, boolean |

### Frequently asked questions

| Field | Rules |
|---|---|
| `category` | nullable, string, max:100 |
| `position` | nullable, integer, min:0 |
| `is_active` | sometimes, boolean |

### Contact details

| Field | Rules |
|---|---|
| `address` | translatable object, e.g. {"ar": "…", "en": "…"} |
| `working_hours` | translatable object, e.g. {"ar": "…", "en": "…"} |
| `note` | translatable object, e.g. {"ar": "…", "en": "…"} |
| `email` | nullable, email, max:255 |
| `phone` | nullable, string, max:50 |
| `whatsapp` | nullable, string, max:50 |
| `website` | nullable, url, max:255 |
| `social` | nullable, array |
| `social.*.platform` | required, string, max:50 |
| `social.*.url` | required, url, max:255 |

### Readers

| Field | Rules |
|---|---|
| `name` | sometimes, string, max:255 |
| `email` | sometimes, email, max:255, users, email |
| `city` | nullable, string, max:120 |
| `avatar` | nullable, string, max:255 |
| `role` | sometimes, student, admin, one of a fixed set |
| `locale` | sometimes, one of a fixed set |
| `level_id` | nullable, exists:levels,id |
| `points` | sometimes, integer, min:0 |
| `password` | nullable, string, min:8 |

`POST /api/admin/users/{user}/recalculate` re-derives a reader's points,
badges and certificates from their attempts — for use after content changes
underneath them.

### Certificates

| Field | Rules |
|---|---|
| `user_id` | required, exists:users,id |
| `level_id` | nullable, exists:levels,id |
| `book_id` | nullable, exists:books,id |

Issuing a certificate by hand is for cases the automatic rules do not cover.
Deleting one is permanent, and any copy already shared stops verifying.

### Languages

| Field | Rules |
|---|---|
| `code` | sometimes, required, string, max:10 |
| `name` | sometimes, required, string, max:80 |
| `english_name` | sometimes, required, string, max:80 |
| `direction` | sometimes, required, ltr, rtl, one of a fixed set |
| `is_active` | nullable, boolean |
| `is_default` | nullable, boolean |
| `position` | nullable, integer, min:0 |

Adding a language here makes it available to every translatable field at
once, with no deploy and no migration.

### Media

`GET /api/admin/media` lists what has been uploaded, `POST` uploads a file as
`multipart/form-data`, and `DELETE` removes one by path. Uploads live on the
configured media disk; on an ephemeral host they do not survive a deploy unless
object storage is configured.

---

## 13. Every route


### Reader API — 42 endpoints

| Method | Path | Access |
|---|---|---|
| `GET` | `/api/announcements` | reader |
| `POST` | `/api/announcements/read-all` | reader |
| `GET` | `/api/announcements/{announcement}` | reader |
| `POST` | `/api/announcements/{announcement}/read` | reader |
| `POST` | `/api/auth/forgot-password` | public |
| `POST` | `/api/auth/login` | public |
| `POST` | `/api/auth/logout` | reader |
| `GET` | `/api/auth/me` | reader |
| `POST` | `/api/auth/register` | public |
| `POST` | `/api/auth/reset-password` | public |
| `GET` | `/api/badges` | public |
| `GET` | `/api/books` | public |
| `GET` | `/api/books/{book}` | public |
| `GET` | `/api/books/{book}/quiz` | reader |
| `POST` | `/api/books/{book}/quiz` | reader |
| `POST` | `/api/books/{book}/read` | reader |
| `GET` | `/api/certificates` | reader |
| `GET` | `/api/certificates/available` | reader |
| `GET` | `/api/certificates/verify/{code}` | public |
| `GET` | `/api/certificates/{certificate}` | reader |
| `GET` | `/api/contact` | public |
| `GET` | `/api/countries` | public |
| `GET` | `/api/dashboard` | reader |
| `GET` | `/api/faqs` | public |
| `GET` | `/api/honor-board` | public |
| `GET` | `/api/levels` | public |
| `GET` | `/api/locales` | public |
| `GET` | `/api/notification-preferences` | reader |
| `PUT` | `/api/notification-preferences` | reader |
| `GET` | `/api/pages/{slug}` | public |
| `PUT` | `/api/profile` | reader |
| `PUT` | `/api/profile/password` | reader |
| `GET` | `/api/programs` | public |
| `GET` | `/api/programs/{program}` | public |
| `POST` | `/api/programs/{program}/enroll` | reader |
| `DELETE` | `/api/programs/{program}/enroll` | reader |
| `GET` | `/api/progress` | reader |
| `GET` | `/api/sections/{section}` | reader |
| `GET` | `/api/sections/{section}/quiz` | reader |
| `POST` | `/api/sections/{section}/quiz` | reader |
| `POST` | `/api/sections/{section}/read` | reader |
| `GET` | `/api/slides/{screen}` | public |


### Admin API — 72 endpoints

| Method | Path | Access |
|---|---|---|
| `GET` | `/api/admin/announcements` | admin |
| `POST` | `/api/admin/announcements` | admin |
| `GET` | `/api/admin/announcements/{announcement}` | admin |
| `PUT` | `/api/admin/announcements/{announcement}` | admin |
| `DELETE` | `/api/admin/announcements/{announcement}` | admin |
| `POST` | `/api/admin/announcements/{announcement}/publish` | admin |
| `GET` | `/api/admin/badges` | admin |
| `POST` | `/api/admin/badges` | admin |
| `PUT` | `/api/admin/badges/{badge}` | admin |
| `DELETE` | `/api/admin/badges/{badge}` | admin |
| `POST` | `/api/admin/badges/{badge}/award` | admin |
| `POST` | `/api/admin/badges/{badge}/revoke` | admin |
| `GET` | `/api/admin/books` | admin |
| `POST` | `/api/admin/books` | admin |
| `GET` | `/api/admin/books/{book}` | admin |
| `PUT` | `/api/admin/books/{book}` | admin |
| `DELETE` | `/api/admin/books/{book}` | admin |
| `GET` | `/api/admin/books/{book}/questions` | admin |
| `POST` | `/api/admin/books/{book}/questions` | admin |
| `GET` | `/api/admin/books/{book}/sections` | admin |
| `POST` | `/api/admin/books/{book}/sections` | admin |
| `GET` | `/api/admin/certificates` | admin |
| `POST` | `/api/admin/certificates` | admin |
| `DELETE` | `/api/admin/certificates/{certificate}` | admin |
| `GET` | `/api/admin/contact` | admin |
| `PUT` | `/api/admin/contact` | admin |
| `GET` | `/api/admin/faqs` | admin |
| `POST` | `/api/admin/faqs` | admin |
| `POST` | `/api/admin/faqs/reorder` | admin |
| `PUT` | `/api/admin/faqs/{faq}` | admin |
| `DELETE` | `/api/admin/faqs/{faq}` | admin |
| `GET` | `/api/admin/levels` | admin |
| `POST` | `/api/admin/levels` | admin |
| `GET` | `/api/admin/levels/{level}` | admin |
| `PUT` | `/api/admin/levels/{level}` | admin |
| `DELETE` | `/api/admin/levels/{level}` | admin |
| `GET` | `/api/admin/locales` | admin |
| `POST` | `/api/admin/locales` | admin |
| `PUT` | `/api/admin/locales/{locale}` | admin |
| `DELETE` | `/api/admin/locales/{locale}` | admin |
| `GET` | `/api/admin/media` | admin |
| `POST` | `/api/admin/media` | admin |
| `DELETE` | `/api/admin/media` | admin |
| `GET` | `/api/admin/pages` | admin |
| `POST` | `/api/admin/pages` | admin |
| `GET` | `/api/admin/pages/{page}` | admin |
| `PUT` | `/api/admin/pages/{page}` | admin |
| `DELETE` | `/api/admin/pages/{page}` | admin |
| `GET` | `/api/admin/programs` | admin |
| `POST` | `/api/admin/programs` | admin |
| `GET` | `/api/admin/programs/{program}` | admin |
| `PUT` | `/api/admin/programs/{program}` | admin |
| `DELETE` | `/api/admin/programs/{program}` | admin |
| `PUT` | `/api/admin/programs/{program}/books` | admin |
| `PUT` | `/api/admin/programs/{program}/members` | admin |
| `PUT` | `/api/admin/questions/{question}` | admin |
| `DELETE` | `/api/admin/questions/{question}` | admin |
| `GET` | `/api/admin/sections/{section}` | admin |
| `PUT` | `/api/admin/sections/{section}` | admin |
| `DELETE` | `/api/admin/sections/{section}` | admin |
| `GET` | `/api/admin/sections/{section}/questions` | admin |
| `POST` | `/api/admin/sections/{section}/questions` | admin |
| `GET` | `/api/admin/slides` | admin |
| `POST` | `/api/admin/slides` | admin |
| `PUT` | `/api/admin/slides/{slide}` | admin |
| `DELETE` | `/api/admin/slides/{slide}` | admin |
| `GET` | `/api/admin/stats` | admin |
| `GET` | `/api/admin/users` | admin |
| `GET` | `/api/admin/users/{user}` | admin |
| `PUT` | `/api/admin/users/{user}` | admin |
| `DELETE` | `/api/admin/users/{user}` | admin |
| `POST` | `/api/admin/users/{user}/recalculate` | admin |
