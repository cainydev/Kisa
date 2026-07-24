<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Delivery;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class MediaUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        User::create([
            'name' => 'Marcus Wagner',
            'email' => 'marcus@example.test',
            'password' => bcrypt('secret'),
        ]);
        Supplier::factory()->create();
    }

    private function signedUrl(string $type, int $id, string $collection): string
    {
        return URL::temporarySignedRoute('media.upload', now()->addMinutes(30), [
            'type' => $type,
            'id' => $id,
            'collection' => $collection,
        ]);
    }

    private function pdf(string $name = 'rechnung.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, '%PDF-1.4 fake');
    }

    public function test_signed_url_stores_invoice_on_delivery(): void
    {
        $delivery = Delivery::factory()->create();

        $this->post($this->signedUrl('delivery', $delivery->id, 'invoice'), [
            'file' => $this->pdf(),
        ])->assertOk();

        $this->assertCount(1, $delivery->fresh()->getMedia('invoice'));
    }

    public function test_upload_without_signature_is_rejected(): void
    {
        $delivery = Delivery::factory()->create();

        $this->post("/api/uploads/delivery/{$delivery->id}/invoice", [
            'file' => $this->pdf(),
        ])->assertForbidden();

        $this->assertCount(0, $delivery->fresh()->getMedia('invoice'));
    }

    public function test_expired_signature_is_rejected(): void
    {
        $delivery = Delivery::factory()->create();
        $url = URL::temporarySignedRoute('media.upload', now()->subMinute(), [
            'type' => 'delivery',
            'id' => $delivery->id,
            'collection' => 'invoice',
        ]);

        $this->post($url, ['file' => $this->pdf()])->assertForbidden();

        $this->assertCount(0, $delivery->fresh()->getMedia('invoice'));
    }

    public function test_signature_cannot_be_retargeted_to_another_delivery(): void
    {
        $signed = Delivery::factory()->create();
        $other = Delivery::factory()->create();

        $url = $this->signedUrl('delivery', $signed->id, 'invoice');
        $tampered = str_replace(
            "/uploads/delivery/{$signed->id}/invoice",
            "/uploads/delivery/{$other->id}/invoice",
            $url
        );

        $this->post($tampered, ['file' => $this->pdf()])->assertForbidden();

        $this->assertCount(0, $other->fresh()->getMedia('invoice'));
    }

    public function test_non_pdf_is_rejected(): void
    {
        $delivery = Delivery::factory()->create();

        $this->post($this->signedUrl('delivery', $delivery->id, 'invoice'), [
            'file' => UploadedFile::fake()->createWithContent('shell.php', '<?php echo 1;'),
        ])->assertStatus(422);

        $this->assertCount(0, $delivery->fresh()->getMedia('invoice'));
    }

    public function test_php_extension_is_rejected_even_with_pdf_content(): void
    {
        $delivery = Delivery::factory()->create();

        $this->post($this->signedUrl('delivery', $delivery->id, 'invoice'), [
            'file' => UploadedFile::fake()->createWithContent('evil.php', '%PDF-1.4 fake'),
        ])->assertStatus(422);

        $this->assertCount(0, $delivery->fresh()->getMedia('invoice'));
    }

    /**
     * Second layer behind the mimetypes rule: whatever name survives
     * validation is still slugged and forced to .pdf before it hits the local
     * disk, so nothing lands under a name the web server would execute.
     */
    public function test_stored_file_name_is_slugged_and_forced_to_pdf(): void
    {
        $delivery = Delivery::factory()->create();

        $this->post($this->signedUrl('delivery', $delivery->id, 'invoice'), [
            'file' => $this->pdf('../../etc/pa ss wd;rm -rf.pdf'),
        ])->assertOk();

        $media = $delivery->fresh()->getFirstMedia('invoice');

        $this->assertNotNull($media);
        $this->assertStringEndsWith('.pdf', $media->file_name);
        $this->assertStringNotContainsString('/', $media->file_name);
        $this->assertStringNotContainsString('..', $media->file_name);
        $this->assertStringNotContainsString(';', $media->file_name);
    }

    public function test_unregistered_collection_is_rejected(): void
    {
        $delivery = Delivery::factory()->create();

        $this->post($this->signedUrl('delivery', $delivery->id, 'geheim'), [
            'file' => $this->pdf(),
        ])->assertNotFound();
    }

    public function test_missing_record_is_rejected(): void
    {
        $this->post($this->signedUrl('delivery', 999999, 'invoice'), [
            'file' => $this->pdf(),
        ])->assertNotFound();
    }

    public function test_certificate_document_can_be_uploaded(): void
    {
        $certificate = Certificate::factory()->for(Supplier::factory())->create();

        $this->post($this->signedUrl('certificate', $certificate->id, 'document'), [
            'file' => $this->pdf('zertifikat.pdf'),
        ])->assertOk();

        $this->assertCount(1, $certificate->fresh()->getMedia('document'));
    }

    public function test_upload_replaces_previous_single_file_document(): void
    {
        $delivery = Delivery::factory()->create();

        $this->post($this->signedUrl('delivery', $delivery->id, 'invoice'), ['file' => $this->pdf('alt.pdf')])->assertOk();
        $this->post($this->signedUrl('delivery', $delivery->id, 'invoice'), ['file' => $this->pdf('neu.pdf')])->assertOk();

        $media = $delivery->fresh()->getMedia('invoice');

        $this->assertCount(1, $media);
        $this->assertSame('neu.pdf', $media->first()->file_name);
    }
}
