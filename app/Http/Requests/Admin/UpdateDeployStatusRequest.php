<?php

namespace App\Http\Requests\Superadmin;

use App\Enums\DeployStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeployStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'deploy_status' => ['required', Rule::enum(DeployStatus::class)],
        ];
    }
}
