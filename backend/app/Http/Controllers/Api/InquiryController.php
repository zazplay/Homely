<?php

namespace App\Http\Controllers\Api;

use App\Actions\Inquiries\CreateInquiry;
use App\Data\Inquiries\InquiryData;
use App\Data\Inquiries\StoreInquiryData;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Spatie\LaravelData\PaginatedDataCollection;

class InquiryController extends Controller
{
    /**
     * "Contact agent": send a message about a listing (apartment_id) or to an agent directly (agent_id).
     * No account needed; limited to 5 per minute per IP against spam.
     */
    #[Middleware('throttle:5,1')]
    #[BodyParameter('name', required: true, type: 'string', example: 'Daniel Rivera')]
    #[BodyParameter('contact', description: 'Phone or email', required: true, type: 'string', example: 'daniel@example.com')]
    #[BodyParameter('message', required: true, type: 'string', example: "Hi Emma, I'm interested in 214 7th Ave. Is it still available?")]
    #[BodyParameter('apartment_id', description: 'Required without agent_id', type: 'integer', example: 1)]
    #[BodyParameter('agent_id', description: 'Required without apartment_id', type: 'integer')]
    public function store(StoreInquiryData $data, CreateInquiry $createInquiry): JsonResponse
    {
        $inquiry = $createInquiry->handle($data);

        return response()->json(['id' => $inquiry->id], Response::HTTP_CREATED);
    }

    /**
     * The realtor's lead inbox, newest first.
     *
     * @return PaginatedDataCollection<array-key, InquiryData>
     */
    #[Middleware('auth:sanctum')]
    public function mine(Request $request): PaginatedDataCollection
    {
        $inquiries = $request->user()->inquiries()
            ->with('apartment')
            ->latest()
            ->orderByDesc('id')
            ->paginate(20);

        return InquiryData::collect($inquiries, PaginatedDataCollection::class);
    }

    /**
     * Mark a lead as read.
     */
    #[Middleware('auth:sanctum')]
    public function markRead(Request $request, int $inquiry): Response
    {
        // Scoped to the current realtor: someone else's lead is simply "not found".
        $lead = $request->user()->inquiries()->findOrFail($inquiry);
        $lead->read_at = now()->toImmutable(); // not mass-assignable on purpose
        $lead->save();

        return response()->noContent();
    }
}
