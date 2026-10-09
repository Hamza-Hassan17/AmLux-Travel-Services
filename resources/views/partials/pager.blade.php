<div class="pager">
    <div>@if ($p->total()) Showing {{ $p->firstItem() }}–{{ $p->lastItem() }} of {{ $p->total() }} @else No results @endif</div>
    @if ($p->hasPages())
        <div class="pager-btns">
            @if ($p->onFirstPage())
                <span class="btn btn-ghost btn-sm is-disabled">Previous</span>
            @else
                <a class="btn btn-ghost btn-sm" href="{{ $p->previousPageUrl() }}">Previous</a>
            @endif
            <span class="pager-page">Page {{ $p->currentPage() }} of {{ $p->lastPage() }}</span>
            @if ($p->hasMorePages())
                <a class="btn btn-ghost btn-sm" href="{{ $p->nextPageUrl() }}">Next</a>
            @else
                <span class="btn btn-ghost btn-sm is-disabled">Next</span>
            @endif
        </div>
    @endif
</div>
