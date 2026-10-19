@extends('layouts.admin')

@section('page_title', 'Security Policies')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 font-weight-bold text-dark mb-0">
                <i class="fas fa-sliders-h text-secondary mr-2"></i>Security Policies & Control Thresholds
            </h1>
            <p class="text-muted small mb-0">Configure system-wide authentication throttles, verification parameters, and threat defenses.</p>
        </div>
    </div>

    @include('admin.security._nav')

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show text-xs font-weight-bold" role="alert">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    <form action="{{ route('admin.security.policies.update') }}" method="POST">
        @csrf
        <div class="card border-0 shadow-sm" style="border-radius:12px;">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-shield-alt mr-1 text-primary"></i> Configurable Security Controls</h6>
                <button type="submit" class="btn btn-primary btn-sm font-weight-bold shadow-sm">
                    <i class="fas fa-save mr-1"></i> Save Changes
                </button>
            </div>
            <div class="card-body p-4">
                <div class="row">
                    @foreach($policies as $policy)
                        <div class="col-md-6 mb-4">
                            <div class="p-3 bg-light rounded-xl border border-gray-200 h-100">
                                <label class="font-weight-bold text-dark text-xs mb-1 d-block">
                                    {{ $policy->name }}
                                    <code class="text-muted text-[10px] ml-1">[{{ $policy->key }}]</code>
                                </label>
                                <p class="text-muted text-xs mb-2">{{ $policy->description }}</p>

                                @if($policy->type === 'boolean')
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="policy_{{ $policy->key }}" name="{{ $policy->key }}" value="1"
                                               {{ in_array(strtolower($policy->value), ['1', 'true', 'yes', 'on']) ? 'checked' : '' }}>
                                        <label class="custom-control-label font-weight-bold text-xs" for="policy_{{ $policy->key }}">
                                            Enforce / Active
                                        </label>
                                    </div>
                                @elseif($policy->type === 'integer')
                                    <div class="input-group input-group-sm" style="max-width:200px;">
                                        <input type="number" class="form-control font-weight-bold" name="{{ $policy->key }}" value="{{ $policy->value }}" required min="1">
                                    </div>
                                @else
                                    <input type="text" class="form-control form-control-sm font-weight-bold" name="{{ $policy->key }}" value="{{ $policy->value }}">
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="card-footer bg-light p-3 border-top d-flex justify-content-end">
                <button type="submit" class="btn btn-primary btn-sm font-weight-bold px-4">
                    <i class="fas fa-save mr-1"></i> Save Policy Settings
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
