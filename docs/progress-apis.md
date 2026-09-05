# Progress, badges and the honour board

The five endpoints behind the three reader screens — what each returns and which
tab it fills. Every sample below is a real response from the live API.

Base URL: `https://himam-back.onrender.com/api`

| Screen | Tabs | Endpoint |
| --- | --- | --- |
| الأوسمة | الجميع / المتاحة | `GET /badges` |
| لوحة الشرف | هذا الشهر / على العام | `GET /honor-board?scope=` |
| الشهادات | الحاصل عليها | `GET /certificates` |
| الشهادات | المتاحة | `GET /certificates/available` |
| الرئيسية | — | `GET /dashboard` |

---

## الأوسمة — `GET /badges`

Token optional.

Returns **every** active badge, each with an `earned` flag for the signed-in
reader. Locked badges stay in the list rather than being filtered out — the
screen shows them greyed as motivation.

One call fills both tabs: **الجميع** renders the whole array, **المتاحة**
filters to `earned: false`.

| Field | Meaning |
| --- | --- |
| `earned` | Whether this reader holds it. `false` for everyone when no token is sent. |
| `criteria_type` | `sections_passed` or `books_completed` — what it is awarded for. |
| `criteria_value` | The threshold. Pair it with the reader's stats to show «4 من 5» on a locked badge. |

```json
{
  "data": [
    {
      "id": 1,
      "name": "The Persistent",
      "description": "Passed your first section of the programme.",
      "image": "assets/badge.png",
      "criteria_type": "sections_passed",
      "criteria_value": 1,
      "position": 0,
      "earned": true
    },
    {
      "id": 7,
      "name": "The Resolute",
      "criteria_type": "books_completed",
      "criteria_value": 6,
      "earned": false
    }
  ]
}
```

---

## لوحة الشرف — `GET /honor-board`

Public.

Readers ranked by points. `scope` is what separates the two tabs — and it is a
real recalculation, not a relabelling: the monthly and yearly boards sum only
the quiz attempts inside that window, so «هذا الشهر» never just repeats the
all-time order.

| Query | Values |
| --- | --- |
| `scope` | `month` · `year` · `all` (default) |
| `limit` | Default 20, capped at 100 |

```json
{
  "data": [
    {
      "rank": 1,
      "id": 2,
      "name": "عبدالله سالم",
      "avatar": "assets/avatar-1.svg",
      "level": "Third Level",
      "books": 4,
      "points": 1800
    }
  ],
  "meta": { "scope": "month" }
}
```

`points` means points *within the scope*, so the number under a name on the
monthly board is that month's total — not the reader's lifetime figure.

---

## الشهادات، الحاصل عليها — `GET /certificates`

Token required. Only what the reader actually holds.

```json
{
  "data": [{
    "id": 3,
    "serial": "CERT-2026-RQEHZP",
    "title": "First Level",
    "issued_at": "2026-09-05",
    "level": { "id": 1, "name": "First Level" },
    "book": null,
    "verification_url": ".../certificates/verify/315352dd-…"
  }]
}
```

`verification_url` is public and is what the QR code on a printed certificate
points at — it confirms the holder and serial without exposing contact details.

---

## الشهادات، المتاحة — `GET /certificates/available`

Token required.

Every certificate the programme can issue, earned or not, each with how far
along the reader is on the ones still ahead. This was the one gap: the
«المتاحة» tab had no endpoint until it was added.

It can render the whole tab pair on its own if you prefer a single request.

```json
{
  "data": [
    {
      "level": { "id": 1, "name": "First Level" },
      "earned": true,
      "serial": "CERT-2026-RQEHZP",
      "issued_at": "2026-09-05",
      "sections_passed": 6,
      "sections_total": 6,
      "percent": 100
    },
    {
      "level": { "id": 2, "name": "Second Level" },
      "earned": false,
      "serial": null,
      "sections_passed": 4,
      "sections_total": 6,
      "percent": 67
    }
  ]
}
```

---

## الرئيسية — `GET /dashboard`

Token required.

One request for the whole home screen, so it doesn't need four. Returns the
headline counters plus short previews of the three collections above.

| Key | Contents |
| --- | --- |
| `stats` | `points`, `sections_passed` / `sections_total`, `books_completed` / `books_total`, `badges_earned`, `certificates` |
| `badges` | 4 most recent |
| `certificates` | Earned, newest first |
| `honor_board` | Top 5, all-time scope |
| `unread_announcements` | Count for the bell badge |

```json
{
  "points": 1500,
  "sections_passed": 10,  "sections_total": 18,
  "books_completed": 3,   "books_total": 6,
  "badges_earned": 5,
  "certificates": 1
}
```

---

## Passing a language

Every endpoint is language-negotiated and returns a single resolved `name` or
`title` — never `name_ar` alongside `name_en`. Three ways to ask, in precedence
order:

1. `?lang=en` — wins over everything, useful for a shareable link
2. `X-Locale: en` — explicit and unambiguous
3. `Accept-Language: en` — what a browser sends by itself

If none is given, the reader's saved preference applies, then the language
marked default in the dashboard. Every response echoes what it chose in a
`Content-Language` header.

Supported today: `ar` · `en` · `fr` · `ur`. The list is editable at
`/admin/locales` without a deploy.

---

## Two things worth knowing

**A token changes what public endpoints return.** `/badges` is public so the
landing screen can preview the catalogue, but it personalises when a token is
present. Send one and `earned` is real; omit it and every badge reads `false`.
An *invalid* token stays anonymous and still returns `200` rather than `401`,
so a stale session degrades quietly instead of breaking the screen.

**Points are credited once, on the first pass.** A section's points land the
first time its quiz is passed. Retaking it afterwards is free and useful for
revision but awards nothing further — so a reader cannot climb the honour board
by repeating a section they have already cleared.

---

The full Postman collection (85 requests) is in [`postman/`](../postman).
