<?php

use App\Models\Certificate;
use App\Models\Delivery;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    Storage::fake('public');

    User::create([
        'name' => 'Marcus Wagner',
        'email' => 'marcus@example.test',
        'password' => bcrypt('secret'),
    ]);
    Supplier::factory()->create();

    $this->delivery = Delivery::factory()->create();
});

/**
 * A signed upload URL that is valid for the next 30 minutes.
 */
function signedUrl(string $type, int $id, string $collection): string
{
    return URL::temporarySignedRoute('media.upload', now()->addMinutes(30), [
        'type' => $type,
        'id' => $id,
        'collection' => $collection,
    ]);
}

function pdf(string $name = 'rechnung.pdf'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, '%PDF-1.4 fake');
}

/**
 * Upload a file to the delivery under test through a freshly signed URL.
 */
function uploadToDelivery(UploadedFile $file, string $collection = 'invoice')
{
    return test()->post(
        signedUrl('delivery', test()->delivery->id, $collection),
        ['file' => $file]
    );
}

describe('accepted uploads', function () {
    it('stores an invoice on a delivery', function () {
        uploadToDelivery(pdf())->assertOk();

        expect($this->delivery->fresh()->getMedia('invoice'))->toHaveCount(1);
    });

    it('stores a document on a certificate', function () {
        $certificate = Certificate::factory()->for(Supplier::factory())->create();

        $this->post(signedUrl('certificate', $certificate->id, 'document'), [
            'file' => pdf('zertifikat.pdf'),
        ])->assertOk();

        expect($certificate->fresh()->getMedia('document'))->toHaveCount(1);
    });

    it('replaces the previous file in a single-file collection', function () {
        uploadToDelivery(pdf('alt.pdf'))->assertOk();
        uploadToDelivery(pdf('neu.pdf'))->assertOk();

        $media = $this->delivery->fresh()->getMedia('invoice');

        expect($media)->toHaveCount(1)
            ->and($media->first()->file_name)->toBe('neu.pdf');
    });
});

describe('rejected uploads', function () {
    it('rejects an upload with no signature', function () {
        $this->post("/api/uploads/delivery/{$this->delivery->id}/invoice", [
            'file' => pdf(),
        ])->assertForbidden();

        expect($this->delivery->fresh()->getMedia('invoice'))->toBeEmpty();
    });

    it('rejects an expired signature', function () {
        $url = URL::temporarySignedRoute('media.upload', now()->subMinute(), [
            'type' => 'delivery',
            'id' => $this->delivery->id,
            'collection' => 'invoice',
        ]);

        $this->post($url, ['file' => pdf()])->assertForbidden();

        expect($this->delivery->fresh()->getMedia('invoice'))->toBeEmpty();
    });

    it('rejects a signature retargeted to another delivery', function () {
        $other = Delivery::factory()->create();

        $tampered = str_replace(
            "/uploads/delivery/{$this->delivery->id}/invoice",
            "/uploads/delivery/{$other->id}/invoice",
            signedUrl('delivery', $this->delivery->id, 'invoice')
        );

        $this->post($tampered, ['file' => pdf()])->assertForbidden();

        expect($other->fresh()->getMedia('invoice'))->toBeEmpty();
    });

    it('rejects $name', function (string $filename, string $contents) {
        uploadToDelivery(UploadedFile::fake()->createWithContent($filename, $contents))
            ->assertStatus(422);

        expect($this->delivery->fresh()->getMedia('invoice'))->toBeEmpty();
    })->with([
        'a non-pdf file' => ['shell.php', '<?php echo 1;'],
        'a .php extension carrying pdf content' => ['evil.php', '%PDF-1.4 fake'],
    ]);

    it('rejects a collection that is not registered on the target', function () {
        uploadToDelivery(pdf(), collection: 'geheim')->assertNotFound();
    });

    it('rejects an upload for a record that does not exist', function () {
        $this->post(signedUrl('delivery', 999999, 'invoice'), ['file' => pdf()])
            ->assertNotFound();
    });
});

describe('stored file names', function () {
    /**
     * Second layer behind the mimetypes rule: whatever name survives validation
     * is still slugged and forced to .pdf before it hits the local disk, so
     * nothing lands under a name the web server would execute.
     */
    it('slugs the name and forces a pdf extension', function () {
        uploadToDelivery(pdf('../../etc/pa ss wd;rm -rf.pdf'))->assertOk();

        $media = $this->delivery->fresh()->getFirstMedia('invoice');

        expect($media)->not->toBeNull()
            ->and($media->file_name)->toEndWith('.pdf')
            ->and($media->file_name)->not->toContain('/')
            ->and($media->file_name)->not->toContain('..')
            ->and($media->file_name)->not->toContain(';');
    });

    /**
     * Öko-codes are full of umlauts, so they must transliterate rather than be
     * stripped: "DE-ÖKO-001…" should not land as "DE--KO-001…".
     */
    it('transliterates umlauts rather than stripping them', function () {
        uploadToDelivery(pdf('DE-ÖKO-001.276-0059778.2025.002.pdf'), collection: 'certificate')
            ->assertOk();

        expect($this->delivery->fresh()->getFirstMedia('certificate')->file_name)
            ->toBe('DE-OKO-001.276-0059778.2025.002.pdf');
    });
});
