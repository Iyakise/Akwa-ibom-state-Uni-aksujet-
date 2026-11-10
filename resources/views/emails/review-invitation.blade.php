<p>Dear reviewer,</p>
<p>The Aksujet editorial team invites you to review manuscript <strong>{{ $manuscript->reference }}</strong>, “{{ $manuscript->title }}”, for {{ $manuscript->journal->title }}.</p>
@if($assignment->due_at)<p>Please submit your assessment by {{ $assignment->due_at->format('F j, Y') }}.</p>@endif
<p>Sign in with the account matching this invitation to access the blinded manuscript and submit your confidential assessment:</p>
<p><a href="{{ route('reviewer.assignments.show',$assignment) }}">Open reviewer workspace</a></p>
<p>The manuscript file is private and this assignment is restricted to your reviewer account.</p>
<p>Regards,<br>Aksujet Editorial Office</p>
