# Registration, password reset and notification reads

The endpoints added in September 2026. Every sample below is a real response
from the API.

Base URL: `https://himam-back.onrender.com/api`

| Endpoint | Auth | Purpose |
| --- | --- | --- |
| `POST /auth/register` | public | Now collects the association's own registration fields |
| `POST /auth/forgot-password` | public | Emails a reset link |
| `POST /auth/reset-password` | public | Sets the new password against the emailed token |
| `POST /announcements/{id}/read` | reader | Marks one notification read |
| `GET /countries` | public | Country list for the registration form |

---

## Registration — `POST /auth/register`

Beyond name, email and password, registration now collects what the
association's own form asks for. **Everything except name, email and password
is optional**, and every column is nullable — accounts created before these
questions existed are unaffected, and a certificate is never blocked on someone
going back to fill in their age band.

| Field | Required | Accepted values |
| --- | --- | --- |
| `name` | yes | Free text. This is what gets printed on the certificate. |
| `email` | yes | Must be unique. |
| `password` | yes | At least 8 characters, with `password_confirmation`. |
| `gender` | no | `male` · `female` |
| `phone` | no | Free text, max 32. |
| `city` | no | Free text. |
| `country` | no | Free text — the app sends the country's localised name. |
| `accepts_email` | no | Boolean. Consent to be emailed about the programme, which is a separate question from the in-app notification preferences. |
| `locale` | no | Any active locale code. Defaults to the request's language. |

### Request

```json
{
  "name": "سالم بن أحمد",
  "email": "salem@himam.test",
  "password": "secret123",
  "password_confirmation": "secret123",
  "gender": "male",
  "country": "Kuwait",
  "accepts_email": true
}
```

### Response — `201`

```json
{
  "token": "1|IiNGqw…",
  "user": {
    "id": 10,
    "name": "سالم بن أحمد",
    "email": "salem@himam.test",
    "gender": "male",
    "phone": null,
    "city": null,
    "country": "Kuwait",
    "accepts_email": true,
    "role": "student",
    "locale": "ar",
    "points": 0,
    "level": null,
    "created_at": "2026-09-09T06:22:51+00:00"
  }
}
```

The token is usable immediately — there is no separate sign-in step after
registering.

### Validation errors — `422`

Errors are returned per field and are translated. The same request in Arabic:

```json
{
  "errors": {
    "name": ["حقل الاسم مطلوب."],
    "email": ["قيمة حقل البريد الإلكتروني مستخدمة من قبل."],
    "password": ["يجب ألا يقل طول حقل كلمة المرور عن 8 حروف."],
    "gender": ["قيمة حقل الجنس غير صحيحة."]
  }
}
```

---

## Forgot password — `POST /auth/forgot-password`

```json
{ "email": "salem@himam.test" }
```

```json
{ "message": "إن كان لهذا البريد حساب، فسيصله رابط الاستعادة." }
```

**The reply is identical whether or not the address is registered.** Saying "no
such account" here would turn the endpoint into a way to test which addresses
have accounts, which is worth more to an attacker than the small convenience it
offers someone who mistyped their email. Do not build a UI that implies
otherwise.

Asking again too soon returns `429`:

```json
{ "message": "أُرسل رابط الاستعادة قبل قليل. يُرجى الانتظار قبل طلب رابط آخر." }
```

### The emailed link

The link opens the **app**, not the API:

```
https://<frontend>/reset-password?token=f9df3c0e…&email=salem%40himam.test
```

The origin comes from `FRONTEND_URL`. If it is unset the link falls back to
`APP_URL`, which is the backend — so a reader would land on a JSON endpoint.
Set it on any host that sends these emails.

---

## Reset password — `POST /auth/reset-password`

```json
{
  "token": "f9df3c0e…",
  "email": "salem@himam.test",
  "password": "newsecret123",
  "password_confirmation": "newsecret123"
}
```

```json
{ "message": "تم تغيير كلمة المرور، ويمكنك تسجيل الدخول الآن." }
```

Three things worth knowing:

- **The token is single use.** Replaying it fails.
- **It expires.** An expired or wrong token returns `422` with
  `"رابط الاستعادة غير صالح أو انتهت صلاحيته. يُرجى طلب رابط جديد."` — the same
  message either way, so the endpoint cannot be used to probe tokens.
- **A successful reset revokes every API token on that account.** Anyone holding
  one had the old password, or took it; resetting is the moment to end those
  sessions. The reader must sign in again, including on their other devices.

---

## Mark one notification read — `POST /announcements/{id}/read`

Requires a reader token.

```json
{
  "data": { "id": 1, "read": true },
  "meta": { "unread": 4 }
}
```

`meta.unread` is the new count, so the bell can update without refetching the
whole feed. It excludes muted categories, exactly as the feed does — the badge
can never sit on a number the reader has no way to clear.

Calling it twice is harmless. A notification addressed to another reader returns
`403`; an unpublished one returns `404`.

### Why this exists when `GET /announcements/{id}` already marks it read

Opening a notification with `GET` does mark it read, and still does. But a `GET`
is the request most likely to be served from a cache and never reach the server,
and a reader opening a notification from a list they already hold has no reason
to fetch the body again. This endpoint is what the app calls at the moment it
decides the notification has been seen.

---

## Countries — `GET /countries`

Public. Backs the country picker on the registration form.

Names and ordering follow the request's language — Arabic names sort by the
Arabic alphabet, not by their codes. Only ISO 3166-1 codes and dialling codes
are stored server-side; the names come from PHP's own locale data, so a language
added later needs no new translation file.

```json
{
  "data": [
    { "code": "ET", "name": "إثيوبيا", "dial_code": "+251" },
    { "code": "AZ", "name": "أذربيجان", "dial_code": "+994" },
    { "code": "KW", "name": "الكويت", "dial_code": "+965" }
  ],
  "meta": { "locale": "ar" }
}
```

114 countries. `dial_code` is there for any screen that asks for a phone number:
deriving it from the country already chosen is kinder than asking a reader to
remember it.

---

## A note on languages

Every message above is returned in the request's language — `?lang=`,
`X-Locale`, or `Accept-Language`, in that order of precedence — including field
validation errors.

This was not previously true. There was no `lang/` directory at all, so every
API message fell through to its English literal: the API answered an Arabic app
in English. All 32 messages plus the validation rules are now translated into
`ar`, `fr` and `ur`.

---

The full Postman collection (89 requests) is in [`postman/`](../postman).
`Forgot password` and `Reset password` are in the Auth folder; `Mark one read`
sits beside `Mark all read`. Paste a token from a real reset email into the
`resetToken` variable before sending `Reset password` — without one it correctly
returns `422`, which the request asserts.
