<?php

namespace App\Http\Controllers;

use App\Exceptions\EntitlementDenied;
use App\Http\Requests\GenerateQuizRequest;
use App\Http\Requests\SubmitQuizAttemptRequest;
use App\Jobs\GenerateQuiz;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\Usage\UsageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QuizController extends Controller
{
    public function index(): View
    {
        return view('quizzes.index', ['quizzes' => auth()->user()->quizzes()->latest()->get()]);
    }

    public function show(Quiz $quiz): View
    {
        Gate::authorize('view', $quiz);

        return view('quizzes.show', ['quiz' => $quiz->load('questions')]);
    }

    public function start(Quiz $quiz): RedirectResponse
    {
        Gate::authorize('view', $quiz);
        $attempt = $quiz->attempts()->firstOrCreate(['user_id' => auth()->id(), 'status' => 'in_progress']);

        return to_route('quizzes.attempts.show', [$quiz, $attempt]);
    }

    public function attempt(Quiz $quiz, QuizAttempt $attempt): View
    {
        Gate::authorize('view', $quiz);
        abort_unless($attempt->quiz_id === $quiz->id && $attempt->user_id === auth()->id(), 404);

        return view('quizzes.attempt', ['quiz' => $quiz->load('questions'), 'attempt' => $attempt]);
    }

    public function submit(SubmitQuizAttemptRequest $request, Quiz $quiz, QuizAttempt $attempt): RedirectResponse
    {
        Gate::authorize('view', $quiz);
        abort_unless($attempt->quiz_id === $quiz->id && $attempt->user_id === auth()->id(), 404);
        if ($attempt->status === 'submitted') {
            return to_route('quizzes.attempts.show', [$quiz, $attempt]);
        }
        $answers = $request->validated('answers');
        DB::transaction(function () use ($attempt, $quiz, $answers): void {
            $score = 0;
            $questions = $quiz->questions()->get();
            foreach ($questions as $question) {
                $response = trim((string) ($answers[$question->id] ?? ''));
                $correct = in_array($question->type, ['multiple_choice', 'true_false'], true) && strcasecmp($response, trim($question->answer)) === 0;
                $points = $correct ? 1 : 0;
                $score += $points;
                $attempt->answers()->updateOrCreate(['question_id' => $question->id], ['response' => $response, 'awarded_points' => $points, 'feedback' => $question->type === 'short_answer' ? 'Short answers are saved for review.' : null]);
            }
            $attempt->update(['status' => 'submitted', 'score' => $score, 'max_score' => $questions->count(), 'submitted_at' => now()]);
        });

        return to_route('quizzes.attempts.show', [$quiz, $attempt]);
    }

    public function store(GenerateQuizRequest $request, UsageService $usage): RedirectResponse
    {
        $topic = $request->string('topic')->toString();
        $requestKey = (string) Str::uuid();
        try {
            $usage->reserve($request->user(), 'quiz_generation', 1, $requestKey);
        } catch (EntitlementDenied $exception) {
            throw ValidationException::withMessages(['topic' => $exception->getMessage()]);
        }
        $quiz = $request->user()->quizzes()->create([
            'title' => $topic,
            'type' => $request->string('type')->toString(),
            'difficulty' => $request->string('difficulty')->toString(),
            'question_count' => $request->integer('question_count'),
            'status' => 'pending',
            'request_key' => $requestKey,
        ]);
        GenerateQuiz::dispatch($quiz, $topic);

        return to_route('quizzes.show', $quiz);
    }
}
