
<p>Payer: {{ $payment->client->profile->first_name . ' ' . $payment->client->profile->last_name }},</p>
<p>Payment ID: {{ $payment->transaction_unique_id }} has been successfully completed.</p>
<p>Payment Date: {{ $payment->updated_at->format('F j, Y')}}</p>
<p>{{ config('app.name') }}</p>
