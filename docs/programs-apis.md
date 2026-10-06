# Reading programmes

A programme is an ordered container of books with its own audience. Its type
answers two questions that do not vary together — **who may see it**, and **in
what order its books open**.

Every sample below is a real response from the API.

Base URL: `https://himam-back.onrender.com/api`

| Type | Arabic | Who sees it | Book access |
| --- | --- | --- | --- |
| `general` | عام | Everyone | All books open, any order |
| `sequential` | منهجي | Everyone, unless the admin unlists it | Book *n+1* opens once book *n* is finished |
| `selective` | انتقائي | Only the readers an admin assigns | Hidden entirely from everyone else |

| Endpoint | Auth | Purpose |
| --- | --- | --- |
| `GET /programs` | optional | Programmes this reader may see |
| `GET /programs/{id}` | optional | Its books in order, with lock state |
| `POST /programs/{id}/enroll` | reader | Join |
| `DELETE /programs/{id}/enroll` | reader | Leave |
| `GET/POST/PUT/DELETE /admin/programs` | admin | Manage programmes |
| `PUT /admin/programs/{id}/books` | admin | Set the books and their order |
| `PUT /admin/programs/{id}/members` | admin | Assign readers (selective only) |

---

## Visible programmes — `GET /programs`

Public. Sending no token shows what an anonymous visitor sees, which is the
point of the general programme being public and the selective one not existing
as far as they are concerned.

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
      "type": "sequential",
      "enrolled": false,
      "books_count": 4,
      "books_completed": 0,
      "percent": 0
    }
  ]
}
```

`percent` counts **sections**, not books, so a long book in progress registers
as progress rather than as nothing.

---

## One programme — `GET /programs/{id}`

Books in the programme's own order, each with whether it is open and what is
blocking it.

```json
{
  "data": {
    "id": 2,
    "title": "Sequential path",
    "type": "sequential",
    "enrolled": false,
    "books": [
      {
        "id": 1,
        "title": "Introduction to Structured Reading",
        "author": "Prepared by the programme administration",
        "cover": "assets/banner.svg",
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
        "order_index": 1,
        "unlocked": false,
        "status": "locked",
        "blocked_by": 1,
        "completed": false,
        "sections_passed": 0,
        "sections_total": 3
      }
    ],
    "books_completed": 0,
    "percent": 0
  }
}
```

| Field | Meaning |
| --- | --- |
| `unlocked` | Whether this reader may open it now |
| `status` | `available` · `in_progress` · `completed` · `locked` |
| `blocked_by` | The **book id** that has to be finished first, or `null` |

`blocked_by` is an id rather than a title so the screen can look up the title
itself and the two can never disagree about what the book is called in the
current language. It is what lets the app say *"finish X first"* instead of
only drawing a padlock.

A programme the reader was not assigned returns **404**, not 403 — they should
not learn that it exists.

---

## The lock is enforced on the server

A padlock drawn in the app is a courtesy to the reader. It is not a control,
because the request can be made without the app. Every endpoint that hands over
content checks first:

```
GET  /books/{id}
GET  /sections/{id}
GET  /sections/{id}/quiz
POST /sections/{id}/quiz
```

Calling any of them for a book the chain has not reached:

```json
{
  "message": "Finish the previous book in this programme first.",
  "reason": "locked_previous"
}
```

returned with **403**. For a book that exists only inside a selective programme
the reader was never assigned, the answer is **404** instead.

### A book may be in several programmes, and one grant is enough

A book locked in a sequential programme is still readable if a general
programme also offers it. The stricter reading — any lock wins — would mean
that adding a book to one programme silently withdrew it from the readers of
another.

**Books in no programme are unaffected**, so the existing catalogue behaves
exactly as it did before programmes existed.

### A finished book never re-locks

If an admin reorders a programme and puts an unfinished book in front of one
the reader has already completed, the completed book stays open. Reordering
must not take back what someone has already read.

---

## Enrolling — `POST` / `DELETE /programs/{id}/enroll`

```json
{ "data": { "program_id": 1, "enrolled": true } }
```

**Enrolling is a bookmark, not a permission.** It says "this is what I am
working on" so a reader's own list stays short. It grants nothing they could
not already reach, and enrolling in a programme they cannot see returns `404` —
which is also how a selective programme stays hidden.

Enrolling twice is harmless.

**Leaving removes only a self-enrolment.** An administrator's assignment is not
the reader's to undo. Progress survives either way: leaving a programme should
never cost someone the sections they have passed.

This is why the join table records *how* a reader got there. Without that
distinction a reader could enrol their way into a programme they were never
meant to find.

---

## Admin

```
GET    /admin/programs
POST   /admin/programs          { title{}, description{}, type, is_public, is_active }
GET    /admin/programs/{id}
PUT    /admin/programs/{id}
DELETE /admin/programs/{id}

PUT    /admin/programs/{id}/books     { "book_ids": [3, 1, 2] }
PUT    /admin/programs/{id}/members   { "user_ids": [4, 7] }
```

`title` and `description` are the usual `locale => text` maps.

**Books are sent as a whole list, and position in the array is the order.** For
a sequential programme that order is what "the previous book" means, so
applying a reshuffle one book at a time would leave readers looking at an order
nobody intended.

`members` returns **422** on a programme that is not selective — the other
types have no member list to edit.

`is_public` is only the admin's to set on a **sequential** programme: a general
one is always listed and a selective one never is, so the server settles the
flag itself for those two.

---

## A note on the data model

The specification suggested a `UserBookProgress` table with a per-book status.
There isn't one, deliberately.

Whether a reader has finished a book is already decided by which of its
sections they have passed — that is what issues certificates and awards badges
today. A second stored answer would become two sources of truth that disagree
the first time a section is added to a book someone had already "completed".
Status is computed from the existing progress instead.

---

The full Postman collection is in [`postman/`](../postman); the **Reading
programs** folder covers the reader side, and the programme requests in
**Admin** cover the rest.
