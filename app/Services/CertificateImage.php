<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\QuizAttempt;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;

/**
 * Draws one reader's certificate.
 *
 * The artwork is a template on disk; this fills in what differs between one
 * certificate and the next — who it is for, what for, at what level, how well,
 * its serial and its date — and hands back a complete document.
 *
 * SVG rather than a raster image, for two reasons. The text stays text, so the
 * renderer shapes the Arabic and the result is sharp at any size, from a phone
 * list to an A4 print. And it can be produced in PHP with no image extension:
 * rasterising Arabic needs a shaping library that is not installed, and GD
 * would draw the letters unjoined and in the wrong order.
 */
class CertificateImage
{
    private const TEMPLATE = __DIR__.'/../../database/seeders/assets/certificate-template.svg';

    private const SERIF = "Georgia, 'Times New Roman', serif";

    public function render(Certificate $certificate): string
    {
        $svg = file_get_contents(self::TEMPLATE);

        return str_replace('</svg>', $this->layer($certificate)."\n</svg>\n", $svg);
    }

    /**
     * The text, positioned against the template's own ruled lines: the holder
     * sits on the rule at y=470 and the title on the one at y=556.
     */
    private function layer(Certificate $certificate): string
    {
        // Arabic needs its base direction stated. Without it the renderer reads
        // the line left to right and a date comes out reordered around its
        // numerals — "17 October 2026" as "October 2026 17".
        $rtl = 'direction="rtl"';

        $holder = $certificate->user?->name ?? '';
        $title = $certificate->t('title') ?? '';
        $level = $certificate->level?->t('name') ?? $certificate->book?->level?->t('name');

        // The sitting this certificate speaks for, so the mark and the date
        // describe one paper rather than two different occasions.
        $attempt = $this->bestAttempt($certificate);

        // The date of the exam, not of the paperwork. A level certificate has
        // no single sitting behind it, so it keeps the day it was issued.
        $date = ($attempt?->created_at ?? $certificate->issued_at)?->translatedFormat('j F Y') ?? '';

        $lines = [
            // The serial sits under the header rather than beside the date: it
            // is a Latin code next to Arabic, and the two in one line are
            // reordered whichever base direction wins. At the foot it fouled
            // the seal's ribbons.
            $this->text(561, 186, 13, '#A09B8C', $certificate->serial, 'direction="ltr" letter-spacing="1.5"'),

            $this->text(561, 396, 17, '#6B6A63', __('This is to certify that'), $rtl),
            $this->text(561, 442, 40, '#143353', $holder, $rtl.' font-weight="bold"'),
            $this->text(561, 498, 17, '#6B6A63', __('has successfully completed'), $rtl),
            $this->text(561, 538, 26, '#1E4E79', $title, $rtl),
        ];

        if ($level) {
            $lines[] = $this->text(561, 572, 15, '#6B6A63', $level, $rtl);
        }

        if ($grade = $this->grade($attempt)) {
            $lines[] = $this->text(561, 602, 16, '#A6821B', $grade, $rtl.' font-weight="bold"');
        }

        $lines[] = $this->text(561, 626, 13, '#8A8678', $date, $rtl);

        return "\n  <!-- Filled per certificate by CertificateImage. -->\n  <g>\n    "
            .implode("\n    ", $lines)
            ."\n  </g>"
            .$this->qr($certificate);
    }

    /**
     * The verification QR.
     *
     * Drawn as squares in the document's own coordinates rather than embedded
     * as an image, so it scales with everything else and survives printing,
     * where a bitmap at this size would not.
     *
     * Medium error correction, which tolerates roughly 15% of the code being
     * obscured — enough for a fold or a thumbprint on a printed copy.
     */
    private function qr(Certificate $certificate): string
    {
        $url = url('/api/certificates/verify/'.$certificate->verification_code);

        $matrix = Encoder::encode($url, ErrorCorrectionLevel::M())->getMatrix();
        $count = $matrix->getWidth();

        // 92 units square, lower left: inside the border, clear of the seal in
        // the middle and above the signature rule, which it used to cross.
        $size = 92;
        $originX = 96;
        $originY = 552;
        $cell = $size / $count;

        $squares = '';
        for ($y = 0; $y < $count; $y++) {
            for ($x = 0; $x < $count; $x++) {
                if (! $matrix->get($x, $y)) {
                    continue;
                }

                // The half-unit overlap closes the hairline gaps a renderer
                // leaves between rects at fractional coordinates, which a
                // scanner reads as broken modules.
                $squares .= sprintf(
                    '<rect x="%.2f" y="%.2f" width="%.2f" height="%.2f"/>',
                    $originX + $x * $cell,
                    $originY + $y * $cell,
                    $cell + 0.5,
                    $cell + 0.5
                );
            }
        }

        $caption = htmlspecialchars(__('Scan to verify'), ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return "\n  <!-- Verification QR -->\n  <g>\n"
            .sprintf('    <rect x="%d" y="%d" width="%d" height="%d" fill="#FFFFFF"/>',
                $originX - 7, $originY - 7, $size + 14, $size + 14)."\n"
            .'    <g fill="#143353">'.$squares."</g>\n"
            .sprintf(
                '    <text x="%d" y="%d" text-anchor="middle" font-family="%s" font-size="11" fill="#A09B8C" direction="rtl">%s</text>',
                (int) ($originX + $size / 2), $originY + $size + 22, self::SERIF, $caption
            )."\n  </g>";
    }

    /**
     * The sitting a certificate speaks for: the best passing attempt at that
     * book's exam.
     *
     * A level certificate is earned by finishing several books rather than by
     * one paper, so there is none — and neither is there for a book with no
     * passing attempt on record.
     */
    private function bestAttempt(Certificate $certificate): ?QuizAttempt
    {
        if (! $certificate->book_id || ! $certificate->user_id) {
            return null;
        }

        return QuizAttempt::where('user_id', $certificate->user_id)
            ->where('book_id', $certificate->book_id)
            ->where('passed', true)
            ->orderByDesc('score')
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * How well that sitting went.
     */
    private function grade(?QuizAttempt $best): ?string
    {
        if (! $best || ! $best->total) {
            return null;
        }

        $percent = (int) round($best->score / $best->total * 100);

        $word = match (true) {
            $percent >= 90 => __('Excellent'),
            $percent >= 80 => __('Very good'),
            $percent >= 70 => __('Good'),
            default => __('Pass'),
        };

        return sprintf('%s — %d%%', $word, $percent);
    }

    private function text(int $x, int $y, int $size, string $fill, string $content, string $extra = ''): string
    {
        $safe = htmlspecialchars($content, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return sprintf(
            '<text x="%d" y="%d" text-anchor="middle" font-family="%s" font-size="%d" fill="%s"%s>%s</text>',
            $x, $y, self::SERIF, $size, $fill, $extra ? ' '.$extra : '', $safe
        );
    }
}
