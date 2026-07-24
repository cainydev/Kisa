<?php

namespace App\Http\Controllers;

use App\Support\Media\UploadTarget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MediaUploadController extends Controller
{
    /**
     * Largest accepted upload, in kilobytes. Scanned multi-page delivery notes
     * are the realistic worst case.
     */
    private const MAX_KILOBYTES = 25600;

    /**
     * Store a document into a media collection of the signed target.
     *
     * The route carries the target type, id and collection inside the
     * signature, so a leaked URL can only ever write the one document it was
     * minted for. The file itself is still validated here: the signature
     * proves who asked for the upload, not what was uploaded.
     */
    public function store(Request $request, string $type, int $id, string $collection): JsonResponse
    {
        if (! UploadTarget::supports($type, $collection)) {
            return response()->json([
                'message' => "Unknown upload target \"{$type}/{$collection}\".",
            ], 404);
        }

        $record = UploadTarget::resolve($type, $id);

        if ($record === null) {
            return response()->json([
                'message' => "No {$type} with id {$id}.",
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'file' => [
                'required',
                'file',
                'mimetypes:application/pdf',
                'max:'.self::MAX_KILOBYTES,
            ],
        ], [
            'file.required' => 'Attach the document as multipart form field "file".',
            'file.mimetypes' => 'Only PDF documents are accepted.',
            'file.max' => 'The document exceeds '.(self::MAX_KILOBYTES / 1024).' MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $media = $record
            ->addMediaFromRequest('file')
            ->usingFileName($this->safeFileName($request->file('file')->getClientOriginalName()))
            ->toMediaCollection($collection);

        return response()->json([
            'message' => 'Stored '.$media->file_name.' on '.UploadTarget::describe($record).'.',
            'media_id' => $media->id,
            'collection' => $collection,
        ]);
    }

    /**
     * Strip the client-supplied name down to a safe slug and force the .pdf
     * extension. The media disk is local, so a name like "x.php" reaching the
     * filesystem would be executable; the mimetypes rule alone does not
     * constrain the extension.
     */
    private function safeFileName(string $original): string
    {
        $base = pathinfo($original, PATHINFO_FILENAME);
        $base = Str::ascii($base);
        $base = preg_replace('/[^A-Za-z0-9._-]+/', '-', $base) ?? '';
        $base = trim($base, '-.');

        return ($base === '' ? 'dokument' : mb_substr($base, 0, 120)).'.pdf';
    }
}
