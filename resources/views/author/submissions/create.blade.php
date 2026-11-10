<x-app-layout>
<x-slot name="header"><p class="eyebrow">Author workspace</p><h1 class="workspace-title">Submit a manuscript</h1><p class="workspace-subtitle">Your upload is private. The public site only displays work after editorial approval.</p></x-slot>
@if($errors->any())<div class="flash flash--error" role="alert"><strong>Submission not sent.</strong> Please correct the fields below and try again.</div>@endif
<form method="POST" action="{{ route('author.submissions.store') }}" enctype="multipart/form-data" class="workspace-form">@csrf
<section class="form-section"><div class="form-section-heading"><span class="form-step">01</span><div><p class="eyebrow">The paper</p><h2>Submission details</h2></div></div>
<label class="field"><span>Journal</span><select class="field-input" name="journal_id" required><option value="">Choose a journal</option>@foreach($journals as $journal)<option value="{{ $journal->id }}" @selected(old('journal_id') == $journal->id)>{{ $journal->title }}</option>@endforeach</select>@error('journal_id')<small class="field-error">{{ $message }}</small>@enderror</label>
<label class="field"><span>Article type</span><select class="field-input" name="article_type" required><option value="research-article">Research article</option><option value="review">Review article</option><option value="case-study">Case study</option><option value="short-communication">Short communication</option></select>@error('article_type')<small class="field-error">{{ $message }}</small>@enderror</label>
<label class="field"><span>Manuscript title</span><input class="field-input" name="title" value="{{ old('title') }}" maxlength="500" required>@error('title')<small class="field-error">{{ $message }}</small>@enderror</label>
<label class="field"><span>Abstract <small>100–10,000 characters</small></span><textarea class="field-input field-textarea" name="abstract" rows="7" minlength="100" maxlength="10000" required>{{ old('abstract') }}</textarea>@error('abstract')<small class="field-error">{{ $message }}</small>@enderror</label>
<label class="field"><span>Cover letter <small>Optional</small></span><textarea class="field-input field-textarea" name="cover_letter" rows="5" maxlength="20000">{{ old('cover_letter') }}</textarea></label>
<label class="field"><span>Manuscript file <small>PDF, DOC or DOCX · up to 20 MB</small></span><input class="field-input field-file" type="file" name="manuscript_file" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required>@error('manuscript_file')<small class="field-error">{{ $message }}</small>@enderror</label>
</section>
<section class="form-section"><div class="form-section-heading"><span class="form-step">02</span><div><p class="eyebrow">Corresponding author</p><h2>Your details</h2></div></div>
<label class="field"><span>Author name</span><input class="field-input" name="author_name" value="{{ old('author_name', auth()->user()->name) }}" maxlength="255" required></label>
<label class="field"><span>Affiliation <small>Optional</small></span><input class="field-input" name="affiliation" value="{{ old('affiliation', auth()->user()->affiliation) }}" maxlength="255"></label>
<label class="field"><span>ORCID <small>Optional</small></span><input class="field-input" name="orcid" value="{{ old('orcid', auth()->user()->orcid) }}" placeholder="0000-0000-0000-0000" maxlength="19">@error('orcid')<small class="field-error">{{ $message }}</small>@enderror</label>
<label class="field"><span>Co-authors <small>One per line: Full name | email | affiliation | ORCID</small></span><textarea class="field-input field-textarea" name="coauthors" rows="5" placeholder="Amina Okon | amina@example.org | Akwa Ibom State University | 0000-0000-0000-0000">{{ old('coauthors') }}</textarea>@error('coauthors')<small class="field-error">{{ $message }}</small>@enderror</label>
</section>
<section class="form-section"><div class="form-section-heading"><span class="form-step">03</span><div><p class="eyebrow">Author declarations</p><h2>Before you submit</h2></div></div>
<label class="check-row check-row--block"><input type="checkbox" name="confirm_originality" value="1" required @checked(old('confirm_originality'))><span>I confirm this work is original, has not been published elsewhere and is not under consideration by another journal.</span></label>
<label class="check-row check-row--block"><input type="checkbox" name="confirm_ethics" value="1" required @checked(old('confirm_ethics'))><span>I confirm that all authors approve the submission and that required research ethics approvals are in place.</span></label>
<p class="form-note">Submitting creates a private manuscript record and reference number. It does not publish the paper or make the uploaded file public.</p>
<div class="form-actions"><a class="text-link" href="{{ route('author.submissions.index') }}">Cancel</a><button class="button button--pine" type="submit">Submit manuscript <span aria-hidden="true">↗</span></button></div>
</section>
</form>
</x-app-layout>
