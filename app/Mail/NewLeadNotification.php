<?php

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewLeadNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Lead $lead) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New enquiry: '.$this->lead->name.($this->lead->form ? ' via '.$this->lead->form->name : ''));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.new-lead', with: ['lead' => $this->lead, 'url' => url('/admin/leads/'.$this->lead->id)]);
    }
}
