@extends('backEnd.layouts.app')
@section('title', 'Front-end Template')

@section('content')
<style>
    /* Scoped to this screen: no admin-wide stylesheet is touched. */
    .fm-theme-option {
        position: relative;
        display: flex;
        flex-direction: column;
        height: 100%;
        padding: 1.25rem;
        border: 2px solid #e5e7eb;
        border-radius: .75rem;
        background: #fff;
        cursor: pointer;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .fm-theme-option:hover {
        border-color: #94a3b8;
    }
    .fm-theme-option.is-active {
        border-color: #16a34a;
        box-shadow: 0 0 0 3px rgba(22, 163, 74, .15);
    }
    .fm-theme-option__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        margin-bottom: .5rem;
    }
    .fm-theme-option__title {
        font-weight: 600;
        font-size: .95rem;
        color: #111827;
    }
    .fm-theme-option__text {
        margin: 0 0 .75rem;
        font-size: .82rem;
        line-height: 1.6;
        color: #6b7280;
    }
    .fm-theme-option__path {
        display: block;
        margin-top: auto;
        padding-top: .75rem;
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: .72rem;
        color: #9ca3af;
        word-break: break-all;
    }
    .fm-theme-option__preview {
        margin-top: .75rem;
    }
    .fm-coverage td {
        vertical-align: middle;
        font-size: .82rem;
    }
    .fm-coverage code {
        font-size: .75rem;
    }
</style>

<div class="row">
    <div class="col-12">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
            <h4 class="mb-0">Front-end Template</h4>
            <span class="text-muted small">আপনার সাইটের গোটা ডিজাইন এখান থেকে বদলানো যায়</span>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <form action="{{ route('admin.frontend-theme.update') }}" method="POST" id="fmThemeForm">
                    @csrf
                    <p class="text-muted mb-3" style="font-size:.85rem">
                        কোন টেমপ্লেট সেট চালু থাকবে সেটা নির্বাচন করে সেভ করুন। পুরনো ডিজাইন
                        <strong>কোথাও মুছে যায় না</strong> — আলাদা ফোল্ডারে নিরাপদ আছে, যেকোনো সময় ফিরিয়ে নেওয়া যাবে।
                    </p>

                    <div class="row g-3 mb-3">
                        @foreach($catalog as $key => $tpl)
                            <div class="col-md-6 position-relative">
                                <label class="fm-theme-option {{ $stored === $key ? 'is-active' : '' }}"
                                       for="fmTheme{{ ucfirst($key) }}">
                                    <div class="fm-theme-option__head">
                                        <span class="fm-theme-option__title">{{ $tpl['name'] }}</span>
                                        @if($stored === $key)
                                            <span class="badge bg-success">চালু আছে</span>
                                        @endif
                                    </div>
                                    <p class="fm-theme-option__text">{{ $tpl['summary'] }}</p>
                                    <span class="fm-theme-option__path">resources/views/{{ $tpl['root'] }}/</span>
                                </label>

                                <div class="fm-theme-option__preview">
                                    <a class="btn btn-sm btn-outline-secondary"
                                       href="{{ $previewUrls[$key] }}" target="_blank" rel="noopener">
                                        প্রিভিউ <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                </div>

                                <input type="radio" name="frontend_theme" value="{{ $key }}"
                                       id="fmTheme{{ ucfirst($key) }}"
                                       class="visually-hidden"
                                       {{ $stored === $key ? 'checked' : '' }}>
                            </div>
                        @endforeach
                    </div>

                    <x-admin.button variant="primary" type="submit">
                        <i class="bi bi-save"></i> সেভ করুন
                    </x-admin.button>

                    @if($preview)
                        <a href="{{ $clearUrl }}" class="btn btn-outline-secondary">
                            প্রিভিউ বন্ধ করুন
                        </a>
                        <span class="text-warning small ms-1">
                            বর্তমানে শুধু আপনি প্রিভিউ মোডে দেখছেন — পাবলিক সাইটে কোনো পরিবর্তন হয়নি।
                        </span>
                    @endif
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h5 class="mb-0">কোন কোন পেজে নতুন ডিজাইন বসেছে</h5>
                    <span class="badge bg-dark">
                        {{ collect($coverage)->where('modern', true)->count() }} / {{ count($coverage) }} পেজ
                    </span>
                </div>
                <p class="text-muted" style="font-size:.8rem">
                    "সম্পন্ন" মানে নতুন টেমপ্লেট ফাইল তৈরি হয়ে গেছে। বাকিগুলো এখনো পুরনো ডিজাইনেই দেখাচ্ছে,
                    অর্থাৎ কোনো পেজ ভাঙবে না।
                </p>

                <div class="table-responsive">
                    <table class="table table-sm fm-coverage align-middle">
                        <thead>
                        <tr>
                            <th style="width:45%">ফাইল</th>
                            <th style="width:35%">স্ট্যাটাস</th>
                            <th>লিংক</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($coverage as $row)
                            <tr>
                                <td><code>{{ $row['view'] }}</code></td>
                                <td>
                                    @if($row['modern'])
                                        <span class="badge bg-success">সম্পন্ন</span>
                                    @else
                                        <span class="badge bg-secondary">পুরনো ডিজাইন</span>
                                    @endif
                                </td>
                                <td>
                                    @if($row['preview_url'])
                                        <a href="{{ $row['preview_url'] }}" target="_blank" rel="noopener"
                                           title="নতুন ডিজাইনে দেখুন">
                                            {{ parse_url($row['url'], PHP_URL_PATH) }}
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                    @elseif($row['url'])
                                        <a href="{{ $row['url'] }}" target="_blank" rel="noopener">
                                            {{ parse_url($row['url'], PHP_URL_PATH) }}
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
