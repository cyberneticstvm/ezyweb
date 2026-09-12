<h1>New website enquiry</h1>
<p><strong>Name:</strong> {{ $enquiry->name }}</p>
@if ($enquiry->email)<p><strong>Email:</strong> {{ $enquiry->email }}</p>@endif
@if ($enquiry->phone)<p><strong>Phone:</strong> {{ $enquiry->phone }}</p>@endif
@if ($enquiry->subject)<p><strong>Subject:</strong> {{ $enquiry->subject }}</p>@endif
<p><strong>Message:</strong></p>
<p style="white-space: pre-line">{{ $enquiry->message }}</p>
<p>Submitted from {{ $enquiry->source_page ?: 'the website' }} at {{ $enquiry->submitted_at?->toDayDateTimeString() }}.</p>
