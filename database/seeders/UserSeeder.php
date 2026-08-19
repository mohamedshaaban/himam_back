<?php

namespace Database\Seeders;

use App\Models\BookSection;
use App\Models\Level;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\ProgressService;
use Illuminate\Database\Seeder;

/**
 * Creates the demo accounts and gives them real progress.
 *
 * Rather than writing points straight onto the user rows, each reader actually
 * sits the quizzes through ProgressService — so attempts, reading progress,
 * points, badges and certificates all agree with one another, and the admin
 * dashboard has genuine activity to show.
 */
class UserSeeder extends Seeder
{
    public function __construct(private readonly ProgressService $progress)
    {
    }

    public function run(): void
    {
        $levels = Level::orderBy('position')->get();

        $this->createUser([
            'name' => 'إدارة همم',
            'email' => 'admin@himam.test',
            'role' => 'admin',
            'avatar' => 'assets/avatar-1.svg',
            'city' => 'الرياض',
        ]);

        // Names and ranking come from the honour board in the design; the
        // section counts drive how many points each one ends up with.
        $students = [
            ['name' => 'عبدالله سالم', 'email' => 'abdullah@himam.test', 'avatar' => 'assets/avatar-1.svg', 'level' => 3, 'sections' => 12],
            ['name' => 'محمد احمد', 'email' => 'mohammed@himam.test', 'avatar' => 'assets/avatar-2.svg', 'level' => 2, 'sections' => 10],
            ['name' => 'خالد يوسف', 'email' => 'khaled@himam.test', 'avatar' => 'assets/avatar-3.svg', 'level' => 2, 'sections' => 9],
            ['name' => 'سعود ناصر', 'email' => 'saud@himam.test', 'avatar' => 'assets/avatar-1.svg', 'level' => 1, 'sections' => 8],
            ['name' => 'فهد عبدالعزيز', 'email' => 'fahd@himam.test', 'avatar' => 'assets/avatar-2.svg', 'level' => 1, 'sections' => 7],
            ['name' => 'بدر الحربي', 'email' => 'badr@himam.test', 'avatar' => 'assets/avatar-3.svg', 'level' => 2, 'sections' => 7],
            ['name' => 'ماجد العتيبي', 'email' => 'majed@himam.test', 'avatar' => 'assets/avatar-1.svg', 'level' => 1, 'sections' => 6],
            ['name' => 'يوسف الشمري', 'email' => 'yousef@himam.test', 'avatar' => 'assets/avatar-2.svg', 'level' => 1, 'sections' => 5],
        ];

        $sections = BookSection::with('questions.options')
            ->join('books', 'books.id', '=', 'book_sections.book_id')
            ->orderBy('books.position')
            ->orderBy('book_sections.position')
            ->select('book_sections.*')
            ->get();

        foreach ($students as $student) {
            $user = $this->createUser([
                'name' => $student['name'],
                'email' => $student['email'],
                'role' => 'student',
                'avatar' => $student['avatar'],
                'city' => 'الرياض',
                'level_id' => $levels[$student['level'] - 1]->id ?? null,
            ]);

            foreach ($sections->take($student['sections']) as $section) {
                $this->passSection($user, $section);
            }
        }
    }

    private function createUser(array $attributes): User
    {
        $user = User::create([
            'password' => 'password',
            'locale' => 'ar',
            'email_verified_at' => now(),
            ...$attributes,
        ]);

        foreach (NotificationPreference::CATEGORIES as $category) {
            $user->notificationPreferences()->create(['category' => $category, 'enabled' => true]);
        }

        return $user;
    }

    /**
     * Answers every question correctly and submits, so the attempt goes through
     * the same grading path a real reader would take.
     */
    private function passSection(User $user, BookSection $section): void
    {
        $answers = $section->questions
            ->mapWithKeys(fn ($question) => [
                $question->id => $question->options->firstWhere('is_correct', true)?->id,
            ])
            ->filter()
            ->all();

        if ($answers === []) {
            return;
        }

        $this->progress->submitQuiz($user, $section, $answers);
    }
}
