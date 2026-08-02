<?php

namespace App\Modules\Notifications\Notifications;

use App\Modules\Documents\Models\Document;
use App\Modules\Workflow\Models\WorkflowInstance;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkflowActivityNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $event,
        public WorkflowInstance $instance,
        public ?string $message = null,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (config('edams.workflow_notify_mail', false)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        $document = $this->instance->document;

        return [
            'type' => 'workflow.'.$this->event,
            'title' => $this->title(),
            'message' => $this->message ?: $this->title(),
            'document_id' => $document?->id,
            'document_title' => $document?->title,
            'instance_id' => $this->instance->id,
            'status' => $this->instance->status?->value ?? $this->instance->status,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[EDAMS] '.$this->title())
            ->line($this->message ?: $this->title())
            ->line('Document: '.($this->instance->document?->title ?? '—'))
            ->action('Open Workflow', url('/workflow'));
    }

    private function title(): string
    {
        return match ($this->event) {
            'submitted' => 'Document submitted for approval',
            'approved' => 'Document approved',
            'rejected' => 'Document rejected',
            'returned' => 'Document returned for revision',
            'cancelled' => 'Workflow cancelled',
            default => 'Workflow update',
        };
    }
}
