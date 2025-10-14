@component('mail::message')
# Envoi de document

{{ $emailBody }}

Merci,<br>
{{ config('app.name') }}
@endcomponent
