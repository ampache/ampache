@component('mail::message')


The body of your message.

Click the following link to verify your [email]({{ route('verification.verify', ['id' => $id]) }})

Thanks,<br>
{{ config('app.name') }}
@endcomponent
