@extends('layouts.admin')
@section('page_title', 'Bulk Interest Import')
@section('content')
<div class="card">
    <div class="card-body text-center py-5">
        <i class="fas fa-file-upload fa-4x text-muted mb-3"></i>
        <h3>Bulk Import Utility</h3>
        <p class="text-muted">This module is currently being configured for the new data structure.</p>
        <a href="{{ route('admin.members.index') }}" class="btn btn-primary mt-3">Back to Members</a>
    </div>
</div>
@endsection
