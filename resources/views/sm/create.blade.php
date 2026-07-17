@extends('layouts.app')

@section('title', 'New Cropping Schedule')
@section('page-title', 'New Cropping Schedule')
@section('page-subtitle', 'Step 1 — Basic Details')
@section('back', route('sm.index'))

@section('content')
    <div class="max-w-xl mx-auto">
        <div class="card">
            <div class="card-body">
                <h2 class="text-lg font-bold text-gray-900 mb-1">Step 1 — Basic Details</h2>
                <p class="text-sm text-gray-500 mb-5">Give your cropping schedule a name. You can set up lots, workers and activities right after.</p>

                <form method="POST" action="{{ route('sm.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="title" class="form-label">Title <span class="text-red-500">*</span></label>
                        <input type="text" id="title" name="title" value="{{ old('title') }}" required maxlength="255"
                            class="form-input @error('title') border-red-400! @enderror"
                            placeholder="e.g. Wet Season 2026 — Rice Cropping">
                        @error('title')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="description" class="form-label">Description <span class="text-gray-400 font-normal">(optional)</span></label>
                        <textarea id="description" name="description" rows="4" maxlength="5000"
                            class="form-textarea @error('description') border-red-400! @enderror"
                            placeholder="Notes about this season, the field, the plan…">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2 pt-2">
                        <a href="{{ route('sm.index') }}" class="btn btn-ghost">Cancel</a>
                        <button type="submit" class="btn btn-primary sm:w-auto w-full">
                            Create &amp; Continue
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
