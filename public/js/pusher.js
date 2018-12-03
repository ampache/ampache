import Echo from "laravel-echo"

window.Pusher = require('pusher-js');

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: 'SuEUGtsJUHqKeiaWwXWDcGSfV_BE69uevr0QPzUeZXk',
    cluster: 'us2',
    encrypted: true
});
