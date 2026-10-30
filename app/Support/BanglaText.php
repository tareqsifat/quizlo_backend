<?php

namespace App\Support;

/**
 * Repairs Bangla text damaged by legacy (Bijoy/ANSI) to Unicode conversion and PDF copy-paste.
 * Only unambiguous sequences are rewritten; anything that needs a human is left alone.
 */
class BanglaText
{
    private const RULES = [
        // ে + া / া + ে → ো and ে + ৗ → ৌ (o-kar / au-kar stored as two halves, in either order)
        '/\x{09C7}\x{09BE}|\x{09BE}\x{09C7}/u' => "\u{09CB}",
        '/\x{09C7}\x{09D7}/u' => "\u{09CC}",
        // ZWJ between a consonant (except র, where র‍্য is the correct spelling of e.g. র‍্যাব) and hasanta: ক‍্য → ক্য
        '/([\x{0995}-\x{09AF}\x{09B2}-\x{09B9}\x{09DC}-\x{09DF}])\x{200D}(?=\x{09CD})/u' => '$1',
        // ZWNJ between র and ্য blocks the reph: কার‌্য → কার্য
        '/\x{09B0}\x{200C}(?=\x{09CD}\x{09AF})/u' => "\u{09B0}",
        // ZWNJ in front of a vowel sign detaches it from its consonant: পাস্ত‌ুর → পাস্তুর
        '/\x{200C}(?=[\x{09BE}-\x{09CC}\x{09D7}])/u' => '',
        // Hasanta right after a vowel sign is never valid: বৃ্দ্ধি → বৃদ্ধি
        '/([\x{09BE}-\x{09CC}])\x{09CD}(?![\x{200C}\x{200D}])/u' => '$1',
        // The same vowel sign typed twice: সমবাহুু → সমবাহু
        '/([\x{09BE}-\x{09CC}])\1+/u' => '$1',
    ];

    public static function fix(?string $text): ?string
    {
        if ($text === null || !preg_match('/[\x{0980}-\x{09FF}]/u', $text)) {
            return $text;
        }

        return preg_replace(array_keys(self::RULES), array_values(self::RULES), $text);
    }
}
