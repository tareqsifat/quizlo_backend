<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ExamType;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\LeagueTier;
use App\Models\Achievement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Exam Type
        $examType = ExamType::create([
            'name' => 'BCS Preliminary',
            'name_bn' => 'বিসিএস প্রিলিমিনারি',
            'code' => 'BCS',
            'slug' => 'bcs-preliminary',
            'description' => 'Bangladesh Civil Service Preliminary Examination prep',
            'icon' => 'bcs_icon',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // 2. Seed Subjects
        $english = Subject::create([
            'name' => 'English Grammar',
            'name_bn' => 'ইংরেজি ব্যাকরণ',
            'slug' => 'english-grammar',
            'icon' => 'english_icon',
            'color_hex' => '#4F46E5',
            'is_active' => true,
        ]);

        $bangla = Subject::create([
            'name' => 'Bangla Literature',
            'name_bn' => 'বাংলা সাহিত্য',
            'slug' => 'bangla-literature',
            'icon' => 'bangla_icon',
            'color_hex' => '#EF4444',
            'is_active' => true,
        ]);

        $bangladesh = Subject::create([
            'name' => 'Bangladesh Affairs',
            'name_bn' => 'বাংলাদেশ বিষয়াবলী',
            'slug' => 'bangladesh-affairs',
            'icon' => 'bd_icon',
            'color_hex' => '#10B981',
            'is_active' => true,
        ]);

        // 3. Attach Subjects to Exam Type
        $examType->subjects()->attach($english->id, [
            'is_active' => true,
            'sort_order' => 1,
            'total_marks' => 35,
            'syllabus_note' => 'English Language and Literature'
        ]);

        $examType->subjects()->attach($bangla->id, [
            'is_active' => true,
            'sort_order' => 2,
            'total_marks' => 35,
            'syllabus_note' => 'Bangla Language and Literature'
        ]);

        $examType->subjects()->attach($bangladesh->id, [
            'is_active' => true,
            'sort_order' => 3,
            'total_marks' => 30,
            'syllabus_note' => 'Bangladesh History, Geography, and Constitution'
        ]);

        // 4. Seed Topics
        $grammarTopic = Topic::create([
            'subject_id' => $english->id,
            'name' => 'Parts of Speech',
            'name_bn' => 'পদ প্রকারভেদ',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $banglaTopic = Topic::create([
            'subject_id' => $bangla->id,
            'name' => 'Modern Period',
            'name_bn' => 'আধুনিক যুগ',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // 5. Seed Lessons
        $englishLesson = Lesson::create([
            'exam_type_id' => $examType->id,
            'subject_id' => $english->id,
            'topic_id' => $grammarTopic->id,
            'title' => 'Nouns & Pronouns',
            'title_bn' => 'বিশেষ্য ও সর্বনাম',
            'description' => 'Learn nouns and pronouns basics',
            'xp_reward' => 20,
            'coin_reward' => 10,
            'difficulty' => 'easy',
            'question_count' => 5,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $banglaLesson = Lesson::create([
            'exam_type_id' => $examType->id,
            'subject_id' => $bangla->id,
            'topic_id' => $banglaTopic->id,
            'title' => 'Rabindranath Tagore',
            'title_bn' => 'রবীন্দ্রনাথ ঠাকুর',
            'description' => 'Works of Rabindranath Tagore',
            'xp_reward' => 20,
            'coin_reward' => 10,
            'difficulty' => 'medium',
            'question_count' => 5,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        // 6. Seed Questions & Options
        $q1 = Question::create([
            'subject_id' => $english->id,
            'topic_id' => $grammarTopic->id,
            'lesson_id' => $englishLesson->id,
            'question_text' => 'Which of the following is a noun?',
            'question_bn' => 'নিচের কোনটি বিশেষ্য পদ?',
            'explanation' => 'Happiness is an abstract noun.',
            'difficulty' => 'easy',
            'xp_value' => 10,
            'is_active' => true,
        ]);

        QuestionOption::create(['question_id' => $q1->id, 'option_text' => 'Happy', 'option_text_bn' => 'হ্যাপি', 'is_correct' => false, 'sort_order' => 1]);
        QuestionOption::create(['question_id' => $q1->id, 'option_text' => 'Happily', 'option_text_bn' => 'হ্যাপিলি', 'is_correct' => false, 'sort_order' => 2]);
        QuestionOption::create(['question_id' => $q1->id, 'option_text' => 'Happiness', 'option_text_bn' => 'হ্যাপিনেস', 'is_correct' => true, 'sort_order' => 3]);
        QuestionOption::create(['question_id' => $q1->id, 'option_text' => 'Beautify', 'option_text_bn' => 'বিউটিফাই', 'is_correct' => false, 'sort_order' => 4]);

        $q2 = Question::create([
            'subject_id' => $bangla->id,
            'topic_id' => $banglaTopic->id,
            'lesson_id' => $banglaLesson->id,
            'question_text' => 'Who wrote the novel "Gora"?',
            'question_bn' => '"গোরা" উপন্যাসটি কার লেখা?',
            'explanation' => 'Gora was written by Rabindranath Tagore and published in 1910.',
            'difficulty' => 'medium',
            'xp_value' => 10,
            'is_active' => true,
        ]);

        QuestionOption::create(['question_id' => $q2->id, 'option_text' => 'Kazi Nazrul Islam', 'option_text_bn' => 'কাজী নজরুল ইসলাম', 'is_correct' => false, 'sort_order' => 1]);
        QuestionOption::create(['question_id' => $q2->id, 'option_text' => 'Rabindranath Tagore', 'option_text_bn' => 'রবীন্দ্রনাথ ঠাকুর', 'is_correct' => true, 'sort_order' => 2]);
        QuestionOption::create(['question_id' => $q2->id, 'option_text' => 'Sarat Chandra Chattopadhyay', 'option_text_bn' => 'শরৎচন্দ্র চট্টোপাধ্যায়', 'is_correct' => false, 'sort_order' => 3]);
        QuestionOption::create(['question_id' => $q2->id, 'option_text' => 'Bankim Chandra Chattopadhyay', 'option_text_bn' => 'বঙ্কিমচন্দ্র চট্টোপাধ্যায়', 'is_correct' => false, 'sort_order' => 4]);

        // Tag questions to exam type
        $examType->questions()->attach($q1->id, ['source_batch' => 'BCS-44', 'source_year' => 2022]);
        $examType->questions()->attach($q2->id, ['source_batch' => 'BCS-45', 'source_year' => 2023]);

        // 7. Seed League Tiers
        LeagueTier::create(['name' => 'Bronze', 'name_bn' => 'ব্রোঞ্জ', 'tier_order' => 1, 'color_hex' => '#CD7F32', 'promotion_spots' => 10, 'relegation_spots' => 0, 'max_members' => 30]);
        LeagueTier::create(['name' => 'Silver', 'name_bn' => 'সিলভার', 'tier_order' => 2, 'color_hex' => '#C0C0C0', 'promotion_spots' => 10, 'relegation_spots' => 5, 'max_members' => 30]);
        LeagueTier::create(['name' => 'Gold', 'name_bn' => 'গোল্ড', 'tier_order' => 3, 'color_hex' => '#FFD700', 'promotion_spots' => 10, 'relegation_spots' => 5, 'max_members' => 30]);
        LeagueTier::create(['name' => 'Sapphire', 'name_bn' => 'স্যাফায়ার', 'tier_order' => 4, 'color_hex' => '#0F52BA', 'promotion_spots' => 10, 'relegation_spots' => 5, 'max_members' => 30]);
        LeagueTier::create(['name' => 'Diamond', 'name_bn' => 'ডায়মন্ড', 'tier_order' => 5, 'color_hex' => '#B9F2FF', 'promotion_spots' => 0, 'relegation_spots' => 5, 'max_members' => 30]);

        // 8. Seed Achievements
        Achievement::create(['key' => 'first_win', 'title' => 'First Win', 'title_bn' => 'প্রথম জয়', 'description' => 'Complete your first lesson', 'icon' => 'first_win_badge', 'xp_reward' => 50, 'type' => 'social']);
        Achievement::create(['key' => 'streak_7', 'title' => '7-Day Streak', 'title_bn' => '৭ দিনের ধারাবাহিকতা', 'description' => 'Maintain streak for 7 days', 'icon' => 'streak_7_badge', 'xp_reward' => 100, 'type' => 'streak']);

        // 9. Seed Users
        User::create([
            'name' => 'System Admin',
            'phone' => '01799887766',
            'email' => 'admin@quizlo.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
            'is_active' => true,
        ]);

        User::factory()->create([
            'name' => 'Sajid User',
            'phone' => '01711223344',
            'email' => 'sajid@quizlo.com',
            'daily_goal' => 20,
        ]);

        User::factory(5)->create();

        // 10. Configure Passport Client Secrets
        DB::table('oauth_clients')
            ->where('grant_types', 'like', '%password%')
            ->update(['secret' => null]);
    }
}
