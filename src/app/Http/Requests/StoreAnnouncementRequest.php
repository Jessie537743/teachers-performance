<?php

namespace App\Http\Requests;

use App\Policies\AnnouncementPolicy;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Announcement::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'title'          => ['required', 'string', 'max:200'],
            'body_markdown'  => ['required', 'string', 'max:20000'],
            'priority'       => ['required', Rule::in(['info', 'normal', 'critical'])],
            'is_pinned'      => ['sometimes', 'boolean'],
            'everyone'       => ['sometimes', 'boolean'],
            'show_on_login'  => ['sometimes', 'boolean'],
            'status'         => ['required', Rule::in(['draft', 'published'])],
            'publish_at'     => ['nullable', 'date'],
            'expires_at'     => ['nullable', 'date', 'after_or_equal:publish_at'],

            'targets'                    => ['array'],
            'targets.*.target_type'      => ['required_with:targets', Rule::in(['role', 'department', 'user'])],
            'targets.*.target_id'        => ['required_with:targets', 'string', 'max:64'],
            'targets.*.is_exclude'       => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($v) {
            $policy = new AnnouncementPolicy();
            $ok = $policy->validateTargeting($this->user(), [
                'everyone' => (bool) $this->boolean('everyone'),
                'targets'  => $this->input('targets', []),
            ]);
            if (! $ok) {
                $v->errors()->add('targets', 'Targeting is outside your allowed scope.');
            }
        });
    }
}
