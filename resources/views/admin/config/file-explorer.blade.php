@extends('layouts.admin')

@section('page_title', 'Master File Explorer')

@section('content')
<div class="card shadow-sm border-0 rounded-3">
    <div class="card-header bg-white py-3 border-0 d-flex align-items-center justify-content-between">
        <div>
            <h5 class="card-title fw-bold mb-0">System Asset Manager</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb small mb-0 mt-1">
                    <li class="breadcrumb-item"><a href="{{ route('admin.config.file-explorer') }}">Public</a></li>
                    @if($subPath)
                        @foreach(explode('/', $subPath) as $part)
                            <li class="breadcrumb-item active">{{ $part }}</li>
                        @endforeach
                    @endif
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.config.file-explorer') }}" class="btn btn-sm btn-outline-secondary rounded-pill"><i class="fas fa-sync me-1"></i> Refresh</a>
        </div>
    </div>
    
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-secondary text-uppercase small">
                    <tr>
                        <th class="ps-3 border-0">Name</th>
                        <th class="border-0">Size</th>
                        <th class="text-end pe-3 border-0">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- DIRECTORIES -->
                    @foreach($directories as $d)
                    <tr>
                        <td class="ps-3">
                            <a href="?path={{ $d['path'] }}" class="text-decoration-none text-dark d-flex align-items-center">
                                <i class="fas fa-folder fa-2x text-warning me-3"></i>
                                <div>
                                    <div class="fw-bold">{{ $d['name'] }}</div>
                                    <div class="small text-muted">{{ $d['count'] }} items</div>
                                </div>
                            </a>
                        </td>
                        <td>—</td>
                        <td class="text-end pe-3 text-muted small">Folder</td>
                    </tr>
                    @endforeach

                    <!-- FILES -->
                    @foreach($files as $f)
                    <tr>
                        <td class="ps-3">
                            <div class="d-flex align-items-center">
                                @if($f['is_image'])
                                    <div class="rounded border bg-light me-3 overflow-hidden d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                        <img src="{{ $f['url'] }}" class="w-100 h-100 object-fit-cover shadow-sm clickable-img" data-bs-toggle="modal" data-bs-target="#imgModal" data-src="{{ $f['url'] }}">
                                    </div>
                                @else
                                    <i class="fas fa-file-alt fa-2x text-secondary me-3"></i>
                                @endif
                                <div>
                                    <div class="fw-bold text-dark text-truncate" style="max-width: 300px;">{{ $f['name'] }}</div>
                                    <a href="{{ $f['url'] }}" target="_blank" class="small text-primary">View Original</a>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge bg-light text-dark border">{{ $f['size'] }} KB</span></td>
                        <td class="text-end pe-3">
                            <form action="{{ route('admin.config.file-explorer.delete') }}" method="POST" onsubmit="return confirm('Permanently delete this file?')">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="path" value="{{ $f['path'] }}">
                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach

                    @if(empty($directories) && empty($files))
                    <tr><td colspan="3" class="text-center py-5 text-muted">No assets found in this directory.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- IMAGE PREVIEW MODAL -->
<div class="modal fade" id="imgModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-body p-0 text-center">
                <img id="modalImg" src="" class="img-fluid rounded shadow-lg border border-3 border-white">
                <div class="mt-3">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Close Preview</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .clickable-img { cursor: zoom-in; transition: 0.2s; }
    .clickable-img:hover { transform: scale(1.05); }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const clickableImgs = document.querySelectorAll('.clickable-img');
    const modalImg = document.getElementById('modalImg');
    
    clickableImgs.forEach(img => {
        img.addEventListener('click', function() {
            modalImg.src = this.getAttribute('data-src');
        });
    });
});
</script>
@endsection
