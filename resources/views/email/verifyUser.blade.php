@component('mail::message')


The body of your message.

Click the following link to verify your [email]({{ url('/verifyemail/') . "/" .$email_token }})

Thanks,<br>
{{ config('app.name') }}
@endcomponent
