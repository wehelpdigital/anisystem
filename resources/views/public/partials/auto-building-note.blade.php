{{-- On the public pages that sell Anee Auto, while it is locked (App\Support\AutoLock). Yellow, so it reads the same on a
     dark hero and on a light section. --}}
@unless (\App\Support\AutoLock::open())
    @once
        <style>
            .aab-note { display: block; max-width: 42rem; margin: 0 0 1rem; padding: .7rem .95rem; border-radius: .85rem; background: #f5c518; color: #1f2937; font-size: .9rem; line-height: 1.45; text-align: left; }
            .aab-note b { font-weight: 800; }
        </style>
    @endonce
    <p class="aab-note"><b>{{ \App\Support\AutoLock::TITLE }}.</b> You can start free today with every other tool, and Anee Auto opens as soon as it is ready.</p>
@endunless
