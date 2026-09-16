<x-mail::message>
# New enquiry

**{{ $lead->name }}**@if ($lead->company) — {{ $lead->company }}@endif

@if ($lead->email)
Email: {{ $lead->email }}
@endif
@if ($lead->phone)
Phone: {{ $lead->phone }}
@endif
@if ($lead->form)
Form: {{ $lead->form->name }}
@endif
@if ($lead->service)
Service: {{ $lead->service->name }}
@endif
@if ($lead->product)
Product: {{ $lead->product->name }}
@endif
@if ($lead->submitted_from_url)
Page: {{ $lead->submitted_from_url }}
@endif
@if ($lead->last_source)
Source: {{ $lead->last_source }}@if ($lead->last_medium) / {{ $lead->last_medium }}@endif
@endif

@if ($lead->requirement || $lead->message)
> {{ $lead->requirement ?? $lead->message }}
@endif

<x-mail::button :url="$url">Open in the admin</x-mail::button>
</x-mail::message>
