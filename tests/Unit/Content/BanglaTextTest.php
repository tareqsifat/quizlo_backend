<?php

namespace Tests\Unit\Content;

use App\Support\BanglaText;
use PHPUnit\Framework\TestCase;

class BanglaTextTest extends TestCase
{
    public function test_repairs_broken_sequences_found_in_the_question_bank(): void
    {
        $cases = [
            "\u{0998}\u{09BE}\u{09C7}\u{09DC}\u{09BE}\u{09B0}"                        => "\u{0998}\u{09CB}\u{09DC}\u{09BE}\u{09B0}", // ঘােড়ার → ঘোড়ার
            "\u{0995}\u{09C7}\u{09BE}\u{09A8}"                                        => 'কোন',        // ে+া
            "\u{09B8}\u{09AD}\u{200D}\u{09CD}\u{09AF}\u{09A4}\u{09BE}"                => 'সভ্যতা',     // ZWJ before hasanta
            "\u{0995}\u{09BE}\u{09B0}\u{200C}\u{09CD}\u{09AF}"                        => 'কার্য',       // ZWNJ blocking reph
            "\u{09AA}\u{09BE}\u{09B8}\u{09CD}\u{09A4}\u{200C}\u{09C1}\u{09B0}"        => 'পাস্তুর',     // ZWNJ before vowel sign
            "\u{09AC}\u{09C3}\u{09CD}\u{09A6}\u{09CD}\u{09A7}\u{09BF}"                => 'বৃদ্ধি',      // hasanta after vowel sign
            "\u{09B8}\u{09AE}\u{09AC}\u{09BE}\u{09B9}\u{09C1}\u{09C1}"                => 'সমবাহু',     // doubled vowel sign
        ];

        foreach ($cases as $broken => $expected) {
            $this->assertSame($expected, BanglaText::fix($broken));
        }
    }

    public function test_leaves_valid_text_untouched(): void
    {
        foreach ([
            'কলম্বো',
            'কাঠমান্ডু',
            "\u{09B0}\u{200D}\u{09CD}\u{09AF}\u{09BE}\u{09AC}",   // র‍্যাব keeps its ZWJ
            "\u{09AC}\u{09BE}\u{0997}\u{09CD}\u{200C}\u{09A7}\u{09BE}\u{09B0}\u{09BE}", // বাগ্‌ধারা keeps its visible hasanta
            'Which word is similar to appal?',
            null,
        ] as $text) {
            $this->assertSame($text, BanglaText::fix($text));
        }
    }
}
