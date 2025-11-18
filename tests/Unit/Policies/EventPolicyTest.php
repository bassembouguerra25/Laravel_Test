<?php

namespace Tests\Unit\Policies;

use App\Models\Event;
use App\Policies\EventPolicy;
use Tests\TestCase;

/**
 * Event Policy Test
 *
 * Tests Event authorization rules
 */
class EventPolicyTest extends TestCase
{
    protected EventPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new EventPolicy;
    }

    /**
     * Test admin can create events
     */
    public function test_admin_can_create_events(): void
    {
        $admin = $this->createAdmin();

        $this->assertTrue($this->policy->create($admin));
    }

    /**
     * Test organizer can create events
     */
    public function test_organizer_can_create_events(): void
    {
        $organizer = $this->createOrganizer();

        $this->assertTrue($this->policy->create($organizer));
    }

    /**
     * Test customer cannot create events
     */
    public function test_customer_cannot_create_events(): void
    {
        $customer = $this->createCustomer();

        $this->assertFalse($this->policy->create($customer));
    }

    /**
     * Test admin can update any event
     */
    public function test_admin_can_update_any_event(): void
    {
        $admin = $this->createAdmin();
        $organizer = $this->createOrganizer();
        $event = Event::factory()->forOrganizer($organizer)->create();

        $this->assertTrue($this->policy->update($admin, $event));
    }

    /**
     * Test organizer can update their own event
     */
    public function test_organizer_can_update_their_own_event(): void
    {
        $organizer = $this->createOrganizer();
        $event = Event::factory()->forOrganizer($organizer)->create();

        $this->assertTrue($this->policy->update($organizer, $event));
    }

    /**
     * Test organizer cannot update other organizer's event
     */
    public function test_organizer_cannot_update_other_organizer_event(): void
    {
        $organizer1 = $this->createOrganizer();
        $organizer2 = $this->createOrganizer();
        $event = Event::factory()->forOrganizer($organizer2)->create();

        $this->assertFalse($this->policy->update($organizer1, $event));
    }

    /**
     * Test customer cannot update event
     */
    public function test_customer_cannot_update_event(): void
    {
        $customer = $this->createCustomer();
        $organizer = $this->createOrganizer();
        $event = Event::factory()->forOrganizer($organizer)->create();

        $this->assertFalse($this->policy->update($customer, $event));
    }

    /**
     * Test admin can delete any event
     */
    public function test_admin_can_delete_any_event(): void
    {
        $admin = $this->createAdmin();
        $organizer = $this->createOrganizer();
        $event = Event::factory()->forOrganizer($organizer)->create();

        $this->assertTrue($this->policy->delete($admin, $event));
    }

    /**
     * Test organizer can delete their own event
     */
    public function test_organizer_can_delete_their_own_event(): void
    {
        $organizer = $this->createOrganizer();
        $event = Event::factory()->forOrganizer($organizer)->create();

        $this->assertTrue($this->policy->delete($organizer, $event));
    }

    /**
     * Test organizer cannot delete other organizer's event
     */
    public function test_organizer_cannot_delete_other_organizer_event(): void
    {
        $organizer1 = $this->createOrganizer();
        $organizer2 = $this->createOrganizer();
        $event = Event::factory()->forOrganizer($organizer2)->create();

        $this->assertFalse($this->policy->delete($organizer1, $event));
    }

    /**
     * Test all users can view events
     */
    public function test_all_users_can_view_events(): void
    {
        $admin = $this->createAdmin();
        $organizer = $this->createOrganizer();
        $customer = $this->createCustomer();
        $event = Event::factory()->create();

        $this->assertTrue($this->policy->viewAny($admin));
        $this->assertTrue($this->policy->viewAny($organizer));
        $this->assertTrue($this->policy->viewAny($customer));
        $this->assertTrue($this->policy->view($admin, $event));
        $this->assertTrue($this->policy->view($organizer, $event));
        $this->assertTrue($this->policy->view($customer, $event));
    }
}
