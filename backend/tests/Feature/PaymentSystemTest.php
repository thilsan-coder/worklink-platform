<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Job;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Skill;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Payment\PaymentService;
use App\Services\Payment\TestPaymentGateway;
use Database\Seeders\DevelopmentPaymentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentSystemTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private User $otherCustomer;
    private User $worker;
    private User $otherWorker;
    private Category $category;
    private Skill $skill;
    private Job $completedJob;
    private Job $requestedJob;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::create([
            'phone' => '+94771110001',
            'name' => 'Paying Customer',
            'role' => 'customer',
            'is_active' => true,
        ]);

        $this->otherCustomer = User::create([
            'phone' => '+94771110002',
            'name' => 'Other Customer',
            'role' => 'customer',
            'is_active' => true,
        ]);

        $this->worker = User::create([
            'phone' => '+94771110003',
            'name' => 'Paid Worker',
            'role' => 'worker',
            'is_active' => true,
        ]);

        $this->otherWorker = User::create([
            'phone' => '+94771110004',
            'name' => 'Other Worker',
            'role' => 'worker',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'name' => 'Plumbing',
            'slug' => 'plumbing',
            'is_active' => true,
        ]);

        $this->skill = Skill::create([
            'category_id' => $this->category->id,
            'name' => 'Pipe Fitting',
            'slug' => 'pipe-fitting',
            'is_active' => true,
        ]);

        $this->completedJob = Job::create([
            'job_number' => 'WLJ-PAY-001',
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker->id,
            'category_id' => $this->category->id,
            'skill_id' => $this->skill->id,
            'title' => 'Water Leak Fix',
            'description' => 'Fix main bathroom leak',
            'estimated_cost' => 4500.00,
            'final_cost' => 5000.00,
            'status' => 'COMPLETED',
        ]);

        $this->requestedJob = Job::create([
            'job_number' => 'WLJ-PAY-002',
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker->id,
            'category_id' => $this->category->id,
            'title' => 'Pending Job',
            'description' => 'Not completed yet',
            'status' => 'REQUESTED',
        ]);
    }

    /** 1. Customer can view payment details for own completed job */
    public function test_customer_can_view_payment_details_for_own_completed_job(): void
    {
        $response = $this->actingAs($this->customer)->getJson("/api/jobs/{$this->completedJob->id}/payment");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.job_id', $this->completedJob->id)
            ->assertJsonPath('data.amount', 5000)
            ->assertJsonPath('data.currency', 'LKR')
            ->assertJsonPath('data.is_eligible_for_payment', true);
    }

    /** 2. Customer cannot pay another customer's job */
    public function test_customer_cannot_pay_another_customers_job(): void
    {
        $response = $this->actingAs($this->otherCustomer)->postJson("/api/jobs/{$this->completedJob->id}/payment");

        $response->assertForbidden()
            ->assertJsonPath('success', false);
    }

    /** 3. Payment amount is derived server-side from job */
    public function test_payment_amount_is_derived_serverside(): void
    {
        // Try submitting malicious amount 1.00 from client
        $response = $this->actingAs($this->customer)->postJson("/api/jobs/{$this->completedJob->id}/payment", [
            'amount' => 1.00,
            'currency' => 'USD',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.amount', 5000)
            ->assertJsonPath('data.currency', 'LKR');

        $this->assertDatabaseHas('payments', [
            'job_id' => $this->completedJob->id,
            'amount' => 5000.00,
            'currency' => 'LKR',
            'status' => 'PAID',
        ]);
    }

    /** 4. Successful test payment works and updates status */
    public function test_successful_test_payment_works(): void
    {
        $response = $this->actingAs($this->customer)->postJson("/api/jobs/{$this->completedJob->id}/payment", [
            'payment_method' => 'TEST_PAYMENT',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'PAID')
            ->assertJsonPath('data.job_id', $this->completedJob->id);

        $this->assertDatabaseHas('payments', [
            'job_id' => $this->completedJob->id,
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker->id,
            'status' => 'PAID',
        ]);
    }

    /** 5. Failed test payment records failure */
    public function test_failed_test_payment_records_failure(): void
    {
        $response = $this->actingAs($this->customer)->postJson("/api/jobs/{$this->completedJob->id}/payment", [
            'simulate_failure' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.status', 'FAILED');

        $this->assertDatabaseHas('payments', [
            'job_id' => $this->completedJob->id,
            'status' => 'FAILED',
        ]);

        // Transaction should NOT be created for failure
        $this->assertDatabaseMissing('transactions', [
            'job_id' => $this->completedJob->id,
            'status' => 'COMPLETED',
        ]);
    }

    /** 6. Incomplete / uncompleted job is not eligible for payment */
    public function test_uncompleted_job_cannot_be_paid(): void
    {
        $response = $this->actingAs($this->customer)->postJson("/api/jobs/{$this->requestedJob->id}/payment");

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    /** 7. Duplicate payment for same completed job is strictly prevented */
    public function test_duplicate_payment_is_prevented(): void
    {
        // First payment
        $this->actingAs($this->customer)->postJson("/api/jobs/{$this->completedJob->id}/payment")->assertOk();

        // Second payment attempt
        $secondResponse = $this->actingAs($this->customer)->postJson("/api/jobs/{$this->completedJob->id}/payment");

        $secondResponse->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'This job has already been paid successfully.');

        $this->assertEquals(1, Payment::where('job_id', $this->completedJob->id)->where('status', 'PAID')->count());
    }

    /** 8. Successful payment creates transaction record */
    public function test_successful_payment_creates_transaction_record(): void
    {
        $response = $this->actingAs($this->customer)->postJson("/api/jobs/{$this->completedJob->id}/payment");

        $response->assertOk();

        $this->assertDatabaseHas('transactions', [
            'job_id' => $this->completedJob->id,
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker->id,
            'type' => 'PAYMENT',
            'amount' => 5000.00,
            'status' => 'COMPLETED',
        ]);
    }

    /** 9. Payment notifications created for customer and worker */
    public function test_payment_creates_notifications_for_customer_and_worker(): void
    {
        $this->actingAs($this->customer)->postJson("/api/jobs/{$this->completedJob->id}/payment");

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->customer->id,
            'type' => 'PAYMENT_SUCCESSFUL',
            'title' => 'Payment Successful',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->worker->id,
            'type' => 'PAYMENT_RECEIVED',
            'title' => 'Payment Received',
        ]);
    }

    /** 10. Worker can view own earnings summary and history */
    public function test_worker_can_view_own_earnings(): void
    {
        // Complete payment first
        $this->actingAs($this->customer)->postJson("/api/jobs/{$this->completedJob->id}/payment");

        $response = $this->actingAs($this->worker)->getJson('/api/worker/earnings');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.summary.total_earnings', 5000)
            ->assertJsonPath('data.summary.paid_jobs_count', 1);
    }

    /** 11. Customer cannot access worker earnings endpoint */
    public function test_customer_cannot_access_worker_earnings(): void
    {
        $response = $this->actingAs($this->customer)->getJson('/api/worker/earnings');

        $response->assertForbidden();
    }

    /** 12. User cannot access another user's payment record */
    public function test_user_cannot_access_another_users_payment(): void
    {
        $this->actingAs($this->customer)->postJson("/api/jobs/{$this->completedJob->id}/payment");
        $payment = Payment::where('job_id', $this->completedJob->id)->firstOrFail();

        $response = $this->actingAs($this->otherCustomer)->getJson("/api/payments/{$payment->id}");

        $response->assertForbidden();
    }

    /** 13. Customer can list own payment history */
    public function test_customer_can_list_own_payment_history(): void
    {
        $this->actingAs($this->customer)->postJson("/api/jobs/{$this->completedJob->id}/payment");

        $response = $this->actingAs($this->customer)->getJson('/api/payments');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.job_id', $this->completedJob->id);
    }

    /** 14. Transactions endpoint requires authentication */
    public function test_transactions_require_authentication(): void
    {
        $response = $this->getJson('/api/transactions');
        $response->assertUnauthorized();
    }

    /** 15. User can view own transaction details */
    public function test_user_can_view_own_transaction(): void
    {
        $this->actingAs($this->customer)->postJson("/api/jobs/{$this->completedJob->id}/payment");
        $transaction = Transaction::where('job_id', $this->completedJob->id)->firstOrFail();

        $response = $this->actingAs($this->customer)->getJson("/api/transactions/{$transaction->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.reference', $transaction->reference);
    }

    /** 16. Refund flow works and creates refund transaction */
    public function test_refund_flow_works(): void
    {
        $this->actingAs($this->customer)->postJson("/api/jobs/{$this->completedJob->id}/payment");
        $payment = Payment::where('job_id', $this->completedJob->id)->firstOrFail();

        $service = new PaymentService();
        $refundResult = $service->processRefund($payment, $this->customer, 'Overcharged agreement');

        $this->assertTrue($refundResult['success']);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'REFUNDED',
        ]);
        $this->assertDatabaseHas('transactions', [
            'payment_id' => $payment->id,
            'type' => 'REFUND',
            'status' => 'COMPLETED',
        ]);
    }

    /** 17. Demo payment seeder creates valid records and is idempotent */
    public function test_demo_payment_seeder_is_idempotent(): void
    {
        $seeder = new DevelopmentPaymentSeeder();
        $seeder->run();
        $countFirst = Payment::count();

        $seeder->run();
        $countSecond = Payment::count();

        $this->assertGreaterThan(0, $countFirst);
        $this->assertEquals($countFirst, $countSecond);
    }

    /** 18. Test payment fails when app environment is production */
    public function test_test_gateway_rejects_in_production_environment(): void
    {
        app()->detectEnvironment(fn() => 'production');

        $gateway = new TestPaymentGateway();
        $payment = new Payment(['amount' => 5000, 'currency' => 'LKR']);

        $result = $gateway->process($payment);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('strictly disabled', $result['message']);
    }
}
