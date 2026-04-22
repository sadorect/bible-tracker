<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'message_delivery_preference' => [
                'nullable',
                Rule::in(array_keys(User::messageDeliveryOptions())),
            ],
            'notification_preferences' => ['array'],
            'notification_preferences.reminders' => [
                'nullable',
                Rule::in(array_keys(User::notificationDeliveryOptions())),
            ],
            'notification_preferences.leader_digest' => [
                'nullable',
                Rule::in(array_keys(User::notificationDeliveryOptions())),
            ],
            'notification_preferences.admin_digest' => [
                'nullable',
                Rule::in(array_keys(User::notificationDeliveryOptions())),
            ],
            'notification_preferences.vacancy_alert' => [
                'nullable',
                Rule::in(array_keys(User::notificationDeliveryOptions())),
            ],
        ];
    }
}
