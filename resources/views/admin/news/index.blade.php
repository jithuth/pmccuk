@extends('layouts.admin')

@section('page_title', 'News & Announcements')

@section('content')
<div class="row">
    <!-- CREATE ARTICLE -->
    <div class="col-md-4">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-primary text-white border-0 py-3">
                <h5 class="card-title fw-bold mb-0"><i class="fas fa-plus-circle me-1"></i> Create New Article</h5>
            </div>
            <form action="{{ route('admin.news.add') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Article Title</label>
                        <input type="text" name="title" class="form-control" required placeholder="Display title">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Upload Image</label>
                        <input type="file" name="news_image" class="form-control">
                        <small class="text-muted">Recommended: 800x600px</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Or Image URL</label>
                        <input type="text" name="image_url" class="form-control" placeholder="https://...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Status</label>
                        <select name="status" class="form-select">
                            <option value="published">Published (Visible)</option>
                            <option value="draft">Draft (Hidden)</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold small text-uppercase">Content</label>
                        <textarea name="content" class="form-control rich-editor" required placeholder="Write article content here... HTML is allowed."></textarea>
                    </div>
                </div>
                <div class="card-footer bg-light border-0 p-3">
                    <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm rounded-pill">
                        <i class="fas fa-paper-plane me-2"></i> PUBLISH ARTICLE
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ARTICLES LIST -->
    <div class="col-md-8">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-white py-3">
                <h5 class="card-title fw-bold mb-0">Published Articles</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-secondary small text-uppercase">
                            <tr>
                                <th class="ps-3 border-0">Image</th>
                                <th class="border-0">Title & Info</th>
                                <th class="border-0">Status</th>
                                <th class="text-end pe-3 border-0">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($news as $n)
                            <tr>
                                <td class="ps-3" style="width: 100px;">
                                    <div class="rounded-3 overflow-hidden shadow-sm border" style="width: 70px; height: 50px;">
                                        <img src="{{ !empty($n->image_url) ? (str_starts_with($n->image_url, 'http') ? $n->image_url : asset('storage/'.$n->image_url)) : 'https://placehold.co/800x600?text=News' }}" class="w-100 h-100" style="object-fit: cover;">
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $n->title }}</div>
                                    <div class="small text-muted"><i class="far fa-calendar-alt me-1"></i> {{ $n->created_at->format('d M Y') }}</div>
                                </td>
                                <td>
                                    @if($n->status == 'published')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 rounded-pill small fw-bold">PUBLISHED</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 rounded-pill small fw-bold">DRAFT</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-info rounded-3 me-1" onclick="editNews({{ $n->id }})"><i class="fas fa-edit"></i></button>
                                        <form action="{{ route('admin.news.delete', $n->id) }}" method="POST" onsubmit="return confirm('Delete this article?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-3"><i class="fas fa-trash-alt"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">No news articles found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-0">
                {{ $news->links() }}
            </div>
        </div>
    </div>
</div>

<!-- EDIT NEWS MODAL -->
<div class="modal fade" id="editNewsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header bg-info text-white border-0 py-4 px-4">
                <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i> Edit News Article</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="editNewsForm" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Article Title</label>
                            <input type="text" name="title" id="e_title" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Status</label>
                            <select name="status" id="e_status" class="form-select">
                                <option value="published">Published</option>
                                <option value="draft">Draft</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">New Image (Optional)</label>
                            <input type="file" name="news_image" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Or Image URL</label>
                            <input type="text" name="image_url" id="e_image_url" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small text-uppercase">Content (HTML Supported)</label>
                            <textarea name="content" id="e_content" class="form-control rich-editor" rows="10" required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3 px-4">
                    <button type="button" class="btn btn-link text-muted fw-bold text-decoration-none" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info px-5 py-2 fw-bold text-white rounded-pill shadow-sm">
                        SAVE CHANGES <i class="fas fa-check-circle ms-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<style>
    .note-editor { border-radius: 12px !important; border: 1px solid #dee2e6 !important; background: #fff !important; }
    .note-toolbar { background: #f8f9fa !important; border-bottom: 1px solid #dee2e6 !important; border-radius: 12px 12px 0 0 !important; }
</style>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script>
    $(document).ready(function() {
        $('.rich-editor').summernote({
            height: 300,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ]
        });
    });

    function editNews(id) {
        const modalEl = document.getElementById('editNewsModal');
        const modal = new bootstrap.Modal(modalEl);
        const form = document.getElementById('editNewsForm');

        form.action = `/admin/news/${id}/update`;

        fetch(`/admin/news/${id}/details`)
            .then(res => res.json())
            .then(data => {
                document.getElementById('e_title').value = data.title;
                document.getElementById('e_status').value = data.status;
                document.getElementById('e_image_url').value = data.image_url || '';
                
                // Set Summernote content
                $('#e_content').summernote('code', data.content);
                
                modal.show();
            });
    }
</script>
@endsection
