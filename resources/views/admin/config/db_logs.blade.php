@extends('layouts.admin')
@section('page_title', 'Database & System Logs')
@section('content')
<div class="card">
    <div class="card-body">
        <h3>System Activity Logs</h3>
        <p class="text-muted">Viewing raw database query logs and system events.</p>
        <div class="alert alert-info">Logs are being streamed to the filesystem.</div>
    </div>
</div>
@endsection
