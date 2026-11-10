<p>Dear author,</p>
<p>The editorial team has updated the status of manuscript <strong>{{ $manuscript->reference }}</strong>, “{{ $manuscript->title }}”.</p>
<p><strong>Decision: {{ str_replace('_',' ',ucfirst($decision)) }}</strong></p>
@if($reason)<p>{{ $reason }}</p>@endif
@if($letter)<h2>Editorial letter</h2><p>{!! nl2br(e($letter)) !!}</p>@endif
<p>You can sign in to your Aksujet author workspace to view the official status and correspondence.</p>
<p>Regards,<br>Aksujet Editorial Office<br>{{ $manuscript->journal->title }}</p>
