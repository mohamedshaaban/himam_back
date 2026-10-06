<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\Concerns\Translates;
use Illuminate\Database\Seeder;

/**
 * One programme of each type, so the three behaviours can be seen rather than
 * described: an open one, a locked chain, and one that is invisible unless you
 * were assigned it.
 */
class ProgramSeeder extends Seeder
{
    use Translates;

    public function run(): void
    {
        $books = Book::query()->orderBy('position')->orderBy('id')->get();

        if ($books->isEmpty()) {
            return;
        }

        $admin = User::where('role', 'admin')->first();

        $general = Program::updateOrCreate(
            ['type' => Program::GENERAL, 'position' => 0],
            [
                'title' => $this->tr('البرنامج العام', 'General programme', 'Programme général', 'عام پروگرام'),
                'description' => $this->tr(
                    'مفتوح للجميع، وتقرأ كتبه بالترتيب الذي تختاره.',
                    'Open to everyone, and its books may be read in any order.',
                    'Ouvert à tous ; ses livres se lisent dans l’ordre que vous choisissez.',
                    'سب کے لیے کھلا، اور اس کی کتابیں جس ترتیب سے چاہیں پڑھی جا سکتی ہیں۔',
                ),
                'is_public' => true,
                'is_active' => true,
                'created_by' => $admin?->id,
            ],
        );
        $this->attach($general, $books->take(3));

        $sequential = Program::updateOrCreate(
            ['type' => Program::SEQUENTIAL, 'position' => 1],
            [
                'title' => $this->tr('المسار المنهجي', 'Sequential path', 'Parcours méthodique', 'منہجی راستہ'),
                'description' => $this->tr(
                    'يُفتح كل كتاب بعد إتمام الذي قبله.',
                    'Each book unlocks once the one before it is finished.',
                    'Chaque livre se débloque une fois le précédent terminé.',
                    'ہر کتاب پچھلی کتاب مکمل ہونے پر کھلتی ہے۔',
                ),
                'is_public' => true,
                'is_active' => true,
                'created_by' => $admin?->id,
            ],
        );
        $this->attach($sequential, $books->slice(0, 4));

        $selective = Program::updateOrCreate(
            ['type' => Program::SELECTIVE, 'position' => 2],
            [
                'title' => $this->tr('المسار الانتقائي', 'Selective path', 'Parcours sélectif', 'انتقائی راستہ'),
                'description' => $this->tr(
                    'يُسنده المشرف إلى قرّاء بعينهم، ولا يظهر لغيرهم.',
                    'Assigned by a supervisor to particular readers, and hidden from everyone else.',
                    'Attribué par un superviseur à des lecteurs choisis, et masqué pour les autres.',
                    'نگران کی جانب سے مخصوص قارئین کو دیا جاتا ہے، باقی سب سے پوشیدہ۔',
                ),
                'is_public' => false,
                'is_active' => true,
                'created_by' => $admin?->id,
            ],
        );
        $this->attach($selective, $books->slice(2, 3));

        // One assigned reader, so the "hidden from everyone else" half of the
        // rule is observable rather than theoretical.
        $reader = User::where('email', 'mohammed@himam.test')->first();

        if ($reader) {
            $selective->members()->syncWithoutDetaching([
                $reader->id => [
                    'source' => Program::ASSIGNED,
                    'assigned_by' => $admin?->id,
                    'assigned_at' => now(),
                ],
            ]);
        }
    }

    private function attach(Program $program, $books): void
    {
        $program->books()->sync(
            $books->values()
                ->mapWithKeys(fn (Book $book, int $index) => [$book->id => ['order_index' => $index]])
                ->all()
        );
    }
}
