<?php

namespace Tests\Feature\Content;

use Tests\TestCase;
use App\Models\User;
use App\Models\Topic;
use App\Models\Question;
use App\Models\UserAnswer;
use App\Models\ExamTypeSubject;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TopicJourneyTest extends TestCase
{
    use RefreshDatabase;

    private function makeTopic(ExamTypeSubject $ets, int $sortOrder, int $questions): array
    {
        $topic = Topic::factory()->create([
            'exam_type_subject_id' => $ets->id,
            'sort_order' => $sortOrder,
        ]);
        $list = Question::factory()->withOptions()->count($questions)->create([
            'exam_type_subject_id' => $ets->id,
            'topic_id' => $topic->id,
        ]);

        return [$topic, $list];
    }

    private function answer(User $user, ExamTypeSubject $ets, Question $question, bool $correct): void
    {
        UserAnswer::factory()->create([
            'user_id' => $user->id,
            'exam_type_id' => $ets->exam_type_id,
            'question_id' => $question->id,
            'selected_option_id' => $question->options()->where('is_correct', $correct)->first()->id,
            'is_correct' => $correct,
        ]);
    }

    private function getTopics(User $user, ExamTypeSubject $ets)
    {
        return $this->actingAsUser($user)
            ->getJson("/api/v1/subjects/{$ets->subject_id}/topics?exam_type_id={$ets->exam_type_id}")
            ->assertOk();
    }

    public function test_topics_report_distinct_correct_answers_per_topic(): void
    {
        $user = User::factory()->create();
        $ets = ExamTypeSubject::factory()->create();
        [, $first] = $this->makeTopic($ets, 1, 3);
        [, $second] = $this->makeTopic($ets, 2, 2);

        // Same question right twice counts once; wrong answers don't count
        $this->answer($user, $ets, $first[0], true);
        $this->answer($user, $ets, $first[0], true);
        $this->answer($user, $ets, $first[1], true);
        $this->answer($user, $ets, $first[2], false);
        $this->answer($user, $ets, $second[0], false);
        // Another user's answers are not ours
        $this->answer(User::factory()->create(), $ets, $second[1], true);

        $this->getTopics($user, $ets)
            ->assertJsonPath('data.0.question_count', 3)
            ->assertJsonPath('data.0.correct_count', 2)
            ->assertJsonPath('data.1.question_count', 2)
            ->assertJsonPath('data.1.correct_count', 0);
    }

    public function test_completing_a_topic_unlocks_the_next_one(): void
    {
        $user = User::factory()->create();
        $ets = ExamTypeSubject::factory()->create();
        [$first] = $this->makeTopic($ets, 1, 2);
        $this->makeTopic($ets, 2, 2);

        $this->getTopics($user, $ets)
            ->assertJsonPath('data.0.status', 'current')
            ->assertJsonPath('data.1.status', 'locked');

        $this->actingAsUser($user)
            ->postJson("/api/v1/topics/{$first->id}/complete", ['score' => 50])
            ->assertOk();

        $this->getTopics($user, $ets)
            ->assertJsonPath('data.0.status', 'completed')
            ->assertJsonPath('data.1.status', 'current');
    }
}
