@extends('layouts.admin')

@section('page_title', 'Inquiries & Messages')

@section('content')
<div class="card shadow-sm border-0 rounded-3">
    <div class="card-header bg-white py-3 border-0">
        <h5 class="card-title fw-bold mb-0">Contact Form Submissions</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-secondary small text-uppercase">
                    <tr>
                        <th class="ps-3 border-0">Date</th>
                        <th class="border-0">Sender</th>
                        <th class="border-0">Subject</th>
                        <th class="border-0">Message Excerpt</th>
                        <th class="border-0">Status</th>
                        <th class="text-end pe-3 border-0">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($messages as $msg)
                    <tr class="{{ $msg->status == 'unread' ? 'bg-light-primary border-start border-primary border-4' : '' }}">
                        <td class="ps-3 small text-muted">
                            {{ \Carbon\Carbon::parse($msg->created_at)->format('d M, H:i') }}
                        </td>
                        <td>
                            <div class="fw-bold text-dark">{{ $msg->name }}</div>
                            <div class="small text-muted">{{ $msg->email }}</div>
                        </td>
                        <td>
                            <div class="text-truncate" style="max-width: 150px;">{{ $msg->subject }}</div>
                        </td>
                        <td>
                            <div class="text-muted small text-truncate" style="max-width: 300px;" title="{{ $msg->message }}">
                                {{ $msg->message }}
                            </div>
                        </td>
                        <td>
                            @if($msg->status == 'unread')
                                <span class="badge bg-primary px-2 rounded-pill small">NEW</span>
                            @else
                                <span class="badge bg-light text-muted border px-2 rounded-pill small">READ</span>
                            @endif
                        </td>
                        <td class="text-end pe-3">
                            <div class="btn-group">
                                @if($msg->status == 'unread')
                                <form action="{{ route('admin.messages.read', $msg->id) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-success rounded-3 me-1" title="Mark as Read"><i class="fas fa-check"></i></button>
                                </form>
                                @endif
                                <form action="{{ route('admin.messages.delete', $msg->id) }}" method="POST" onsubmit="return confirm('Delete message?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-3" title="Delete"><i class="fas fa-trash-alt"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">No messages found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white border-0">
        {{ $messages->links() }}
    </div>
</div>

<style>
    .bg-light-primary { background-color: rgba(13, 110, 253, 0.03); }
</style>
@endsection
