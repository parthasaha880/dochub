<?php

namespace App\Modules\Documents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkUploadDocumentRequest extends FormRequest
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
            'confidentiality_level' => ['nullable', 'string'],
            'document_type' => ['nullable', 'string', 'max:50'],
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'max:102400'],
        ];
    }
}
