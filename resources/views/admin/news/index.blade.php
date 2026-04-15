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
                        <textarea name="content" class="form-control" rows="8" required placeholder="Write article content here... HTML is allowed."></textarea>
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
                                        <button class="btn btn-sm btn-outline-info rounded-3 me-1"><i class="fas fa-edit"></i></button>
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
@endsection
