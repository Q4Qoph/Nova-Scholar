<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\FlashcardController;
use App\Http\Controllers\GuardianPortalController;
use App\Http\Controllers\LearnerActivationController;
use App\Http\Controllers\LearnerSessionController;
use App\Http\Controllers\ManagedLearnerAccessController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfilePhotoController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\SchoolAcademicController;
use App\Http\Controllers\SchoolAttendanceController;
use App\Http\Controllers\SchoolCommunicationController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SchoolFeeController;
use App\Http\Controllers\SchoolGuardianController;
use App\Http\Controllers\SchoolInvitationController;
use App\Http\Controllers\SchoolLearnerController;
use App\Http\Controllers\SchoolLearnerImportController;
use App\Http\Controllers\SchoolMembershipController;
use App\Http\Controllers\SchoolReceiptController;
use App\Http\Controllers\SubscriptionController;
use App\SchoolRole;
use App\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
})->name('home');

Route::get('/dashboard', function () {
    $user = auth()->user();

    if ($user->role === UserRole::Admin) {
        return redirect()->route('filament.platform.home');
    }

    $schoolMemberships = $user->schoolMemberships()
        ->active()
        ->whereHas('roles', fn (Builder $query): Builder => $query->whereIn('role', [
            SchoolRole::SchoolAdmin->value,
            SchoolRole::Teacher->value,
            SchoolRole::Bursar->value,
        ]))
        ->whereHas('school', fn (Builder $query): Builder => $query->where('status', 'active'))
        ->with(['school', 'roles'])
        ->get();

    if ($schoolMemberships->isNotEmpty()) {
        return redirect()->route('filament.school.pages.home', [
            'tenant' => $schoolMemberships->first()->school->slug,
        ]);
    }

    return view('dashboard', compact('schoolMemberships'));
})->middleware(['auth', 'adult.account', 'verified'])->name('dashboard');

Route::get('/subscription', [SubscriptionController::class, 'index'])
    ->middleware(['auth', 'adult.account', 'verified'])
    ->name('subscription.index');

Route::get('/learner/activate/{token}', [LearnerActivationController::class, 'show'])->name('learner.activate.show');
Route::post('/learner/activate/{token}', [LearnerActivationController::class, 'store'])->name('learner.activate.store');

Route::middleware('guest')->group(function () {
    Route::get('/learner/login', [LearnerSessionController::class, 'create'])->name('learner.login');
    Route::post('/learner/login', [LearnerSessionController::class, 'store']);
});

Route::middleware(['auth', 'learner.account'])->group(function () {
    Route::get('/learner/dashboard', [LearnerSessionController::class, 'dashboard'])->name('learner.dashboard');
    Route::post('/learner/logout', [LearnerSessionController::class, 'destroy'])->name('learner.logout');
});

Route::get('/schools/{school}/overview', [SchoolController::class, 'overview'])
    ->middleware(['auth', 'adult.account', 'verified', 'school.context'])
    ->name('schools.overview');

Route::middleware(['auth', 'adult.account', 'verified'])->group(function () {
    Route::get('/school-invitations/{token}', [SchoolInvitationController::class, 'show'])->name('school-invitations.show');
    Route::post('/school-invitations/{token}', [SchoolInvitationController::class, 'accept'])->name('school-invitations.accept');
    Route::get('/guardian/learners', [GuardianPortalController::class, 'index'])->name('guardian.learners.index');
});

