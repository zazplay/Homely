<?php

namespace App\Actions\Inquiries;

use App\Data\Inquiries\StoreInquiryData;
use App\Enums\UserRole;
use App\Models\Apartment;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CreateInquiry
{
    /**
     * @throws ValidationException
     */
    public function handle(StoreInquiryData $data): Inquiry
    {
        $apartment = $data->apartmentId ? Apartment::find($data->apartmentId) : null;

        // Drafts are not public, so nobody can contact the agent about them.
        if ($apartment && ! $apartment->is_published) {
            throw ValidationException::withMessages(['apartment_id' => 'This listing is not available.']);
        }

        // From a listing the agent is its realtor; from a profile — the given agent.
        $agent = $apartment ? User::find($apartment->realtor_id) : User::find($data->agentId);

        if (! $agent || ! $agent->hasRole(UserRole::Realtor)) {
            throw ValidationException::withMessages(['agent_id' => 'This agent is not available.']);
        }

        $inquiry = new Inquiry($data->only('name', 'contact', 'message')->toArray());
        $inquiry->agent()->associate($agent);
        $inquiry->apartment()->associate($apartment);
        $inquiry->save();

        return $inquiry;
    }
}
