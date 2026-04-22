<?php

namespace Tests\Feature;

use App\Jobs\DeliverMessageRecipientEmail;
use App\Models\Hierarchy;
use App\Models\Message;
use App\Models\MessageRecipient;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Messaging\MessageCenterService;
use App\Services\Messaging\MessagingSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MessageCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_downward_messages_are_scoped_to_the_selected_branch(): void
    {
        Queue::fake();

        $batchLeader = $this->createUser(User::ROLE_BATCH_LEADER, ['name' => 'Batch Leader']);
        $batch = $this->createHierarchy('Batch 2026', 'batch', $batchLeader);

        $teamAlphaLeader = $this->createUser(User::ROLE_TEAM_LEADER, ['name' => 'Team Alpha Leader']);
        $teamBravoLeader = $this->createUser(User::ROLE_TEAM_LEADER, ['name' => 'Team Bravo Leader']);
        $outsideBatchLeader = $this->createUser(User::ROLE_BATCH_LEADER, ['name' => 'Outside Batch Leader']);
        $outsideTeamLeader = $this->createUser(User::ROLE_TEAM_LEADER, ['name' => 'Outside Team Leader']);

        $teamAlpha = $this->createHierarchy('Team Alpha', 'team', $teamAlphaLeader, $batch);
        $teamBravo = $this->createHierarchy('Team Bravo', 'team', $teamBravoLeader, $batch);
        $outsideBatch = $this->createHierarchy('Outside Batch', 'batch', $outsideBatchLeader);
        $outsideTeam = $this->createHierarchy('Outside Team', 'team', $outsideTeamLeader, $outsideBatch);

        $alphaMember = $this->createUser(User::ROLE_MEMBER, [
            'name' => 'Alpha Member',
            'hierarchy_id' => $teamAlpha->id,
        ]);
        $bravoMember = $this->createUser(User::ROLE_MEMBER, [
            'name' => 'Bravo Member',
            'hierarchy_id' => $teamBravo->id,
        ]);
        $outsideMember = $this->createUser(User::ROLE_MEMBER, [
            'name' => 'Outside Member',
            'hierarchy_id' => $outsideTeam->id,
        ]);

        $previewRecipients = app(MessageCenterService::class)->previewDownwardRecipients($batchLeader, [
            'hierarchy_ids' => [$teamAlpha->id],
        ]);

        $this->assertEqualsCanonicalizing(
            [$teamAlphaLeader->id, $alphaMember->id],
            $previewRecipients->pluck('id')->all(),
        );

        $invalidStoreResponse = $this->actingAs($batchLeader)->post(route('messages.store'), $this->composePayload([
            'subject' => 'Branch update',
            'body' => 'Hello branch',
            'hierarchy_ids' => [$teamAlpha->id],
            'recipient_ids' => [$alphaMember->id, $outsideMember->id],
        ]));

        $invalidStoreResponse->assertSessionHasErrors('recipient_ids.1');

        $storeResponse = $this->actingAs($batchLeader)->post(route('messages.store'), $this->composePayload([
            'subject' => 'Branch update',
            'body' => 'Hello branch',
            'hierarchy_ids' => [$teamAlpha->id],
            'recipient_ids' => [$teamAlphaLeader->id, $alphaMember->id],
        ]));

        $storeResponse->assertRedirect();
        $storeResponse->assertSessionHasNoErrors();

        $message = Message::query()->with('recipients')->latest('id')->firstOrFail();

        $this->assertSame(Message::DIRECTION_DOWNWARD, $message->direction);
        $this->assertEqualsCanonicalizing(
            [$teamAlphaLeader->id, $alphaMember->id],
            $message->recipients->pluck('recipient_id')->all(),
        );
    }

    public function test_upward_messages_go_to_the_immediate_parent_leader(): void
    {
        Queue::fake();

        $batchLeader = $this->createUser(User::ROLE_BATCH_LEADER, ['name' => 'Batch Leader']);
        $batch = $this->createHierarchy('Batch 2026', 'batch', $batchLeader);

        $teamLeader = $this->createUser(User::ROLE_TEAM_LEADER, ['name' => 'Team Leader']);
        $this->createHierarchy('Team Alpha', 'team', $teamLeader, $batch);

        $response = $this->actingAs($teamLeader)->post(route('messages.store'), $this->composePayload([
            'direction' => Message::DIRECTION_UPWARD,
            'subject' => 'Need help',
            'body' => 'Please advise',
        ]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $message = Message::query()->with('recipients')->latest('id')->firstOrFail();

        $this->assertSame(Message::DIRECTION_UPWARD, $message->direction);
        $this->assertEquals([$batchLeader->id], $message->recipients->pluck('recipient_id')->all());
    }

    public function test_upward_messages_escalate_to_admins_when_no_parent_leader_exists(): void
    {
        Queue::fake();

        $admin = $this->createUser(User::ROLE_ADMIN, ['name' => 'Platform Admin']);
        $teamLeader = $this->createUser(User::ROLE_TEAM_LEADER, ['name' => 'Solo Team Leader']);
        $this->createHierarchy('Solo Team', 'team', $teamLeader);

        $response = $this->actingAs($teamLeader)->post(route('messages.store'), $this->composePayload([
            'direction' => Message::DIRECTION_UPWARD,
            'subject' => 'Need escalation',
            'body' => 'Escalating upward',
        ]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $message = Message::query()->with('recipients')->latest('id')->firstOrFail();

        $this->assertSame(Message::DIRECTION_UPWARD, $message->direction);
        $this->assertEquals([$admin->id], $message->recipients->pluck('recipient_id')->all());
    }

    public function test_non_participants_cannot_view_a_thread(): void
    {
        Queue::fake();

        $batchLeader = $this->createUser(User::ROLE_BATCH_LEADER, ['name' => 'Batch Leader']);
        $batch = $this->createHierarchy('Batch 2026', 'batch', $batchLeader);

        $teamLeader = $this->createUser(User::ROLE_TEAM_LEADER, ['name' => 'Team Leader']);
        $team = $this->createHierarchy('Team Alpha', 'team', $teamLeader, $batch);

        $member = $this->createUser(User::ROLE_MEMBER, [
            'name' => 'Thread Starter',
            'hierarchy_id' => $team->id,
        ]);

        $outsiderLeader = $this->createUser(User::ROLE_TEAM_LEADER, ['name' => 'Outsider Leader']);
        $outsiderTeam = $this->createHierarchy('Outside Team', 'team', $outsiderLeader);
        $outsider = $this->createUser(User::ROLE_MEMBER, [
            'name' => 'Outsider',
            'hierarchy_id' => $outsiderTeam->id,
        ]);

        $this->actingAs($member)->post(route('messages.store'), $this->composePayload([
            'direction' => Message::DIRECTION_UPWARD,
            'subject' => 'Private thread',
            'body' => 'For my leader only',
        ]))->assertRedirect();

        $message = Message::query()->latest('id')->firstOrFail();

        $this->assertTrue(Gate::forUser($outsider)->denies('view', $message));
        $this->assertTrue(Gate::forUser($member)->allows('view', $message));
        $this->assertTrue(Gate::forUser($teamLeader)->allows('view', $message));
    }

    public function test_delivery_preferences_control_inbox_delivery_and_email_queueing(): void
    {
        Queue::fake();

        SystemSetting::query()->updateOrCreate(
            ['key' => MessagingSettings::KEY_EMAIL_ENABLED],
            ['value' => '1'],
        );

        $admin = $this->createUser(User::ROLE_ADMIN, ['name' => 'Platform Admin']);
        $teamLeader = $this->createUser(User::ROLE_TEAM_LEADER, ['name' => 'Team Leader']);
        $team = $this->createHierarchy('Delivery Team', 'team', $teamLeader);

        $inboxOnlyRecipient = $this->createUser(User::ROLE_MEMBER, [
            'name' => 'Inbox Only',
            'hierarchy_id' => $team->id,
            'message_delivery_preference' => User::MESSAGE_DELIVERY_INBOX,
        ]);
        $emailOnlyRecipient = $this->createUser(User::ROLE_MEMBER, [
            'name' => 'Email Only',
            'hierarchy_id' => $team->id,
            'message_delivery_preference' => User::MESSAGE_DELIVERY_EMAIL,
        ]);
        $unverifiedRecipient = $this->createUser(User::ROLE_MEMBER, [
            'name' => 'Unverified Recipient',
            'hierarchy_id' => $team->id,
            'message_delivery_preference' => User::MESSAGE_DELIVERY_BOTH,
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($admin)->post(route('messages.store'), $this->composePayload([
            'subject' => 'Delivery settings',
            'body' => 'Testing delivery behavior',
            'hierarchy_ids' => [$team->id],
            'recipient_ids' => [
                $inboxOnlyRecipient->id,
                $emailOnlyRecipient->id,
                $unverifiedRecipient->id,
            ],
        ]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $message = Message::query()->latest('id')->firstOrFail();
        $rows = MessageRecipient::query()
            ->where('message_id', $message->id)
            ->get()
            ->keyBy('recipient_id');

        $this->assertSame(User::MESSAGE_DELIVERY_INBOX, $rows[$inboxOnlyRecipient->id]->delivery_preference_snapshot);
        $this->assertNotNull($rows[$inboxOnlyRecipient->id]->inbox_delivered_at);
        $this->assertSame(MessageRecipient::EMAIL_STATUS_SKIPPED, $rows[$inboxOnlyRecipient->id]->email_status);
        $this->assertNull($rows[$inboxOnlyRecipient->id]->email_failure);

        $this->assertSame(User::MESSAGE_DELIVERY_EMAIL, $rows[$emailOnlyRecipient->id]->delivery_preference_snapshot);
        $this->assertNull($rows[$emailOnlyRecipient->id]->inbox_delivered_at);
        $this->assertSame(MessageRecipient::EMAIL_STATUS_PENDING, $rows[$emailOnlyRecipient->id]->email_status);

        $this->assertSame(User::MESSAGE_DELIVERY_BOTH, $rows[$unverifiedRecipient->id]->delivery_preference_snapshot);
        $this->assertNotNull($rows[$unverifiedRecipient->id]->inbox_delivered_at);
        $this->assertSame(MessageRecipient::EMAIL_STATUS_SKIPPED, $rows[$unverifiedRecipient->id]->email_status);
        $this->assertSame(
            'Recipient does not have a verified email address.',
            $rows[$unverifiedRecipient->id]->email_failure,
        );

        Queue::assertPushed(DeliverMessageRecipientEmail::class, 1);
    }

    public function test_leader_replies_stay_in_the_thread_and_only_target_thread_participants(): void
    {
        Queue::fake();

        $batchLeader = $this->createUser(User::ROLE_BATCH_LEADER, ['name' => 'Batch Leader']);
        $batch = $this->createHierarchy('Batch 2026', 'batch', $batchLeader);

        $teamLeader = $this->createUser(User::ROLE_TEAM_LEADER, ['name' => 'Team Leader']);
        $this->createHierarchy('Team Alpha', 'team', $teamLeader, $batch);
        $extraMember = $this->createUser(User::ROLE_MEMBER, [
            'name' => 'Extra Branch Member',
            'hierarchy_id' => $teamLeader->hierarchy_id,
        ]);

        $this->actingAs($teamLeader)->post(route('messages.store'), $this->composePayload([
            'direction' => Message::DIRECTION_UPWARD,
            'subject' => 'Need guidance',
            'body' => 'Can you help?',
        ]))->assertRedirect();

        $threadRoot = Message::query()->latest('id')->firstOrFail();
        $messageCenter = app(MessageCenterService::class);
        $replyRecipients = $messageCenter->previewReplyRecipients($batchLeader, $threadRoot);

        $this->assertEquals([$teamLeader->id], $replyRecipients->pluck('id')->all());
        $this->assertFalse($replyRecipients->pluck('id')->contains($extraMember->id));

        $reply = $messageCenter->sendReply($batchLeader, $threadRoot, [
            'subject' => 'Re: Need guidance',
            'body' => 'Yes, here is the plan.',
        ]);

        $this->assertSame($threadRoot->id, $reply->thread_root_id);
        $this->assertSame($threadRoot->id, $reply->parent_message_id);
        $this->assertSame(Message::DIRECTION_DOWNWARD, $reply->direction);
        $this->assertEquals([$teamLeader->id], $reply->recipients->pluck('recipient_id')->all());
    }

    public function test_rendered_message_content_is_snapshotted_for_recipients(): void
    {
        Queue::fake();

        $batchLeader = $this->createUser(User::ROLE_BATCH_LEADER, ['name' => 'Batch Leader']);
        $batch = $this->createHierarchy('Batch Root', 'batch', $batchLeader);

        $teamLeader = $this->createUser(User::ROLE_TEAM_LEADER, ['name' => 'Team Leader']);
        $team = $this->createHierarchy('Team Light', 'team', $teamLeader, $batch);

        $member = $this->createUser(User::ROLE_MEMBER, [
            'name' => 'Original Disciple',
            'hierarchy_id' => $team->id,
        ]);

        $subjectTemplate = 'Hello {{ user.name }}';
        $bodyTemplate = 'Group {{ hierarchy.name }} under {{ parent_hierarchy.name }} leader {{ leader.name }}.';

        $this->actingAs($batchLeader)->post(route('messages.store'), $this->composePayload([
            'subject' => $subjectTemplate,
            'body' => $bodyTemplate,
            'hierarchy_ids' => [$team->id],
            'recipient_ids' => [$member->id],
        ]))->assertRedirect();

        $message = Message::query()->latest('id')->firstOrFail();
        $recipientRow = MessageRecipient::query()
            ->where('message_id', $message->id)
            ->where('recipient_id', $member->id)
            ->firstOrFail();

        $originalRenderedBody = $recipientRow->rendered_body;

        $this->assertSame('Hello Original Disciple', $recipientRow->rendered_subject);
        $this->assertStringContainsString('Team Light', $originalRenderedBody);
        $this->assertStringContainsString('Batch Root', $originalRenderedBody);

        $member->update(['name' => 'Updated Disciple']);
        $team->update(['name' => 'Renamed Team']);
        $batch->update(['name' => 'Renamed Batch']);
        $batchLeader->update(['name' => 'Updated Leader']);

        $recipientRow->refresh();

        $this->assertSame('Hello Original Disciple', $recipientRow->rendered_subject);
        $this->assertSame($originalRenderedBody, $recipientRow->rendered_body);

        $this->actingAs($member)
            ->get(route('messages.inbox'))
            ->assertOk()
            ->assertSeeText('Hello Original Disciple')
            ->assertDontSeeText('Hello Updated Disciple')
            ->assertSeeText($originalRenderedBody)
            ->assertDontSeeText('Renamed Team')
            ->assertDontSeeText('Renamed Batch');
    }

    public function test_viewing_a_thread_marks_the_recipient_copy_as_read(): void
    {
        Queue::fake();

        $batchLeader = $this->createUser(User::ROLE_BATCH_LEADER, ['name' => 'Batch Leader']);
        $batch = $this->createHierarchy('Batch 2026', 'batch', $batchLeader);

        $teamLeader = $this->createUser(User::ROLE_TEAM_LEADER, ['name' => 'Team Leader']);
        $team = $this->createHierarchy('Team Alpha', 'team', $teamLeader, $batch);

        $member = $this->createUser(User::ROLE_MEMBER, [
            'name' => 'Reader Member',
            'hierarchy_id' => $team->id,
        ]);

        $this->actingAs($teamLeader)->post(route('messages.store'), $this->composePayload([
            'subject' => 'Unread message',
            'body' => 'Please read this update.',
            'hierarchy_ids' => [$team->id],
            'recipient_ids' => [$member->id],
        ]))->assertRedirect();

        $message = Message::query()->latest('id')->firstOrFail();
        $recipientRow = MessageRecipient::query()
            ->where('message_id', $message->id)
            ->where('recipient_id', $member->id)
            ->firstOrFail();

        $this->assertNull($recipientRow->read_at);

        $this->actingAs($member)
            ->get(route('messages.show', $message))
            ->assertOk();

        $this->assertNotNull($recipientRow->fresh()->read_at);
    }

    public function test_admin_can_update_messaging_defaults_and_locked_users_keep_their_preference(): void
    {
        $admin = $this->createUser(User::ROLE_ADMIN, ['name' => 'Platform Admin']);
        $lockedUser = $this->createUser(User::ROLE_MEMBER, [
            'name' => 'Locked User',
            'message_delivery_preference' => User::MESSAGE_DELIVERY_INBOX,
            'message_delivery_preference_locked' => true,
        ]);

        $this->actingAs($admin)->put(route('admin.messages.settings.update'), [
            'default_delivery' => User::MESSAGE_DELIVERY_EMAIL,
            'email_enabled' => 0,
        ])->assertRedirect();

        $this->assertDatabaseHas('system_settings', [
            'key' => MessagingSettings::KEY_DEFAULT_DELIVERY,
            'value' => User::MESSAGE_DELIVERY_EMAIL,
        ]);
        $this->assertDatabaseHas('system_settings', [
            'key' => MessagingSettings::KEY_EMAIL_ENABLED,
            'value' => '0',
        ]);

        $this->actingAs($lockedUser)->patch(route('profile.update'), [
            'name' => $lockedUser->name,
            'email' => $lockedUser->email,
            'message_delivery_preference' => User::MESSAGE_DELIVERY_EMAIL,
        ])->assertRedirect(route('profile.edit'));

        $this->assertSame(
            User::MESSAGE_DELIVERY_INBOX,
            $lockedUser->fresh()->message_delivery_preference,
        );
    }

    private function composePayload(array $overrides = []): array
    {
        return array_merge([
            'direction' => Message::DIRECTION_DOWNWARD,
            'template_id' => '',
            'subject' => 'Test subject',
            'body' => 'Test body',
            'hierarchy_ids' => [],
            'roles' => [],
            'active_state' => '',
            'active_plan_id' => '',
            'plan_type' => '',
            'training_status' => '',
            'pace_status' => '',
        ], $overrides);
    }

    private function createUser(string $role, array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => $role,
        ], $attributes));
    }

    private function createHierarchy(string $name, string $type, User $leader, ?Hierarchy $parent = null): Hierarchy
    {
        $hierarchy = Hierarchy::query()->create([
            'name' => $name,
            'type' => $type,
            'leader_id' => $leader->id,
            'parent_id' => $parent?->id,
        ]);

        $leader->update(['hierarchy_id' => $hierarchy->id]);

        return $hierarchy;
    }
}
