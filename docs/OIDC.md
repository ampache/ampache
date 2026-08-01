# OpenID Connect

Ampache can delegate login to an external identity provider (IdP) over OpenID Connect — Microsoft Entra ID (Azure AD), Keycloak, Authentik, Okta, Google, and anything else that speaks standard OIDC.

Users click a button on the login page, authenticate at the provider, and return to Ampache logged in.

Accounts are created and kept up to date the same way LDAP does it.

Only **one** provider is supported at a time.

## How it works

* A user clicks **Sign in with OpenID Connect** on the login form
  * They will be sent straight to the provider if `oidc_auto_redirect` is enabled
* Ampache redirects them to the provider using the authorization-code flow with PKCE (S256)
* The provider authenticates the user and redirects back to **`<web_path>/oidc/`** with a one-time code
* Ampache exchanges the code, verifies the ID token, and reads the claims
* The user is matched to a local account by the **username claim**. With
  * `auto_create` enabled, a new local account is created on first login
  * `external_auto_update` updates user details on every login

`mysql` must stay in `auth_methods` alongside `oidc`, or local accounts —
including the admin — can no longer log in.

## The redirect URI

Register this **exact** URI with your provider:

```url
<your web_path>/oidc/
```

For example `https://music.example.com/oidc/`, or on a subdirectory install
`https://example.com/ampache/oidc/`.

Notes:

* The **trailing slash is required** — register the slashed form exactly, because some providers (Entra especially) do an exact string match on the redirect URI.
* There is **no query string** and it needs **no webserver configuration** — it works identically on Apache, nginx, IIS and lighttpd.
* It is built from `web_path`, so make sure `web_path` in your config is correct and reachable over HTTPS.

## Example: Microsoft Entra ID (Azure AD)

Make sure you go to **"App registrations"**, not **"Enterprise applications"** to register OIDC.

Everything below happens under **App registrations**, and you never touch any SAML or SSO setting.

### 1. Register the application

