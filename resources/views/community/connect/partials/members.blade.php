{{-- A run of member cards. Reused by the directory page, by "load more", and
     by My Co-Farmers, so a member looks the same wherever they are met.

     What a card says about somebody: their cover, their face, what is on
     their mind, where they farm, and how many co-farmers you already share —
     which is the fact that actually decides whether a stranger is worth
     tapping. It carried their latest post instead, which cost four queries a
     page plus two more per card and told a reader less.

     Expects: $members (each with connStatus, and optionally mutualCount +
     isFollowed, which the two pages attach). --}}
@foreach ($members as $m)
    @php
        /* A cover of their own, or a colour that is theirs — same person,
         * same band every time.
         *
         * Hashed from the name rather than taken from the id: these ids run
         * in steps of six, so id % 6 handed a whole column of members the
         * same pink. The avatar hues are chosen this way for the same
         * reason. */
        $mcHue = crc32(mb_strtolower(trim((string) $m->full_name))) % 6;
        $mutual = (int) ($m->mutualCount ?? 0);
    @endphp
    <div class="card card-hover mb-4 mc-card" data-member-card="{{ $m->id }}">
        {{-- The cover runs to the card's edge; the face overlaps it, the way a
             profile does, so a card reads as a person rather than a row. --}}
        <div class="mc-cover mc-tint-{{ $mcHue }}">
            @if ($m->coverPath)
                {{-- A cover whose file has gone leaves a broken-image glyph
                     across the top of the card; the band's own colour is a
                     better answer than that. --}}
                <img src="{{ \App\Support\MediaStore::url($m->coverPath) }}" alt="" loading="lazy"
                     onerror="this.remove()">
            @endif
        </div>
        <div class="mc-body">
            <div class="mc-head">
                {{-- What is on their mind sits above the photo, in the cloud
                     the wall's composer uses — same shape, same place. --}}
                <a href="{{ route('community.connect.profile', ['userId' => $m->id]) }}" class="mc-face">
                    @include('community.partials.avatar-status', ['user' => $m, 'size' => 'avatar-lg', 'link' => false])
                </a>
                <div class="min-w-0 grow mc-who">
                    <a href="{{ route('community.connect.profile', ['userId' => $m->id]) }}" class="mc-name">{{ $m->full_name }}</a>
                    @if (filled($m->headline))
                        <p class="mc-line">{{ $m->headline }}</p>
                    @endif
                    @if (filled($m->location))
                        <p class="mc-line mc-where">
                            <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9.69 18.933C9.89 19.02 10 19 10 19s.11.02.31-.067l.005-.002.018-.008a5.74 5.74 0 00.281-.14 13.73 13.73 0 002.288-1.582C15.02 14.828 17 12.353 17 9A7 7 0 103 9c0 3.353 1.98 5.828 4.098 7.201a13.73 13.73 0 002.29 1.582 5.74 5.74 0 00.28.14l.019.008.005.002zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd"/></svg>
                            <span class="truncate">{{ $m->location }}</span>
                        </p>
                    @endif
                </div>
            </div>

            {{-- The one fact that makes a stranger worth tapping — and,
                 when there is none, the same fallback the wall uses rather
                 than a line saying nothing twice. --}}
            <p class="mc-mutual">
                @if ($mutual > 0)
                    <span class="mc-mutual-n">{{ $mutual }}</span>
                    {{ \Illuminate\Support\Str::plural('mutual co-farmer', $mutual) }}
                @elseif ($m->created_at)
                    <span class="mc-mutual-none">🌱 Member since {{ $m->created_at->timezone('Asia/Manila')->format('M Y') }}</span>
                @endif
            </p>

            <div class="mc-acts">
                @include('community.connect.partials.action', ['status' => $m->connStatus, 'memberId' => $m->id])
                <button type="button" class="fp-follow {{ ($m->isFollowed ?? false) ? 'is-on' : '' }}"
                        data-follow="{{ $m->id }}" data-name="{{ $m->full_name }}"
                        aria-pressed="{{ ($m->isFollowed ?? false) ? 'true' : 'false' }}">
                    <span class="on">Following</span><span class="off">+ Follow</span>
                </button>
            </div>
        </div>
    </div>
@endforeach
