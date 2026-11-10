<?php

use App\Http\Controllers\Author\SubmissionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Editor\EditorialController;
use App\Http\Controllers\Editor\ArchiveImportController;
use App\Http\Controllers\Editor\TeamManagementController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\Reviewer\ReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicSiteController::class, 'home'])->name('home');
Route::get('/journals/{journal:slug}', [PublicSiteController::class, 'journal'])->name('journals.show');
Route::get('/issues/{issue:slug}', [PublicSiteController::class, 'issue'])->name('issues.show');
Route::get('/articles/{article:slug}', [PublicSiteController::class, 'article'])->name('articles.show');
Route::get('/articles/{article:slug}/full-text', [PublicSiteController::class, 'pdf'])->name('articles.pdf');
Route::get('/robots.txt', [PublicSiteController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [PublicSiteController::class, 'sitemap'])->name('sitemap');

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified', 'role:author,editor,administrator'])->prefix('author')->name('author.')->group(function (): void {
    Route::get('/submissions', [SubmissionController::class, 'index'])->name('submissions.index');
    Route::get('/submissions/create', [SubmissionController::class, 'create'])->name('submissions.create');
    Route::post('/submissions', [SubmissionController::class, 'store'])->middleware('throttle:submissions')->name('submissions.store');
    Route::get('/submissions/{manuscript}', [SubmissionController::class, 'show'])->name('submissions.show');
    Route::post('/submissions/{manuscript}/revision', [SubmissionController::class, 'revision'])->middleware('throttle:submissions')->name('submissions.revision.store');
    Route::get('/files/{file}', [SubmissionController::class, 'file'])->name('files.show');
});

Route::middleware(['auth', 'verified', 'role:editor,administrator'])->prefix('editor')->name('editor.')->group(function (): void {
    Route::get('/', [EditorialController::class, 'index'])->name('dashboard');
    Route::get('/archive', [ArchiveImportController::class, 'index'])->name('archive.index');
    Route::post('/archive', [ArchiveImportController::class, 'store'])->name('archive.store');
    Route::post('/journals', [EditorialController::class, 'storeJournal'])->name('journals.store');
    Route::get('/manuscripts/{manuscript}', [EditorialController::class, 'show'])->name('manuscripts.show');
    Route::get('/manuscripts/{manuscript}/files/{file}', [EditorialController::class, 'file'])->name('manuscripts.files.show');
    Route::post('/manuscripts/{manuscript}/assign-editor', [EditorialController::class, 'assignEditor'])->name('manuscripts.editor.store');
    Route::patch('/manuscripts/{manuscript}/status', [EditorialController::class, 'transition'])->name('manuscripts.transition');
    Route::post('/manuscripts/{manuscript}/reviewers', [EditorialController::class, 'assignReviewer'])->name('manuscripts.reviewers.store');
    Route::post('/issues', [EditorialController::class, 'storeIssue'])->name('issues.store');
    Route::post('/issues/{issue}/publish', [EditorialController::class, 'publishIssue'])->name('issues.publish');
    Route::post('/publications', [EditorialController::class, 'storePublication'])->name('publications.store');
    Route::get('/articles/{article}/files/{file}', [EditorialController::class, 'articleFile'])->name('articles.files.show');
    Route::post('/articles/{article}/publish', [EditorialController::class, 'publishArticle'])->name('articles.publish');
    Route::middleware('role:administrator')->group(function (): void {
        Route::get('/team', [TeamManagementController::class, 'index'])->name('team.index');
        Route::patch('/team/{user}', [TeamManagementController::class, 'update'])->name('team.update');
    });
});

Route::middleware(['auth', 'verified', 'role:reviewer'])->prefix('reviewer')->name('reviewer.')->group(function (): void {
    Route::get('/', [ReviewController::class, 'index'])->name('dashboard');
    Route::get('/assignments/{assignment}', [ReviewController::class, 'show'])->name('assignments.show');
    Route::post('/assignments/{assignment}/accept', [ReviewController::class, 'accept'])->name('assignments.accept');
    Route::post('/assignments/{assignment}/decline', [ReviewController::class, 'decline'])->name('assignments.decline');
    Route::get('/assignments/{assignment}/files/{file}', [ReviewController::class, 'file'])->name('assignments.files.show');
    Route::post('/assignments/{assignment}/review', [ReviewController::class, 'submit'])->middleware('throttle:reviews')->name('assignments.review.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