* In the [Azure portal](https://portal.azure.com), open **Microsoft Entra ID** (search for it in the top bar)
* In the left-hand menu of Entra ID, click **App registrations**
* Click **New registration** at the top. This form has no gallery / non-gallery choice; you only fill in the fields below
* **Name**: type `Ampache` (a display name only — nothing to select here)
* **Supported account types**: choose *Accounts in this organizational directory only* (single tenant)
* **Redirect URI**: set the platform dropdown to **Web**, and enter your `web_path` + `/oidc/`
  * e.g. `https://music.example.com/oidc/` (The trailing slash is required)
* Click **Register**

You are now on the app's **Overview** page. Copy two values from here:

* **Application (client) ID** → this becomes `oidc_client_id`
* **Directory (tenant) ID** → you paste this into `oidc_url` below (it is the `<tenant-id>` placeholder)

### 2. Create a client secret

* In the app's left-hand menu, go to **Certificates & secrets**, open the **Client secrets** tab, and click **New client secret**.
* Set a description and expiry, then click **Add**.
* Copy the secret's **Value** column immediately — it is shown only once, and you cannot retrieve it later. This becomes `oidc_client_secret`.
  * Copy the **Value**, not the **Secret ID**

### 3. Confirm the token/claims (optional)

Entra returns `preferred_username` (the UPN, e.g. `alice@contoso.com`) by default,
which works as the username claim. If you want a different value, or you want a
group/role claim later, configure it under **Token configuration** and
**API permissions** — but the defaults are enough to log in.

### 4. Find your endpoints

Entra publishes a discovery document, so you only need the base URL. It is:

```url
https://login.microsoftonline.com/<tenant-id>/v2.0
```

Ampache appends `/.well-known/openid-configuration` to it automatically, so you
do **not** need to set any of the manual `oidc_*_endpoint` keys.

### 5. Configure Ampache

Edit `config/ampache.cfg.php`:

```ini
; enable the method (keep mysql!)
auth_methods = "mysql,oidc"

; let Ampache create and update accounts from the provider
auto_create          = "true"
auto_user            = "user"
external_auto_update = "true"

; --* OIDC ---
oidc_url           = "https://login.microsoftonline.com/<tenant-id>/v2.0"
oidc_client_id     = "<application-client-id>"
oidc_client_secret = "<client-secret-value>"

; Entra sends the UPN here; this is the default, shown for clarity
oidc_username_claim = "preferred_username"
oidc_name_claim     = "name"
oidc_email_claim    = "email"

; optional: nicer button text
oidc_button_text = "Sign in with Microsoft"
```

Do **not** put spaces in `auth_methods` — it is split on commas without trimming,
so `"mysql, oidc"` would look for a method literally named `oidc`.

### 6. Test

1. Open `https://music.example.com/login.php`.
2. Click **Sign in with Microsoft**.
3. Authenticate at Microsoft and consent.
4. You should return to Ampache logged in. If `auto_create` is on, a new local
   user appears under **Admin → Users** with the name/email from your account.
5. **Navigate to a second page** and confirm you are still logged in — this is
   the check most likely to reveal a misconfiguration.

## Configuration reference

Add `oidc` to `auth_methods` (keeping `mysql`), then set the keys below in
`config/ampache.cfg.php`. All keys are commented out by default.

### Required

| Key                  | Description                                                                                               |
|----------------------|-----------------------------------------------------------------------------------------------------------|
| `oidc_url`           | Base URL of the provider. Ampache appends `/.well-known/openid-configuration` to discover the endpoints.  |
| `oidc_client_id`     | Client / application ID from the provider.                                                                |
| `oidc_client_secret` | Client secret. Must be a **confidential** client using the authorization-code grant with **PKCE (S256)**. |

### Identity and profile mapping

| Key                   | Default              | Description                                                                      |
|-----------------------|----------------------|----------------------------------------------------------------------------------|
| `oidc_username_claim` | `preferred_username` | Claim used as the Ampache username / login key. Pick a claim that never changes. |
| `oidc_name_claim`     | `name`               | Claim copied to the user's display name.                                         |
| `oidc_email_claim`    | `email`              | Claim copied to the user's email.                                                |
| `oidc_website_claim`  | *(none)*             | Optional claim → user website.                                                   |
| `oidc_state_claim`    | *(none)*             | Optional claim → user state.                                                     |
| `oidc_city_claim`     | *(none)*             | Optional claim → user city.                                                      |

The **username claim** identifies the account; the others are profile data copied
onto it (created with `auto_create`, refreshed with `external_auto_update`). A
login fails only if the username claim is missing — the rest are optional.

### Behaviour

| Key                  | Default                       | Description                                                                                                    |
|----------------------|-------------------------------|----------------------------------------------------------------------------------------------------------------|
| `oidc_scopes`        | `openid,profile,email`        | Comma-separated scopes to request. `openid` is always sent; do not repeat it.                                  |
| `oidc_use_userinfo`  | `true`                        | Query the userinfo endpoint for claims. Falls back to the verified ID-token claims if disabled or unavailable. |
| `oidc_button_text`   | `Sign in with OpenID Connect` | Login button label.                                                                                            |
| `oidc_auto_redirect` | `false`                       | Skip the local form and go straight to the provider. Reach the local form with `login.php?force_display=1`.    |
| `oidc_issuer`        | *(uses `oidc_url`)*           | Expected `iss` value of the ID token, if it differs from `oidc_url`.                                           |

### Providers without a discovery document

If your provider does not publish `/.well-known/openid-configuration`, set the
endpoints by hand. When you do this, PKCE support is declared for you.

| Key                           | Description                 |
|-------------------------------|-----------------------------|
| `oidc_authorization_endpoint` | Authorization endpoint URL. |
| `oidc_token_endpoint`         | Token endpoint URL.         |
| `oidc_userinfo_endpoint`      | Userinfo endpoint URL.      |
| `oidc_jwks_uri`               | JWKS (signing keys) URL.    |
| `oidc_end_session_endpoint`   | Logout endpoint URL.        |

### TLS

| Key                       | Description                                                                                                                                |
|---------------------------|--------------------------------------------------------------------------------------------------------------------------------------------|
| `oidc_cert_path`          | Path to a CA bundle used to verify the provider's certificate (e.g. `/etc/ssl/certs/ca-certificates.crt`). Default is the system CA store. |
| `oidc_disable_ssl_verify` | Disable TLS verification. **Development only — never enable in production.**                                                               |

## Single sign-out

There is no separate setting to sign out of the provider. Point the existing
`logout_redirect` at your provider's `end_session_endpoint`, and logging out of
Ampache will end the provider session too. For Entra:

```ini
logout_redirect = "https://login.microsoftonline.com/<tenant-id>/oauth2/v2.0/logout?post_logout_redirect_uri=https://music.example.com/"
```

## Troubleshooting

**Can't find where to register the app in Entra / only see SAML options.** You
are under **Enterprise applications**. OIDC uses **App registrations** instead
(Microsoft Entra ID → App registrations → New registration). Ignore anything
labelled *non-gallery application*, *Single sign-on*, or *SAML* — those are a
different protocol Ampache does not use here.

**Redirect URI mismatch at the provider.** The URI you registered must match
`<web_path>/oidc/` exactly, including the trailing slash and HTTPS. Fix `web_path`
in your config if it is wrong.

**Logged in, then immediately logged out on the next page.** `oidc` is not in
`auth_methods`, or `auth_methods` has a space in it. Sessions are filtered by
type against `auth_methods`, so the method name must be present and spelled
exactly `oidc`.

**"OpenID Connect requires an interactive browser login"** on the local form.
That is the expected message if someone tries to type a username/password against
OIDC — use the button, not the form fields.

**Generic error on the login form after returning from the provider.** State,
nonce, signature and rejected-login failures all show a generic message on
purpose; the real reason is written to the Ampache log. Check the log for the
details.

**Certificate / TLS errors reaching the provider.** Your PHP has no usable CA
bundle. Set `oidc_cert_path` to a valid bundle. Do **not** use
`oidc_disable_ssl_verify` outside of development.

**The admin can't log in anymore.** `mysql` was removed from `auth_methods`. Put
it back — you always need at least one local-account method for the admin and for
API/Subsonic clients.