Route::middleware(['auth', 'adult.account', 'verified', 'school.context'])->scopeBindings()->group(function () {
    Route::get('/schools/{school}/learners', [SchoolLearnerController::class, 'index'])->name('schools.learners.index');
    Route::post('/schools/{school}/learners', [SchoolLearnerController::class, 'store'])->name('schools.learners.store');
    Route::get('/schools/{school}/learners/{learner}', [SchoolLearnerController::class, 'show'])->name('schools.learners.show');
    Route::post('/schools/{school}/learners/{learner}/class-memberships', [SchoolLearnerController::class, 'storeClassMembership'])->name('schools.learners.class-memberships.store');
    Route::post('/schools/{school}/learners/{learner}/promote', [SchoolLearnerController::class, 'promote'])->name('schools.learners.promote');
    Route::post('/schools/{school}/learners/{learner}/transfer', [SchoolLearnerController::class, 'transfer'])->name('schools.learners.transfer');
    Route::post('/schools/{school}/learners/{learner}/deactivate', [SchoolLearnerController::class, 'deactivate'])->name('schools.learners.deactivate');
    Route::post('/schools/{school}/learners/{learner}/access', [ManagedLearnerAccessController::class, 'store'])->name('schools.learners.access.store');
    Route::post('/schools/{school}/learner-imports', [SchoolLearnerImportController::class, 'store'])->name('schools.learner-imports.store');
    Route::get('/schools/{school}/learner-imports/{importBatch}', [SchoolLearnerImportController::class, 'show'])->name('schools.learner-imports.show');
    Route::post('/schools/{school}/learner-imports/{importBatch}/commit', [SchoolLearnerImportController::class, 'commit'])->name('schools.learner-imports.commit');
    Route::post('/schools/{school}/learners/{learner}/guardians', [SchoolGuardianController::class, 'store'])->name('schools.learners.guardians.store');
    Route::delete('/schools/{school}/learners/{learner}/guardians/{guardianLink}', [SchoolGuardianController::class, 'destroy'])->name('schools.learners.guardians.destroy');
    Route::get('/schools/{school}/academic', [SchoolAcademicController::class, 'index'])->name('schools.academic.index');
    Route::get('/schools/{school}/attendance', [SchoolAttendanceController::class, 'index'])->name('schools.attendance.index');
    Route::post('/schools/{school}/attendance', [SchoolAttendanceController::class, 'store'])->name('schools.attendance.store');
    Route::get('/schools/{school}/fees', [SchoolFeeController::class, 'index'])->name('schools.fees.index');
    Route::post('/schools/{school}/fee-schedules', [SchoolFeeController::class, 'storeSchedule'])->name('schools.fee-schedules.store');
    Route::post('/schools/{school}/fee-charge-batches/preview', [SchoolFeeController::class, 'preview'])->name('schools.fee-charge-batches.preview');
    Route::post('/schools/{school}/fee-charge-batches', [SchoolFeeController::class, 'post'])->name('schools.fee-charge-batches.post');
    Route::post('/schools/{school}/receipts', [SchoolReceiptController::class, 'store'])->name('schools.receipts.store');
    Route::post('/schools/{school}/receipt-allocations', [SchoolReceiptController::class, 'allocate'])->name('schools.receipts.allocations.store');
    Route::get('/schools/{school}/communications', [SchoolCommunicationController::class, 'index'])->name('schools.communication.index');
    Route::post('/schools/{school}/announcements', [SchoolCommunicationController::class, 'store'])->name('schools.announcements.store');
    Route::post('/schools/{school}/announcements/{announcement}/send', [SchoolCommunicationController::class, 'send'])->name('schools.announcements.send');
    Route::post('/schools/{school}/academic-years', [SchoolAcademicController::class, 'storeYear'])->name('schools.academic-years.store');
    Route::post('/schools/{school}/academic-years/{academicYear}/terms', [SchoolAcademicController::class, 'storeTerm'])->name('schools.terms.store');
    Route::post('/schools/{school}/academic-years/{academicYear}/class-groups', [SchoolAcademicController::class, 'storeClassGroup'])->name('schools.class-groups.store');
    Route::post('/schools/{school}/subjects', [SchoolAcademicController::class, 'storeSubject'])->name('schools.subjects.store');
    Route::post('/schools/{school}/teaching-assignments', [SchoolAcademicController::class, 'storeAssignment'])->name('schools.teaching-assignments.store');
    Route::post('/schools/{school}/invitations', [SchoolInvitationController::class, 'store'])->name('schools.invitations.store');
    Route::delete('/schools/{school}/invitations/{invitation}', [SchoolInvitationController::class, 'destroy'])->name('schools.invitations.destroy');
    Route::post('/schools/{school}/memberships/{membership}/roles', [SchoolMembershipController::class, 'storeRole'])->name('schools.memberships.roles.store');
    Route::delete('/schools/{school}/memberships/{membership}/roles/{role}', [SchoolMembershipController::class, 'destroyRole'])->name('schools.memberships.roles.destroy');
    Route::delete('/schools/{school}/memberships/{membership}', [SchoolMembershipController::class, 'destroy'])->name('schools.memberships.destroy');
});

Route::resource('documents', DocumentController::class)
    ->only(['index', 'store', 'destroy'])
    ->middleware(['auth', 'adult.account', 'verified']);

Route::get('/chat', [ChatController::class, 'index'])->middleware(['auth', 'adult.account', 'verified'])->name('chats.index');
Route::get('/chat/{chat}', [ChatController::class, 'show'])->middleware(['auth', 'adult.account', 'verified'])->name('chats.show');
Route::post('/chat/{chat}/messages', [ChatController::class, 'store'])->middleware(['auth', 'adult.account', 'verified'])->name('chats.messages.store');

Route::middleware(['auth', 'adult.account', 'verified'])->group(function () {
    Route::get('/quizzes', [QuizController::class, 'index'])->name('quizzes.index');
    Route::post('/quizzes', [QuizController::class, 'store'])->name('quizzes.store');
    Route::get('/quizzes/{quiz}', [QuizController::class, 'show'])->name('quizzes.show');
    Route::post('/quizzes/{quiz}/attempts', [QuizController::class, 'start'])->name('quizzes.attempts.start');
    Route::get('/quizzes/{quiz}/attempts/{attempt}', [QuizController::class, 'attempt'])->name('quizzes.attempts.show');
    Route::post('/quizzes/{quiz}/attempts/{attempt}', [QuizController::class, 'submit'])->name('quizzes.attempts.submit');
    Route::get('/flashcards', [FlashcardController::class, 'index'])->name('flashcards.index');
    Route::post('/flashcards', [FlashcardController::class, 'store'])->name('flashcards.store');
    Route::get('/flashcards/{flashcardDeck}', [FlashcardController::class, 'show'])->name('flashcards.show');
    Route::post('/flashcards/{flashcardDeck}/{flashcard}/review', [FlashcardController::class, 'review'])->name('flashcards.review');
});

Route::middleware(['auth', 'adult.account'])->group(function () {
    Route::get('/profile/photo', ProfilePhotoController::class)->name('profile.photo');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
