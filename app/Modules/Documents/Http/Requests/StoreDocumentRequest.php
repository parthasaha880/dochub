<?php

namespace App\Modules\Documents\Http\Requests;

use App\Modules\Documents\Enums\ConfidentialityLevel;
use App\Modules\Documents\Enums\DocumentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
            'folder_id' => ['nullable', 'uuid', 'exists:folders,id'],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'category_id' => ['nullable', 'uuid', 'exists:document_categories,id'],
            'subcategory_id' => ['nullable', 'uuid', 'exists:document_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'archive_no' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'keywords' => ['nullable', 'string'],
            'confidentiality_level' => ['nullable', Rule::enum(ConfidentialityLevel::class)],
            'document_type' => ['nullable', 'string', 'max:50'],
            'retention_until' => ['nullable', 'date'],
            'archive_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::enum(DocumentStatus::class)],
            'remarks' => ['nullable', 'string'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:100'],
            'file' => ['required', 'file', 'max:102400'],
        ];
    }
}
