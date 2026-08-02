# Broadcasts

Broadcast what you're currently playing in your web player to other users.

## How it works

When you choose to become a broadcaster, all web player events (song change, position, play, pause ...) are transmitted to the web socket server which redistributes the information to listeners.
Listeners cannot interact with their web player which is controlled according to what you're doing on your side. This ensures connected listeners are playing the same music at the same time as you.

Broadcasting is the one Ampache feature that needs a **second long-running process** as well as the web server. That is usually why it appears not to work: the interface is there, the browser tries to open a socket, nothing is listening, and nothing is reported on the page.

| Piece | What it does |
| --- | --- |
| The web server | serves Ampache as usual |
| `bin/cli run:websocket` | the websocket server the players connect to; must stay running |

## Ampache settings

In `config/ampache.cfg.php`:

```INI
broadcast = "true"
websocket_address = "ws://localhost:8100"
```

`broadcast` shows the feature in the interface. `websocket_address` is handed to the browser verbatim, so it must be an address the **browser** can reach — not one that only resolves on the server.

Its host is also checked on the server side. The websocket server takes the host out of `websocket_address` and refuses any connection whose browser `Origin` is a different host, answering `403 Forbidden`, and the browser reports nothing on the page when that happens. So the host in `websocket_address` must be the host people load Ampache from.

When `websocket_address` is left empty, Ampache falls back to `<scheme>://<server name>:8100`, choosing `wss` when your web path is an `https://` url and `ws` otherwise.

## Run the websocket server

```shell
bin/cli run:websocket           # listens on port 8100
bin/cli run:websocket -p 8888   # or a port of your choosing
```

It stays in the foreground and serves two routes: `/broadcast` (the feature) and `/echo` (a test endpoint). Leave it running — if it stops, broadcasts stop with it.

Do not confuse it with `bin/cli run:broadcast`, which sends a UPnP/DLNA discovery announcement and has nothing to do with this feature.

For a real installation, run it as a service. A systemd unit ships with Ampache in `docs/examples/ampache_websocket.service`:

```shell
sudo cp docs/examples/ampache_websocket.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now ampache_websocket
```

## HTTPS and reverse proxies

A site served over HTTPS may only open a `wss://` socket. Browsers block a plain `ws://` connection from an `https://` page as mixed content, and they do it silently in the page — you will only see it in the browser console.

The usual answer is to proxy a path on your existing HTTPS host to the websocket server, so no extra port is exposed and the existing certificate is reused:

```INI
websocket_address = "wss://music.example.org/websocket"
```

### Apache

Apache doesn't support WebSocket by default and a proxy is needed. For WebSocket connections, proxy mod is not enough and proxy_wstunnel mod is required. Be aware that proxy_wstunnel module isn't available by default on Apache 2.2 on most distributions. Apache >= 2.4 is recommended.

Enable proxy_wstunnel module then add this to your vhost:

```AmpacheConf
ProxyPass        /websocket/ ws://127.0.0.1:8100/ retry=0
ProxyPassReverse /websocket/ ws://127.0.0.1:8100/
ProxyRequests off
ProxyTimeout 3600
```

### nginx

```AmpacheConf
location /websocket/ {
    proxy_pass http://127.0.0.1:8100/;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_read_timeout 3600s;
}
```

The `Upgrade` and `Connection` headers are the part that matters: without them the proxy answers the handshake as an ordinary HTTP request and the socket never opens. The long timeout matters too — a broadcast is an idle connection between songs, and a short timeout closes it mid-listen.

You can also temporarily open the binding port (8100 here) to internet access and use it directly, but it will not pass most http proxies.

## Using it

**To broadcast:** start playing something in the web player, then choose the broadcast control in the player. Ampache creates a broadcast and starts pushing the current song and position to it.

**To listen:** open **Broadcasts** in the sidebar and pick one. The list has **All** and **Live** views; a broadcast counts as live only while it is actually running. Your player then follows the broadcaster — the track and position are driven by them, not by you.

### Who can listen

A new broadcast requires the listener to have a valid session. A user who wants theirs open can turn off **Require a session to listen to my broadcasts** in their preferences; it applies to broadcasts created afterwards, and an existing one is changed with the *Authentication Required* checkbox on the Broadcasts browse, where they are also deleted.

## Checking it works

Work outward — each step rules out the one before it.

1. **Is the server up?** `bin/cli run:websocket -p 8100` should print `Starting socket at <host>:8100` and stay running.

2. **Is the port open on the server?**

    ```shell
    php -r '$c = @stream_socket_client("tcp://127.0.0.1:8100", $e, $s, 2); echo $c ? "open" : "closed: $s", PHP_EOL;'
    ```

3. **Is it reachable from the browser?** Open the browser console on any Ampache page:

    ```js
    var s = new WebSocket('ws://music.example.org:8100/echo');
    s.onopen  = () => console.log('websocket reachable');
    s.onerror = (e) => console.log('websocket failed', e);
    ```

    A failure here is a firewall, a proxy without the `Upgrade` headers, or a `ws://` address on an `https://` page.

    **`/echo` succeeding does not prove `/broadcast` will.** `/echo` accepts any origin; `/broadcast` only accepts the host `websocket_address` names. Repeat the check against the real route:

    ```js
    var b = new WebSocket('ws://music.example.org:8100/broadcast');
    b.onopen  = () => console.log('broadcast route ok');
    b.onerror = () => console.log('broadcast refused - origin/host mismatch?');
    ```

4. **Then try a broadcast** with two browsers — one broadcasting, one listening. The listener's player should change track when the broadcaster does.

## Known limitations

* The websocket server is a single process and holds every listener connection; it is not clustered.
* There is no reconnect: if the server restarts, listeners must rejoin.
* A listener's browser must be able to reach the *stream* as well as the socket, so the same access rules as ordinary playback apply.
* The origin check is exact on the host, so an installation reachable under two names can only broadcast under the one `websocket_address` names.
