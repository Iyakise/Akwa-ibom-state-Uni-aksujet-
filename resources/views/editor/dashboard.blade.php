<x-app-layout>
<x-slot name="header">
  <p class="eyebrow">Aksujet editorial office</p>
  <h1 class="workspace-title">Editorial desk</h1>
  <p class="workspace-subtitle">Review incoming manuscripts, manage journal issues, and move approved papers toward publication.</p>
</x-slot>

@if(session('status'))<div class="flash flash--success" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="flash flash--error" role="alert">{{ $errors->first() }}</div>@endif

<div class="action-row">
  <a class="button button--pine" href="{{ route('editor.archive.index') }}">Import and manage archive PDFs <span aria-hidden="true">→</span></a>
  @if(auth()->user()->hasRole('administrator'))<a class="button button--pine button--small" href="{{ route('editor.team.index') }}">Manage accounts</a>@endif
</div>

<div class="metric-row">
  @foreach(['submitted'=>'New submissions','initial_screening'=>'Screening','under_review'=>'Under review','revision_requested'=>'Revisions'] as $key => $label)
    <div class="metric-card"><span class="eyebrow">{{ $label }}</span><strong>{{ $summary[$key] ?? 0 }}</strong></div>
  @endforeach
</div>

<section class="workspace-panel">
  <div class="panel-heading"><div><p class="eyebrow">Editorial workflow</p><h2>Manuscript queue</h2></div><span class="panel-count">{{ $manuscripts->total() }} total</span></div>
  <div class="table-wrap"><table class="data-table">
    <thead><tr><th>Reference</th><th>Manuscript</th><th>Author</th><th>Journal</th><th>Status</th><th>Received</th><th></th></tr></thead>
    <tbody>
    @forelse($manuscripts as $manuscript)
      <tr>
        <td><span class="reference-code">{{ $manuscript->reference }}</span></td>
        <td class="table-title">{{ $manuscript->title }}</td>
        <td>{{ $manuscript->submitter->name }}</td>
        <td>{{ $manuscript->journal->short_title ?? $manuscript->journal->title }}</td>
        <td><span class="status-pill status-pill--{{ str_replace('_','-',$manuscript->status) }}">{{ str_replace('_',' ',ucfirst($manuscript->status)) }}</span></td>
        <td>{{ $manuscript->submitted_at?->format('M j, Y') }}</td>
        <td><a class="text-link" href="{{ route('editor.manuscripts.show',$manuscript) }}">Open <span aria-hidden="true">→</span></a></td>
      </tr>
    @empty
      <tr><td colspan="7" class="table-empty"><p class="eyebrow">Editorial queue</p><h2>No submissions yet.</h2><p>Verified author submissions will appear here. The public archive and manuscript queue remain separate.</p></td></tr>
    @endforelse
    </tbody>
  </table></div>
  @if($manuscripts->hasPages())
    <div class="pagination-row">@if($manuscripts->previousPageUrl())<a class="button button--pine" href="{{ $manuscripts->previousPageUrl() }}">Previous</a>@endif<span>Page {{ $manuscripts->currentPage() }} of {{ $manuscripts->lastPage() }}</span>@if($manuscripts->nextPageUrl())<a class="button button--pine" href="{{ $manuscripts->nextPageUrl() }}">Next</a>@endif</div>
  @endif
</section>

<div class="workspace-grid workspace-grid--lower">
  <section class="workspace-panel">
    <p class="eyebrow">Journal setup</p><h2>Add a journal</h2>
    <p class="panel-copy">Create a journal record once; issues and published articles are managed under it.</p>
    <form method="POST" action="{{ route('editor.journals.store') }}" class="form-stack">@csrf
      <label class="field"><span>Journal title</span><input class="field-input" name="title" maxlength="255" required></label>
      <div class="form-inline"><label class="field"><span>Short title <small>Optional</small></span><input class="field-input" name="short_title" maxlength="120"></label><label class="field"><span>URL slug <small>Optional</small></span><input class="field-input" name="slug" maxlength="190" placeholder="generated-from-title"></label></div>
      <div class="form-inline"><label class="field"><span>ISSN <small>Optional</small></span><input class="field-input" name="issn" placeholder="1234-567X"></label><label class="field"><span>eISSN <small>Optional</small></span><input class="field-input" name="eissn" placeholder="1234-567X"></label></div>
      <label class="field"><span>Description <small>Optional</small></span><textarea class="field-input field-textarea" name="description" rows="3" maxlength="5000"></textarea></label>
      <label class="field"><span>Scope <small>Optional</small></span><textarea class="field-input field-textarea" name="scope" rows="3" maxlength="5000"></textarea></label>
      <label class="field"><span>Editorial contact email <small>Optional</small></span><input class="field-input" type="email" name="contact_email" maxlength="255"></label>
      <button class="button button--pine" type="submit">Create journal</button>
    </form>
  </section>

  <section class="workspace-panel">
    <p class="eyebrow">Issue planning</p><h2>Create an issue draft</h2>
    <p class="panel-copy">An issue must be published before its articles can enter the public archive.</p>
    @if($journals->isNotEmpty())
      <form method="POST" action="{{ route('editor.issues.store') }}" class="form-stack">@csrf
        <label class="field"><span>Journal</span><select name="journal_id" class="field-input" required>@foreach($journals as $journal)<option value="{{ $journal->id }}">{{ $journal->title }}</option>@endforeach</select></label>
        <div class="form-inline"><label class="field"><span>Volume</span><input class="field-input" name="volume" type="number" min="1" required></label><label class="field"><span>Issue</span><input class="field-input" name="issue_number" type="number" min="1" required></label><label class="field"><span>Year</span><input class="field-input" name="publication_year" type="number" value="{{ now()->year }}" min="1900" max="2200" required></label></div>
        <label class="field"><span>Title <small>Optional</small></span><input class="field-input" name="title" maxlength="255"></label>
        <label class="field"><span>Issue description <small>Optional</small></span><textarea class="field-input field-textarea" name="description" rows="3" maxlength="5000"></textarea></label>
        <button class="button button--pine" type="submit">Save issue draft</button>
      </form>
    @else
      <div class="notice-card"><strong>Create an active journal first.</strong><p>The journal setup form is beside this panel.</p></div>
    @endif
  </section>

  <section class="workspace-panel">
    <p class="eyebrow">Issue archive</p><h2>Issues</h2>
    <div class="issue-admin-list">
      @forelse($issues as $issue)
        <div class="issue-admin-row"><div><strong>{{ $issue->title }}</strong><small>{{ $issue->journal->short_title }} · {{ $issue->status }}</small></div>
          @if($issue->status === 'draft')<form method="POST" action="{{ route('editor.issues.publish',$issue) }}" data-confirm="Publish this issue to the public archive?">@csrf<button class="button button--pine button--small" type="submit">Publish issue</button></form>
          @else<a class="text-link" href="{{ route('issues.show',$issue) }}">View public issue ↗</a>@endif
        </div>
      @empty<p class="panel-copy">No issues have been created.</p>
      @endforelse
    </div>
  </section>
</div>
</x-app-layout>
