<?php

namespace App\Http\Requests\Erkap;

use App\Models\Erkap\ZBBReview;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateZBBReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('erkap.zbb-reviews.edit');
    }

    public function rules(): array
    {
        return [
            'zbb_status' => ['required', Rule::in(ZBBReview::STATUSES)],
            'increase_rationale' => ['nullable', 'string', 'max:2000'],
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}